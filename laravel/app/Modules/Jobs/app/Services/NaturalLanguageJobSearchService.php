<?php

namespace App\Modules\Jobs\app\Services;

use App\Models\User;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\Services\AIGatewayService;
use Illuminate\Support\Facades\Log;

class NaturalLanguageJobSearchService
{
    public function __construct(
        protected ?AIGatewayService $aiGateway = null
    ) {
        $this->aiGateway = $aiGateway ?? app(AIGatewayService::class);
    }

    /**
     * Parse natural language search prompt into structured job query parameters.
     *
     * @return array{
     *     keywords: array<string>,
     *     target_role: ?string,
     *     work_mode: string,
     *     location: ?string,
     *     salary_min: ?int,
     *     intent_summary: string
     * }
     */
    public function parseQuery(string $prompt, ?User $user = null): array
    {
        $prompt = trim($prompt);
        if (empty($prompt)) {
            return $this->getDefaultParameters();
        }

        // Try AI Gateway first if user is available
        if ($user) {
            try {
                $aiParsed = $this->parseWithAI($user, $prompt);
                if ($aiParsed) {
                    return $aiParsed;
                }
            } catch (\Throwable $e) {
                Log::info("AI Natural Language Parser fallback to heuristics: {$e->getMessage()}");
            }
        }

        // Fallback: Rule-based Heuristic Parser
        return $this->parseWithHeuristics($prompt);
    }

    /**
     * Call AI gateway to extract structured JSON from user search prompt.
     */
    protected function parseWithAI(User $user, string $prompt): ?array
    {
        $systemInstructions = <<<INSTRUCT
You are an expert technical recruiting query parser.
Analyze the user's natural language job search request and extract structured search parameters as a valid JSON object.
Return ONLY raw JSON with keys:
- "keywords": array of relevant technical skills/frameworks (e.g. ["PHP 8.4", "Laravel", "TiDB", "Redis", "Vue.js"])
- "target_role": string of normalized role title (e.g. "Senior Laravel Backend Engineer") or null
- "work_mode": "Remote", "Hybrid", "Onsite", or "Any"
- "location": string of geographic preference (e.g. "Worldwide", "US", "UK", "EU") or null
- "salary_min": integer (e.g. 130000) or null
- "intent_summary": 1-line plain text summary of candidate intent
INSTRUCT;

        $requestDTO = new AIRequestDTO(
            prompt: "User Search Query: \"{$prompt}\"\n\nJSON output:",
            systemPrompt: $systemInstructions,
            temperature: 0.1,
            maxTokens: 500
        );

        $response = $this->aiGateway->complete($user, $requestDTO);
        $cleanJson = trim($response->content);

        // Strip markdown code fences if model enclosed in ```json ... ```
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $cleanJson, $matches)) {
            $cleanJson = $matches[1];
        }

        $decoded = json_decode($cleanJson, true);

        if (is_array($decoded) && isset($decoded['keywords'])) {
            return [
                'keywords' => is_array($decoded['keywords']) ? $decoded['keywords'] : [],
                'target_role' => $decoded['target_role'] ?? null,
                'work_mode' => in_array($decoded['work_mode'] ?? '', ['Remote', 'Hybrid', 'Onsite']) ? $decoded['work_mode'] : 'Any',
                'location' => $decoded['location'] ?? null,
                'salary_min' => isset($decoded['salary_min']) && is_numeric($decoded['salary_min']) ? (int) $decoded['salary_min'] : null,
                'intent_summary' => $decoded['intent_summary'] ?? "Seeking roles matching: {$prompt}",
            ];
        }

        return null;
    }

    /**
     * Resilient rule-based heuristic parser for offline, test, or fast search mode.
     */
    public function parseWithHeuristics(string $prompt): array
    {
        $lower = strtolower($prompt);

        // 1. Detect Work Mode
        $workMode = 'Any';
        if (preg_match('/\b(remote|work from home|wfh|telecommute|distributed)\b/i', $lower)) {
            $workMode = 'Remote';
        } elseif (preg_match('/\b(hybrid)\b/i', $lower)) {
            $workMode = 'Hybrid';
        } elseif (preg_match('/\b(onsite|in-office)\b/i', $lower)) {
            $workMode = 'Onsite';
        }

        // 2. Detect Salary Requirement
        $salaryMin = null;
        if (preg_match('/(?:over|\>|min|at least|paying|\$)\s*\$?(\d{2,3})(?:k|,000)?/i', $lower, $m)) {
            $val = (int) $m[1];
            $salaryMin = $val < 1000 ? $val * 1000 : $val;
        }

        // 3. Detect Technical Keywords
        $knownSkills = [
            'php 8.4' => 'PHP 8.4',
            'laravel 13' => 'Laravel 13',
            'laravel' => 'Laravel',
            'php' => 'PHP',
            'wordpress' => 'WordPress',
            'woocommerce' => 'WooCommerce',
            'tidb' => 'TiDB Cloud',
            'mysql' => 'MySQL',
            'postgresql' => 'PostgreSQL',
            'redis' => 'Redis',
            'vue' => 'Vue.js',
            'react' => 'React',
            'tailwind' => 'Tailwind CSS',
            'graphql' => 'GraphQL',
            'rest api' => 'REST APIs',
            'api' => 'APIs',
            'docker' => 'Docker',
            'aws' => 'AWS',
            'microservices' => 'Microservices',
            'tdd' => 'TDD',
            'phpunit' => 'PHPUnit',
        ];

        $matchedKeywords = [];
        foreach ($knownSkills as $needle => $canonical) {
            if (str_contains($lower, $needle)) {
                $matchedKeywords[] = $canonical;
            }
        }

        if (empty($matchedKeywords)) {
            $matchedKeywords = ['PHP', 'Backend'];
        }

        // 4. Target Role
        $targetRole = null;
        if (str_contains($lower, 'lead') || str_contains($lower, 'architect')) {
            $targetRole = 'Lead Technical Architect';
        } elseif (str_contains($lower, 'senior')) {
            $targetRole = 'Senior Software Engineer';
        } elseif (str_contains($lower, 'full stack') || str_contains($lower, 'fullstack')) {
            $targetRole = 'Full Stack Web Engineer';
        } elseif (str_contains($lower, 'wordpress') || str_contains($lower, 'woocommerce')) {
            $targetRole = 'WordPress & WooCommerce Specialist';
        } else {
            $targetRole = 'Backend Software Engineer';
        }

        // 5. Location
        $location = null;
        if (preg_match('/\b(us|usa|united states|uk|united kingdom|europe|eu|worldwide|global)\b/i', $lower, $lm)) {
            $location = strtoupper($lm[1]);
        }

        return [
            'keywords' => array_values(array_unique($matchedKeywords)),
            'target_role' => $targetRole,
            'work_mode' => $workMode,
            'location' => $location,
            'salary_min' => $salaryMin,
            'intent_summary' => "Parsed query: targeting {$targetRole} positions with focus on " . implode(', ', array_slice($matchedKeywords, 0, 4)),
        ];
    }

    /**
     * Default parameters when query is empty.
     */
    protected function getDefaultParameters(): array
    {
        return [
            'keywords' => ['PHP', 'Laravel', 'Backend'],
            'target_role' => 'Backend Software Engineer',
            'work_mode' => 'Any',
            'location' => null,
            'salary_min' => null,
            'intent_summary' => 'Displaying all active software engineering opportunities.',
        ];
    }
}

