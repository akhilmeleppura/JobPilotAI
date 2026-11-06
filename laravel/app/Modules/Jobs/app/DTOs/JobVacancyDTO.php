<?php

namespace App\Modules\Jobs\app\DTOs;

use App\Shared\DTOs\BaseDTO;

class JobVacancyDTO extends BaseDTO
{
    public function __construct(
        public string $company_name,
        public string $job_title,
        public string $location = 'Remote',
        public string $work_mode = 'Remote', // Remote, Hybrid, Onsite
        public string $employment_type = 'Full-time', // Full-time, Contract, Part-time
        public ?int $salary_min = null,
        public ?int $salary_max = null,
        public ?string $currency = 'USD',
        public array $skills = [],
        public ?string $description = null,
        public string $apply_url = '',
        public ?string $source_url = null,
        public string $source_provider = 'Direct',
        public ?string $external_job_id = null,
        public ?string $posted_date = null,
        public ?string $hr_name = null,
        public ?string $hr_email = null,
        public ?string $apply_method = 'Link', // Link, Email, API
    ) {}

    public function toArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'job_title' => $this->job_title,
            'location' => $this->location,
            'work_mode' => $this->work_mode,
            'employment_type' => $this->employment_type,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency' => $this->currency,
            'skills' => $this->skills,
            'description' => $this->description,
            'apply_url' => $this->apply_url,
            'source_url' => $this->source_url ?: $this->apply_url,
            'source_provider' => $this->source_provider,
            'external_job_id' => $this->external_job_id,
            'posted_date' => $this->posted_date ?: date('Y-m-d'),
            'hr_name' => $this->hr_name,
            'hr_email' => $this->hr_email,
            'apply_method' => $this->apply_method,
        ];
    }
}

