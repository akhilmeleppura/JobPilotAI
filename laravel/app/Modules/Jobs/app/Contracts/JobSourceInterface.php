<?php

namespace App\Modules\Jobs\app\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface JobSourceInterface
{
    /**
     * Unique key identifier for the provider (e.g. 'himalayas', 'remote_ok', 'adzuna').
     */
    public function getProviderKey(): string;

    /**
     * Human-readable name.
     */
    public function getProviderName(): string;

    /**
     * Whether this provider requires API credentials.
     */
    public function requiresCredentials(): bool;

    /**
     * Check if configured and ready for queries.
     */
    public function isAvailable(?User $user = null): bool;

    /**
     * Query provider for job openings and return normalized DTOs.
     *
     * @param array<string, mixed> $criteria Query options (keywords, location, limit)
     * @return Collection<int, \App\Modules\Jobs\app\DTOs\JobVacancyDTO>
     */
    public function fetchJobs(array $criteria = [], ?User $user = null): Collection;
}

