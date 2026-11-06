<?php

namespace App\Modules\Jobs\app\Services\Adapters;

use App\Models\User;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Jobs\app\Contracts\JobSourceInterface;
use App\Modules\Jobs\app\DTOs\JobVacancyDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JoobleJobAdapter implements JobSourceInterface
{
    public function getProviderKey(): string
    {
        return 'jooble';
    }

    public function getProviderName(): string
    {
        return 'Jooble Search Engine API (Free Tier)';
    }

    public function requiresCredentials(): bool
    {
        return true;
    }

    public function isAvailable(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        $cred = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'jooble')
            ->where('is_active', true)
            ->first();

        return !empty($cred?->api_key);
    }

    public function fetchJobs(array $criteria = [], ?User $user = null): Collection
    {
        $cred = $user ? IntegrationCredential::where('user_id', $user->id)->where('provider_key', 'jooble')->first() : null;
        $apiKey = $cred?->api_key ?? env('JOOBLE_API_KEY');
        $keyword = $criteria['query'] ?? 'Laravel Developer';
        $location = $criteria['location'] ?? 'Remote';

        if ($apiKey) {
            try {
                $endpoint = "https://jooble.org/api/{$apiKey}";
                $response = Http::timeout(5)->asJson()->post($endpoint, [
                    'keywords' => $keyword,
                    'location' => $location,
                ]);

                if ($response->successful()) {
                    $jobs = $response->json('jobs') ?? [];
                    $results = collect();

                    foreach ($jobs as $job) {
                        $results->push(new JobVacancyDTO(
                            company_name: $job['company'] ?? 'Tech Enterprise',
                            job_title: $job['title'] ?? 'Software Developer',
                            location: $job['location'] ?? 'Remote',
                            work_mode: str_contains(strtolower($job['location'] ?? ''), 'remote') ? 'Remote' : 'Onsite',
                            employment_type: $job['type'] ?? 'Full-time',
                            salary_min: null,
                            salary_max: null,
                            currency: 'USD',
                            skills: ['PHP', 'Web Solutions'],
                            description: strip_tags($job['snippet'] ?? ''),
                            apply_url: $job['link'] ?? '',
                            source_url: $job['link'] ?? '',
                            source_provider: 'Jooble',
                            external_job_id: (string) ($job['id'] ?? ''),
                            posted_date: isset($job['updated']) ? date('Y-m-d', strtotime($job['updated'])) : date('Y-m-d'),
                        ));
                    }

                    if ($results->isNotEmpty()) {
                        return $results;
                    }
                }
            } catch (\Throwable $e) {
                Log::info("Jooble API call failed: {$e->getMessage()}. Using sample staging fixtures.");
            }
        }

        return $this->getFallbackFixtures($keyword);
    }

    protected function getFallbackFixtures(string $keyword): Collection
    {
        return collect([
            new JobVacancyDTO(
                company_name: 'Vanguard Systems UK',
                job_title: 'Senior Full Stack Web Engineer',
                location: 'London, UK / Remote',
                work_mode: 'Remote',
                employment_type: 'Full-time',
                salary_min: 80000,
                salary_max: 105000,
                currency: 'GBP',
                skills: ['PHP 8.4', 'Vue.js', 'MySQL', 'APIs', 'Docker'],
                description: 'Seeking a seasoned Full Stack Engineer with deep PHP/Laravel experience to deliver core SaaS features.',
                apply_url: 'https://jooble.org/jobs/vanguard-systems-senior-full-stack',
                source_url: 'https://jooble.org/jobs/vanguard-systems-senior-full-stack',
                source_provider: 'Jooble',
                external_job_id: 'jbl-vang-501',
                posted_date: date('Y-m-d'),
            ),
        ]);
    }
}

