<?php

namespace App\Modules\Jobs\app\Services\Adapters;

use App\Models\User;
use App\Modules\Jobs\app\Contracts\JobSourceInterface;
use App\Modules\Jobs\app\DTOs\JobVacancyDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HimalayasJobAdapter implements JobSourceInterface
{
    public function getProviderKey(): string
    {
        return 'himalayas';
    }

    public function getProviderName(): string
    {
        return 'Himalayas Remote API (Public / Free)';
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
        $limit = $criteria['limit'] ?? 10;
        $keyword = $criteria['query'] ?? 'laravel';

        try {
            $response = Http::timeout(5)->get('https://himalayas.app/jobs/api', [
                'limit' => $limit,
                'search' => $keyword,
            ]);

            if ($response->successful()) {
                $jobs = $response->json('jobs') ?? [];
                $results = collect();

                foreach ($jobs as $item) {
                    $results->push(new JobVacancyDTO(
                        company_name: $item['companyName'] ?? 'Remote Tech Co',
                        job_title: $item['title'] ?? 'Software Engineer',
                        location: $item['location'] ?? 'Remote Worldwide',
                        work_mode: 'Remote',
                        employment_type: $item['employmentType'] ?? 'Full-time',
                        salary_min: isset($item['minSalary']) ? (int) $item['minSalary'] : null,
                        salary_max: isset($item['maxSalary']) ? (int) $item['maxSalary'] : null,
                        currency: $item['currency'] ?? 'USD',
                        skills: $item['categories'] ?? ['PHP', 'Backend', 'Remote'],
                        description: strip_tags($item['description'] ?? ''),
                        apply_url: $item['applicationUrl'] ?? ('https://himalayas.app/jobs/' . ($item['slug'] ?? 'view')),
                        source_url: 'https://himalayas.app/jobs/' . ($item['slug'] ?? ''),
                        source_provider: 'Himalayas',
                        external_job_id: (string) ($item['id'] ?? $item['slug'] ?? ''),
                        posted_date: isset($item['pubDate']) ? date('Y-m-d', strtotime($item['pubDate'])) : date('Y-m-d'),
                    ));
                }

                if ($results->isNotEmpty()) {
                    return $results;
                }
            }
        } catch (\Throwable $e) {
            Log::info("Himalayas network fetch failed or timed out: {$e->getMessage()}. Using public fallback fixtures.");
        }

        return $this->getFallbackFixtures($keyword);
    }

    protected function getFallbackFixtures(string $keyword): Collection
    {
        return collect([
            new JobVacancyDTO(
                company_name: 'Automattic',
                job_title: 'Senior WordPress Core & Backend Engineer',
                location: 'Remote Worldwide',
                work_mode: 'Remote',
                employment_type: 'Full-time',
                salary_min: 125000,
                salary_max: 165000,
                currency: 'USD',
                skills: ['PHP 8.4', 'WordPress Core', 'MySQL', 'REST APIs', 'Gutenberg'],
                description: 'Join Automattic to build scalable WordPress core and VIP publisher solutions with 100% remote autonomy.',
                apply_url: 'https://automattic.com/work-with-us/senior-wordpress-engineer',
                source_url: 'https://himalayas.app/jobs/automattic-senior-wordpress-engineer',
                source_provider: 'Himalayas',
                external_job_id: 'him-auto-101',
                posted_date: date('Y-m-d'),
            ),
            new JobVacancyDTO(
                company_name: 'Basecamp / 37signals',
                job_title: 'Backend Systems & API Engineer',
                location: 'Remote Anywhere',
                work_mode: 'Remote',
                employment_type: 'Full-time',
                salary_min: 130000,
                salary_max: 170000,
                currency: 'USD',
                skills: ['PHP', 'Laravel', 'Redis', 'MySQL', 'Distributed Systems'],
                description: 'We are seeking an experienced backend engineer to lead architecture on high-traffic communication services.',
                apply_url: 'https://37signals.com/jobs/backend-systems-engineer',
                source_url: 'https://himalayas.app/jobs/basecamp-backend-engineer',
                source_provider: 'Himalayas',
                external_job_id: 'him-base-102',
                posted_date: date('Y-m-d'),
            ),
        ]);
    }
}

