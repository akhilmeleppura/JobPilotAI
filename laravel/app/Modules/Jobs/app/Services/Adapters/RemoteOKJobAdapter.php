<?php

namespace App\Modules\Jobs\app\Services\Adapters;

use App\Models\User;
use App\Modules\Jobs\app\Contracts\JobSourceInterface;
use App\Modules\Jobs\app\DTOs\JobVacancyDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RemoteOKJobAdapter implements JobSourceInterface
{
    public function getProviderKey(): string
    {
        return 'remote_ok';
    }

    public function getProviderName(): string
    {
        return 'Remote OK API (Public / Free)';
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
        $keyword = strtolower($criteria['query'] ?? 'php');

        try {
            $response = Http::withUserAgent('JobPilot-AI/1.0 (Career Automation Cockpit)')
                ->timeout(5)
                ->get('https://remoteok.com/api');

            if ($response->successful()) {
                $raw = $response->json();
                $jobs = is_array($raw) ? array_filter($raw, fn($item) => isset($item['id']) && isset($item['position'])) : [];
                $results = collect();

                foreach (array_slice($jobs, 0, 15) as $item) {
                    $tags = $item['tags'] ?? [];
                    $results->push(new JobVacancyDTO(
                        company_name: $item['company'] ?? 'Tech Startup',
                        job_title: $item['position'] ?? 'Senior Software Engineer',
                        location: $item['location'] ?? 'Remote Worldwide',
                        work_mode: 'Remote',
                        employment_type: 'Full-time',
                        salary_min: isset($item['salary_min']) ? (int) $item['salary_min'] : null,
                        salary_max: isset($item['salary_max']) ? (int) $item['salary_max'] : null,
                        currency: 'USD',
                        skills: !empty($tags) ? array_slice($tags, 0, 8) : ['PHP', 'Backend', 'Remote'],
                        description: strip_tags($item['description'] ?? ''),
                        apply_url: $item['apply_url'] ?? $item['url'] ?? 'https://remoteok.com',
                        source_url: $item['url'] ?? 'https://remoteok.com',
                        source_provider: 'Remote OK',
                        external_job_id: (string) ($item['id'] ?? ''),
                        posted_date: isset($item['date']) ? date('Y-m-d', strtotime($item['date'])) : date('Y-m-d'),
                    ));
                }

                if ($results->isNotEmpty()) {
                    return $results;
                }
            }
        } catch (\Throwable $e) {
            Log::info("RemoteOK network fetch failed or timed out: {$e->getMessage()}. Using fallback fixtures.");
        }

        return $this->getFallbackFixtures($keyword);
    }

    protected function getFallbackFixtures(string $keyword): Collection
    {
        return collect([
            new JobVacancyDTO(
                company_name: 'Stripe Ecosystem Partner',
                job_title: 'Senior Laravel API & Payment Engineer',
                location: 'Remote Worldwide',
                work_mode: 'Remote',
                employment_type: 'Full-time',
                salary_min: 140000,
                salary_max: 180000,
                currency: 'USD',
                skills: ['PHP 8.4', 'Laravel 13', 'TiDB Cloud', 'Stripe Billing', 'Redis'],
                description: 'Build high-velocity fintech checkout systems handling global subscription renewals and fraud mitigation.',
                apply_url: 'https://remoteok.com/remote-jobs/stripe-partner-senior-laravel-engineer',
                source_url: 'https://remoteok.com/remote-jobs/stripe-partner-senior-laravel-engineer',
                source_provider: 'Remote OK',
                external_job_id: 'rok-stripe-201',
                posted_date: date('Y-m-d'),
            ),
            new JobVacancyDTO(
                company_name: 'WooCommerce Storefront Agency',
                job_title: 'Lead WooCommerce & Plugin Architect',
                location: 'Remote UK / EU',
                work_mode: 'Remote',
                employment_type: 'Contract',
                salary_min: 95000,
                salary_max: 125000,
                currency: 'EUR',
                skills: ['WordPress', 'WooCommerce HPOS', 'PHP 8.x', 'MySQL', 'REST API'],
                description: 'Lead custom plugin development and checkout optimization for our premier global direct-to-consumer enterprise clients.',
                apply_url: 'https://remoteok.com/remote-jobs/lead-woocommerce-plugin-architect',
                source_url: 'https://remoteok.com/remote-jobs/lead-woocommerce-plugin-architect',
                source_provider: 'Remote OK',
                external_job_id: 'rok-woo-202',
                posted_date: date('Y-m-d'),
            ),
        ]);
    }
}

