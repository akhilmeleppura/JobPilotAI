<?php

namespace App\Modules\Jobs\app\Models;

use App\Shared\Contracts\FingerprintableInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobVacancy extends Model implements FingerprintableInterface
{
    use HasFactory;

    protected $table = 'job_vacancies';

    protected $fillable = [
        'company_id',
        'company_name',
        'job_title',
        'location',
        'work_mode',
        'employment_type',
        'experience_min',
        'experience_max',
        'salary_min',
        'salary_max',
        'currency',
        'skills',
        'description',
        'posted_date',
        'application_deadline',
        'apply_method',
        'apply_url',
        'source_url',
        'source_provider',
        'external_job_id',
        'hr_name',
        'hr_email',
        'hr_email_source_url',
        'vacancy_fingerprint',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'experience_min' => 'float',
            'experience_max' => 'float',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'posted_date' => 'date',
            'application_deadline' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getFingerprintComponents(): array
    {
        return [
            'company' => $this->company_name,
            'title' => $this->job_title,
            'location' => $this->location,
            'external_id' => $this->external_job_id,
            'apply_url' => $this->apply_url ?: $this->source_url,
        ];
    }

    public function generateFingerprint(): string
    {
        $comp = $this->getFingerprintComponents();
        $normalizedCompany = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '', (string) $comp['company'])));
        $normalizedTitle = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '', (string) $comp['title'])));
        $normalizedLocation = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '', (string) $comp['location'])));
        $key = !empty($comp['external_id']) ? $comp['external_id'] : preg_replace('/\?.*/', '', (string) $comp['apply_url']);

        return hash('sha256', "{$normalizedCompany}::{$normalizedTitle}::{$normalizedLocation}::{$key}");
    }

    protected static function booted(): void
    {
        static::creating(function ($vacancy) {
            if (empty($vacancy->vacancy_fingerprint)) {
                $vacancy->vacancy_fingerprint = $vacancy->generateFingerprint();
            }
        });
    }
}

