<?php

namespace App\Modules\LinkedIn\app\Services;

use App\Models\User;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\Services\AIGatewayService;
use App\Modules\LinkedIn\app\Models\LinkedInProfile;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class LinkedInOptimizerService
{
    public function __construct(
        protected AIGatewayService $aiGateway
    ) {}

    /**
     * Retrieve or instantiate a LinkedIn profile record for user.
     */
    public function getOrCreateProfile(User $user, ?int $roleProfileId = null): LinkedInProfile
    {
        $query = LinkedInProfile::where('user_id', $user->id);

        if ($roleProfileId) {
            $query->where('role_profile_id', $roleProfileId);
        }

        $profile = $query->first();

        if ($profile) {
            return $profile;
        }

        // Return new or fallback to any existing profile for this user
        $fallback = LinkedInProfile::where('user_id', $user->id)->first();
        if ($fallback && ! $roleProfileId) {
            return $fallback;
        }

        return LinkedInProfile::create([
            'user_id' => $user->id,
            'role_profile_id' => $roleProfileId,
            'current_headline' => '',
            'current_about' => '',
            'current_experience' => '',
            'recruiter_score' => 65,
            'headline_variations' => [],
            'missing_keywords' => [],
        ]);
    }

    /**
     * Audit profile against target persona and recruiter search algorithms.
     */
    public function auditProfile(User $user, ?RoleProfile $roleProfile, array $currentProfile): array
    {
        $headline = trim($currentProfile['headline'] ?? '');
        $about = trim($currentProfile['about'] ?? '');
        $experience = trim($currentProfile['experience'] ?? '');

        // 1. Gather Target Recruiter Keywords from RoleProfile
        $targetKeywords = $this->extractTargetKeywords($roleProfile);

        // 2. Aggregate text for keyword scan
        $combinedText = mb_strtolower("{$headline} {$about} {$experience}");

        $matched = [];
        $missing = [];

        foreach ($targetKeywords as $keyword) {
            $normalizedKw = mb_strtolower(trim($keyword));
            if (str_contains($combinedText, $normalizedKw)) {
                $matched[] = $keyword;
            } else {
                // Also check without trailing 's' or minor variant
                $stem = rtrim($normalizedKw, 's');
                if (mb_strlen($stem) >= 3 && str_contains($combinedText, $stem)) {
                    $matched[] = $keyword;
                } else {
                    $missing[] = $keyword;
                }
            }
        }

        // 3. Compute Recruiter Optimization Score (0-100)
        $score = 0;

        // Keyword coverage: 45% weight (matching 7+ core target technologies yields top score)
        $coreTargetCount = min(count($targetKeywords), 7);
        $coverageRatio = $coreTargetCount > 0 ? (count($matched) / $coreTargetCount) : 0;
        $score += (int) round(min(1.0, $coverageRatio) * 45);

        // Headline score: 20% weight
        $hlLen = mb_strlen($headline);
        if ($hlLen >= 40 && $hlLen <= 220) {
            $score += 15;
            if (str_contains($headline, '|') || str_contains($headline, '•') || str_contains($headline, '/')) {
                $score += 5;
            }
        } elseif ($hlLen > 0) {
            $score += 8;
        }

        // About section depth: 20% weight
        $abLen = mb_strlen($about);
        if ($abLen >= 100) {
            $score += 15;
            if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|connect|reach out|contact/i', $about)) {
                $score += 5;
            }
        } elseif ($abLen > 0) {
            $score += 5;
        }

        // Quantifiable impact & metrics: 15% weight
        $hasMetrics = (bool) preg_match('/(\b\d+(\.\d+)?(%|x|k|M|ms|s|\$)\b|\b\d{2,}\b|\$\d+)/i', "{$about} {$experience}");
        if ($hasMetrics) {
            $score += 15;
        }

        // Clamp score
        $score = min(100, max(15, $score));

        // 4. Actionable Suggestions
        $suggestions = [];
        if ($hlLen < 40) {
            $suggestions[] = 'Your headline is too brief. Expand to 160-210 characters using your primary title, key tech stack, and core value proposition.';
        }
        if (count($missing) > 0) {
            $topMissing = array_slice($missing, 0, 4);
            $suggestions[] = 'Include missing recruiter search terms: ' . implode(', ', $topMissing) . '.';
        }
        if (! $hasMetrics) {
            $suggestions[] = 'Add quantifiable metrics (e.g., "Reduced latency by 45%", "Managed \$500k budget", "Scaled to 100k DAU").';
        }
        if ($abLen < 300) {
            $suggestions[] = 'Your About section is under 300 characters. Aim for 800-1,500 characters with a strong opening hook before the "...see more" fold.';
        }

        return [
            'recruiter_score' => $score,
            'matched_keywords' => array_values(array_unique($matched)),
            'missing_keywords' => array_values(array_unique($missing)),
            'suggestions' => $suggestions,
            'headline_length' => $hlLen,
            'about_length' => $abLen,
            'has_metrics' => $hasMetrics,
        ];
    }

    /**
     * Generate 4 distinct high-converting headline variations (under 220 characters).
     */
    public function generateHeadlines(User $user, RoleProfile $roleProfile, ?string $currentHeadline = null): array
    {
        $primaryTitle = Arr::first($roleProfile->target_titles) ?? $roleProfile->name;
        $skills = implode(', ', array_slice($roleProfile->technical_skills ?? [], 0, 5));

        $prompt = <<<EOT
You are an expert LinkedIn Personal Branding and Technical Recruiter Strategist.
Generate 4 high-converting, recruiter-optimized LinkedIn headlines for this software engineer:
Target Role: {$primaryTitle}
Core Technologies: {$skills}
Current Headline: {$currentHeadline}

IMPORTANT CONSTRAINTS:
1. Each headline MUST be strictly under 220 characters (LinkedIn character limit).
2. Format your response strictly as valid JSON with keys: "authority", "keyword", "metrics", "consulting".
3. "authority": High-impact, authoritative tone (e.g. Senior/Lead, distributed scale).
4. "keyword": Recruiter search optimized with pipe-separated high-demand technical keywords.
5. "metrics": Quantifiable impact, performance improvements, and business outcomes.
6. "consulting": Client-focused, advisory, solution architecture, or freelance value.

Respond with ONLY the JSON object.
EOT;

        try {
            $requestDTO = new AIRequestDTO(
                prompt: $prompt,
                systemPrompt: 'You are an elite career strategist. Output only valid JSON.',
                temperature: 0.6,
                maxTokens: 500
            );

            $response = $this->aiGateway->complete($user, $requestDTO);
            $parsed = $this->cleanAndParseJson($response->content);

            if ($parsed && isset($parsed['authority'], $parsed['keyword'], $parsed['metrics'], $parsed['consulting'])) {
                return [
                    'authority' => Str::limit(trim($parsed['authority']), 220, ''),
                    'keyword' => Str::limit(trim($parsed['keyword']), 220, ''),
                    'metrics' => Str::limit(trim($parsed['metrics']), 220, ''),
                    'consulting' => Str::limit(trim($parsed['consulting']), 220, ''),
                ];
            }
        } catch (Throwable $e) {
            // Fall back gracefully
        }

        return $this->getFallbackHeadlines($primaryTitle, $roleProfile->technical_skills ?? []);
    }

    /**
     * Generate an engaging LinkedIn About / Summary narrative.
     */
    public function generateAboutSection(User $user, RoleProfile $roleProfile, ?string $currentAbout = null): string
    {
        $primaryTitle = Arr::first($roleProfile->target_titles) ?? $roleProfile->name;
        $skills = implode(', ', array_slice($roleProfile->technical_skills ?? [], 0, 8));
        $years = $roleProfile->experience_years ?: 5;

        $prompt = <<<EOT
Write a compelling, first-person LinkedIn "About" section for a software engineer.
Target Title: {$primaryTitle}
Experience: {$years}+ years
Core Stack: {$skills}
Current About Draft: {$currentAbout}

Structure requirements:
1. Hook (Lines 1-3): Engaging opening problem-solver statement visible before the "...see more" fold (~200 characters).
2. What I Do: High-level technical philosophy and core engineering domains.
3. Core Technical Arsenal: Bullet points of key languages, frameworks, databases, and DevOps tools.
4. Measurable Impacts: 2-3 quantified achievements (scalability, latency, reliability).
5. Call to Action: Friendly closing invite to connect or discuss opportunities.

Tone: Professional, humble, authoritative, and builder-focused. Keep under 2,200 characters.
EOT;

        try {
            $requestDTO = new AIRequestDTO(
                prompt: $prompt,
                systemPrompt: 'You are an executive resume and LinkedIn profile writer. Provide clean, well-spaced markdown text.',
                temperature: 0.7,
                maxTokens: 900
            );

            $response = $this->aiGateway->complete($user, $requestDTO);
            if (! empty(trim($response->content))) {
                return trim($response->content);
            }
        } catch (Throwable $e) {
            // Fall back gracefully
        }

        return $this->getFallbackAboutSection($primaryTitle, $years, $roleProfile->technical_skills ?? []);
    }

    /**
     * Optimize experience bullet points to Google's XYZ format.
     */
    public function optimizeExperienceBullets(User $user, string $rawExperience): string
    {
        if (empty(trim($rawExperience))) {
            return '';
        }

        $prompt = <<<EOT
Transform the following raw resume/LinkedIn experience bullet points into high-impact accomplishments using Google's XYZ Formula:
"Accomplished [X], as measured by [Y], by doing [Z]"

Raw Bullets:
{$rawExperience}

Guidelines:
1. Start each bullet point with a powerful past-tense action verb (e.g. Architected, Engineered, Streamlined, Spearheaded, Refactored).
2. Insert quantifiable metrics or realistic impact placeholders if absent (e.g., reduced query times by 35%, supported 500k+ active users).
3. Format as clean bullet points starting with "• ".
EOT;

        try {
            $requestDTO = new AIRequestDTO(
                prompt: $prompt,
                systemPrompt: 'You are an ATS and executive career coach. Return only the optimized bullet points.',
                temperature: 0.6,
                maxTokens: 700
            );

            $response = $this->aiGateway->complete($user, $requestDTO);
            if (! empty(trim($response->content))) {
                return trim($response->content);
            }
        } catch (Throwable $e) {
            // Fall back gracefully
        }

        return $this->getFallbackExperienceBullets($rawExperience);
    }

    /**
     * Save user's updated profile state.
     */
    public function saveProfile(User $user, array $data, ?int $roleProfileId = null): LinkedInProfile
    {
        $profile = $this->getOrCreateProfile($user, $roleProfileId);

        $profile->fill([
            'role_profile_id' => $roleProfileId ?: $profile->role_profile_id,
            'current_headline' => $data['current_headline'] ?? $profile->current_headline,
            'current_about' => $data['current_about'] ?? $profile->current_about,
            'current_experience' => $data['current_experience'] ?? $profile->current_experience,
            'optimized_headline' => $data['optimized_headline'] ?? $profile->optimized_headline,
            'optimized_about' => $data['optimized_about'] ?? $profile->optimized_about,
            'optimized_experience' => $data['optimized_experience'] ?? $profile->optimized_experience,
            'headline_variations' => $data['headline_variations'] ?? $profile->headline_variations,
            'recruiter_score' => $data['recruiter_score'] ?? $profile->recruiter_score,
            'missing_keywords' => $data['missing_keywords'] ?? $profile->missing_keywords,
            'optimization_notes' => $data['optimization_notes'] ?? $profile->optimization_notes,
        ]);

        $profile->save();

        return $profile;
    }

    /**
     * Extract target keywords from role profile.
     */
    protected function extractTargetKeywords(?RoleProfile $roleProfile): array
    {
        if (! $roleProfile) {
            return [
                'PHP', 'Laravel', 'MySQL', 'RESTful APIs', 'Docker', 'Redis',
                'Microservices', 'PostgreSQL', 'Git', 'PHPUnit', 'CI/CD', 'AWS',
            ];
        }

        $rawList = array_merge(
            $roleProfile->technical_skills ?? [],
            array_slice($roleProfile->secondary_skills ?? [], 0, 4)
        );

        $cleaned = [];
        foreach ($rawList as $item) {
            // Split composite phrases like "MySQL / TiDB Cloud" or "Redis & Caching"
            $parts = preg_split('/[\/&,]/', $item);
            foreach ($parts as $part) {
                // Strip specific version numbers like 8.4, 11/12/13
                $clean = trim(preg_replace('/\b\d+(\.\d+)*\b/', '', $part));
                if (mb_strlen($clean) >= 2) {
                    $cleaned[] = $clean;
                }
            }
        }

        if (empty($cleaned)) {
            $cleaned = ['PHP', 'Laravel', 'MySQL', 'Docker', 'Redis', 'RESTful APIs'];
        }

        return array_values(array_unique(array_filter($cleaned)));
    }

    /**
     * Parse JSON from AI response safely.
     */
    protected function cleanAndParseJson(string $content): ?array
    {
        $content = trim($content);
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Deterministic fallback headlines adhering strictly to <= 220 chars.
     */
    protected function getFallbackHeadlines(string $title, array $skills): array
    {
        $skillStr = implode(' | ', array_slice($skills, 0, 4));

        return [
            'authority' => Str::limit("{$title} | Architecting High-Performance Distributed Systems & Scalable Cloud Solutions", 220, ''),
            'keyword' => Str::limit("{$title} | {$skillStr} | REST APIs & Microservices Architecture", 220, ''),
            'metrics' => Str::limit("{$title} | Scaled Enterprise Systems to 1M+ Monthly Active Users & Cut Database Latency by 40%", 220, ''),
            'consulting' => Str::limit("Senior {$title} & Technical Consultant | Enterprise Web Application Engineering & Cloud Modernization", 220, ''),
        ];
    }

    /**
     * Deterministic fallback About narrative.
     */
    protected function getFallbackAboutSection(string $title, int $years, array $skills): string
    {
        $skillList = implode("\n• ", array_slice($skills, 0, 6));

        return <<<EOT
I build resilient, high-concurrency software systems that empower businesses to scale securely and efficiently.

With over {$years}+ years of hands-on software engineering experience specializing in {$title}, I bridge the gap between business velocity and clean, maintainable architecture.

Core Engineering Focus:
• {$skillList}
• Microservices & High-Throughput RESTful APIs
• Database Query Optimization & Caching Strategies

Recent Key Impacts:
• Designed and shipped distributed backend services handling millions of monthly requests with 99.99% uptime.
• Refactored legacy monolithic applications into decoupled, test-driven micro-components, accelerating release cycles.
• Spearheaded automated CI/CD pipelines, slashing deployment friction and zero-downtime database migrations.

Let’s connect! Open to technical discussions, architecture advisory, and high-impact engineering leadership roles.
EOT;
    }

    /**
     * Deterministic XYZ bullet point transformer.
     */
    protected function getFallbackExperienceBullets(string $rawExperience): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($rawExperience));
        $output = [];

        $verbs = ['Architected', 'Engineered', 'Spearheaded', 'Optimized', 'Streamlined', 'Delivered'];
        $i = 0;

        foreach ($lines as $line) {
            $cleaned = trim(ltrim($line, '-*• \t'));
            if (empty($cleaned)) {
                continue;
            }

            // Replace passive phrases
            $cleaned = preg_replace('/^(responsible for|worked on|helped with|assisted in|participated in)\s+/i', '', $cleaned);
            $actionVerb = $verbs[$i % count($verbs)];
            $i++;

            // If line doesn't start with strong verb, prepend
            if (! preg_match('/^[A-Z][a-z]+ed\b/', $cleaned)) {
                $cleaned = "{$actionVerb} " . lcfirst($cleaned);
            }

            // If no metric found, append an XYZ outcome indicator
            if (! preg_match('/\d+/', $cleaned)) {
                $cleaned .= ', improving operational throughput by 25%';
            }

            $output[] = "• {$cleaned}";
        }

        return implode("\n", $output);
    }
}
