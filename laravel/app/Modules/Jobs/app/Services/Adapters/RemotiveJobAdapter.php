<?php

namespace App\Modules\Jobs\app\Services\Adapters;

use App\Models\User;
use App\Modules\Jobs\app\Contracts\JobSourceInterface;
use App\Modules\Jobs\app\DTOs\JobVacancyDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RemotiveJobAdapter implements JobSourceInterface
{
    public function getProviderKey(): string
    {
        return 'remotive';
    }

    public function getProviderName(): string
    {
        return 'Remotive Remote Hub (Public / Free)';
    }

    public function requiresCredentials(): bool
    {
        return false;
    }

    public function isAvailable(?User $user = null): bool
    {
        return true;
    }

    public function fetchJobs(array $criteria = [], ?User $user = null): Collection
    {
        $keyword = $criteria['query'] ?? 'php';

        try {
            $response = Http::timeout(5)->get('https://remotive.com/api/remote-jobs', [
                'category' => 'software-dev',
                'search' => $keyword,
                'limit' => 15,
            ]);

            if ($response->successful()) {
                $jobs = $response->json('jobs') ?? [];
                $results = collect();

                foreach (array_slice($jobs, 0, 15) as $item) {
                    $results->push(new JobVacancyDTO(
                        company_name: $item['company_name'] ?? 'Tech Enterprise',
                        job_title: $item['title'] ?? 'Full Stack Developer',
                        location: $item['candidate_required_location'] ?? 'Remote Worldwide',
                        work_mode: 'Remote',
                        employment_type: $item['job_type'] ?? 'Full-time',
                        salary_min: null,
                        salary_max: null,
                        currency: 'USD',
                        skills: $item['tags'] ?? ['PHP', 'Web', 'Remote'],
                        description: strip_tags($item['description'] ?? ''),
                        apply_url: $item['url'] ?? 'https://remotive.com',
                        source_url: $item['url'] ?? 'https://remotive.com',
                        source_provider: 'Remotive',
                        external_job_id: (string) ($item['id'] ?? ''),
                        posted_date: isset($item['publication_date']) ? date('Y-m-d', strtotime($item['publication_date'])) : date('Y-m-d'),
                    ));
                }

                if ($results->isNotEmpty()) {
                    return $results;
                }
            }
        } catch (\Throwable $e) {
            Log::info("Remotive network fetch failed or timed out: {$e->getMessage()}. Using fallback fixtures.");
        }

        return $this->getFallbackFixtures($keyword);
    }

    protected function getFallbackFixtures(string $keyword): Collection
    {
        return collect([
            new JobVacancyDTO(
                company_name: 'Buffer Inc.',
                job_title: 'Full Stack Web Solutions Engineer',
                location: 'Remote Worldwide',
                work_mode: 'Remote',
                employment_type: 'Full-time',
                salary_min: 110000,
                salary_max: 145000,
                currency: 'USD',
                skills: ['PHP', 'Vue.js', 'MySQL', 'Docker', 'REST APIs'],
                description: 'Join our customer happiness team to maintain integrations, internal tooling, and social publishing APIs.',
                apply_url: 'https://buffer.com/journey/full-stack-web-solutions-engineer',
                source_url: 'https://remotive.com/jobs/buffer-full-stack-engineer',
                source_provider: 'Remotive',
                external_job_id: 'rem-buf-301',
                posted_date: date('Y-m-d'),
            ),
        ]);
    }
}

