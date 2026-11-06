<?php

namespace App\Modules\ContentStudio\app\Services;

use App\Models\User;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\Services\AIGatewayService;
use App\Modules\ContentStudio\app\Models\ContentPost;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class ContentStudioService
{
    public function __construct(
        protected AIGatewayService $aiGateway,
        protected CloudflareImageService $imageService
    ) {}

    /**
     * Ideate trending technical topics tailored to candidate's role persona.
     */
    public function generateTopicIdeas(User $user, ?RoleProfile $roleProfile, string $category = 'all'): array
    {
        $primaryTitle = $roleProfile ? (Arr::first($roleProfile->target_titles) ?? $roleProfile->name) : 'Senior Full-Stack & Backend Engineer';
        $skills = $roleProfile ? implode(', ', array_slice($roleProfile->technical_skills ?? [], 0, 6)) : 'PHP 8.4, Laravel, MySQL, Redis, Docker, Microservices';

        $prompt = <<<EOT
Suggest 6 high-engagement LinkedIn technical post topics for a {$primaryTitle}.
Tech Stack: {$skills}
Category Filter: {$category}

Focus on engineering craftsmanship, architectural trade-offs, battle-tested production lessons, and performance optimization.
Return strictly valid JSON array of objects with keys:
- "title": Compelling technical title
- "category": One of "architecture", "php_internals", "database", "devops", "craftsmanship"
- "hook": 1-2 sentence controversial or curiosity-driven opening hook

Return ONLY valid JSON.
EOT;

        try {
            $requestDTO = new AIRequestDTO(
                prompt: $prompt,
                systemPrompt: 'You are a staff engineer and viral technical LinkedIn creator. Return only valid JSON array.',
                temperature: 0.7,
                maxTokens: 700
            );

            $response = $this->aiGateway->complete($user, $requestDTO);
            $parsed = $this->cleanAndParseJsonArray($response->content);

            if (! empty($parsed) && is_array($parsed)) {
                return $parsed;
            }
        } catch (Throwable $e) {
            // Fall back gracefully
        }

        return $this->getFallbackTopics($category);
    }

    /**
     * Draft a complete technical LinkedIn post with hooks, body, code insights, and hashtags.
     */
    public function draftPost(User $user, string $topic, ?RoleProfile $roleProfile, array $options = []): array
    {
        $primaryTitle = $roleProfile ? (Arr::first($roleProfile->target_titles) ?? $roleProfile->name) : 'Senior Software Engineer';
        $skills = $roleProfile ? implode(', ', array_slice($roleProfile->technical_skills ?? [], 0, 5)) : 'PHP 8.4, Laravel, MySQL, Redis';

        $prompt = <<<EOT
Draft an authoritative, high-engagement LinkedIn post on this topic:
Topic: {$topic}
Author Persona: {$primaryTitle} specializing in {$skills}

Structure requirements:
1. Opening Hook (First 2-3 lines, max 200 characters): A compelling statement or observation that hooks developers before the "...see more" button.
2. The Problem / Production Context: What breaks, why naive approaches fail, or the hidden architectural trap.
3. The Solution / Best Practice: Technical breakdown, design pattern, or key engineering principle.
4. Actionable Takeaways: 3-4 bullet points starting with "• ".
5. Conversational Question: Closing question that invites comments, debates, and peer experiences.
6. Relevant Hashtags: 4-5 curated technical hashtags.
7. Image Prompt: A 1-sentence prompt for a clean, isometric or modern architectural diagram representing this topic visually.

Format response strictly as valid JSON with keys:
- "title": {$topic}
- "hook": Opening 2 lines
- "content": Full formatted post copy (including hook, body, bullets, and closing question)
- "hashtags": array of hashtags with #
- "image_prompt": Text-to-image prompt
- "category": technical category

Return ONLY the JSON object.
EOT;

        try {
            $requestDTO = new AIRequestDTO(
                prompt: $prompt,
                systemPrompt: 'You are an elite software architect and technical writer. Provide clean JSON.',
                temperature: 0.65,
                maxTokens: 1000
            );

            $response = $this->aiGateway->complete($user, $requestDTO);
            $parsed = $this->cleanAndParseJsonObject($response->content);

            if ($parsed && isset($parsed['content'])) {
                return [
                    'title' => $parsed['title'] ?? $topic,
                    'hook' => $parsed['hook'] ?? '',
                    'content' => $parsed['content'],
                    'hashtags' => $parsed['hashtags'] ?? ['#PHP', '#Laravel', '#SystemDesign', '#SoftwareEngineering'],
                    'image_prompt' => $parsed['image_prompt'] ?? "Modern isometric developer diagram of {$topic}, dark aesthetic, 4k",
                    'category' => $parsed['category'] ?? 'architecture',
                ];
            }
        } catch (Throwable $e) {
            // Fall back gracefully
        }

        return $this->getFallbackPostDraft($topic);
    }

    /**
     * Generate visual banner image.
     */
    public function generateImage(User $user, string $prompt, string $title): array
    {
        return $this->imageService->generateBanner($user, $prompt, $title);
    }

    /**
     * Save post (create or update).
     */
    public function savePost(User $user, array $data, ?int $postId = null): ContentPost
    {
        if ($postId) {
            $post = ContentPost::where('user_id', $user->id)->findOrFail($postId);
        } else {
            $post = new ContentPost();
            $post->user_id = $user->id;
        }

        $post->fill([
            'role_profile_id' => $data['role_profile_id'] ?? $post->role_profile_id,
            'title' => $data['title'] ?? ($post->title ?: 'Untitled Technical Insight'),
            'topic' => $data['topic'] ?? ($post->topic ?: 'System Architecture'),
            'category' => $data['category'] ?? ($post->category ?: 'architecture'),
            'content' => $data['content'] ?? $post->content,
            'image_prompt' => $data['image_prompt'] ?? $post->image_prompt,
            'image_path' => $data['image_path'] ?? $post->image_path,
            'image_provider' => $data['image_provider'] ?? $post->image_provider,
            'status' => $data['status'] ?? ($post->status ?: 'draft'),
            'scheduled_at' => ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : $post->scheduled_at,
        ]);

        $post->save();

        return $post;
    }

    /**
     * Schedule post for a future release date/time.
     */
    public function schedulePost(User $user, int $postId, string $scheduledAt): ContentPost
    {
        $post = ContentPost::where('user_id', $user->id)->findOrFail($postId);

        $post->update([
            'status' => 'scheduled',
            'scheduled_at' => Carbon::parse($scheduledAt),
        ]);

        return $post;
    }

    /**
     * Delete post.
     */
    public function deletePost(User $user, int $postId): bool
    {
        $post = ContentPost::where('user_id', $user->id)->find($postId);
        return $post ? (bool) $post->delete() : false;
    }

    /**
     * Get user posts filtered by status.
     */
    public function getPosts(User $user, ?string $status = null): Collection
    {
        $query = ContentPost::where('user_id', $user->id)->orderByDesc('created_at');

        if ($status && in_array($status, ['draft', 'scheduled', 'published', 'archived'])) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Get weekly/monthly scheduled calendar slots.
     */
    public function getCalendarSchedule(User $user): array
    {
        $scheduledPosts = ContentPost::where('user_id', $user->id)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->get();

        $slots = [];
        foreach ($scheduledPosts as $post) {
            $dateKey = $post->scheduled_at->format('Y-m-d');
            if (! isset($slots[$dateKey])) {
                $slots[$dateKey] = [];
            }
            $slots[$dateKey][] = [
                'id' => $post->id,
                'title' => $post->title,
                'time' => $post->scheduled_at->format('H:i'),
                'status' => $post->status,
                'category' => $post->category,
            ];
        }

        return $slots;
    }

    /**
     * Parse JSON array safely.
     */
    protected function cleanAndParseJsonArray(string $content): ?array
    {
        $content = trim($content);
        if (preg_match('/```(?:json)?\s*(\[.*?\])\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Parse JSON object safely.
     */
    protected function cleanAndParseJsonObject(string $content): ?array
    {
        $content = trim($content);
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Deterministic curated technical topics.
     */
    protected function getFallbackTopics(string $category): array
    {
        $all = [
            [
                'title' => 'Why MySQL Composite Indexes Fail on Range Queries',
                'category' => 'database',
                'hook' => 'Most developers create composite indexes on (status, created_at) and wonder why their query still scans 500,000 rows.',
            ],
            [
                'title' => 'PHP 8.4 Property Hooks vs Traditional Accessors',
                'category' => 'php_internals',
                'hook' => 'PHP 8.4 property hooks finally eliminate boilerplate getters and setters, but there is one architectural nuance you must know.',
            ],
            [
                'title' => 'Preventing Redis Cache Stampedes in High-Concurrency APIs',
                'category' => 'architecture',
                'hook' => 'When your hottest cache key expires at 2:00 PM during a traffic spike, your primary database will crash in 3 seconds.',
            ],
            [
                'title' => 'Designing Zero-Downtime Database Migrations in Laravel',
                'category' => 'devops',
                'hook' => 'Running ALTER TABLE on a 20-million row table will lock your production database. Here is the expand-and-contract pattern.',
            ],
            [
                'title' => 'When NOT to Use Microservices: A Monolith Scaling Guide',
                'category' => 'architecture',
                'hook' => 'Before you split your application into 14 microservices, ask yourself if you have solved your distributed transaction problem.',
            ],
            [
                'title' => 'Mastering Laravel Queue Workers: Memory Leaks & Supervisor',
                'category' => 'craftsmanship',
                'hook' => 'Why do Laravel queue workers slowly consume 2GB of RAM over 48 hours? It is usually static state retention.',
            ],
        ];

        if ($category !== 'all') {
            $filtered = array_filter($all, fn($item) => $item['category'] === $category);
            return ! empty($filtered) ? array_values($filtered) : $all;
        }

        return $all;
    }

    /**
     * Deterministic fallback post draft.
     */
    protected function getFallbackPostDraft(string $topic): array
    {
        $hook = "Most developers treat {$topic} as a simple implementation detail.\n\nIn high-throughput production systems, it is the difference between 99.99% uptime and a 3 AM incident response.";

        $content = <<<POST
{$hook}

Here is what happens behind the scenes:

When traffic scales to millions of requests, naive patterns break down under concurrency.

Key Engineering Takeaways:
• Understand your database query execution plans (EXPLAIN is your best friend).
• Decouple heavy computational jobs using background queues with backoff retries.
• Add telemetry metrics (p95 and p99 latency) before making premature optimizations.
• Use caching as an acceleration tier, not a band-aid for inefficient schema design.

What is your preferred pattern when scaling {$topic}? Have you encountered unexpected bottlenecks in production?

#PHP #Laravel #SystemDesign #SoftwareEngineering #DatabaseOptimization
POST;

        return [
            'title' => $topic,
            'hook' => $hook,
            'content' => $content,
            'hashtags' => ['#PHP', '#Laravel', '#SystemDesign', '#SoftwareEngineering', '#DatabaseOptimization'],
            'image_prompt' => "High-tech isometric software architecture diagram representing {$topic}, glowing data streams, dark slate background, modern developer aesthetic, 4k",
            'category' => 'architecture',
        ];
    }
}

