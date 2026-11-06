<?php

namespace App\Modules\Jobs\app\Services;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationEvent;
use App\Modules\Applications\app\Models\ApplicationRegistry;
use App\Modules\Applications\app\Services\DuplicateCheckerService;
use App\Modules\Applications\app\Services\FingerprintService;
use App\Modules\Email\app\Models\EmailTemplate;
use App\Modules\Jobs\app\Contracts\JobSourceInterface;
use App\Modules\Jobs\app\DTOs\JobVacancyDTO;
use App\Modules\Jobs\app\Models\Company;
use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\Jobs\app\Services\Adapters\AdzunaJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\ATSBoardJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\HimalayasJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\JoobleJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\RemoteOKJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\RemotiveJobAdapter;
use App\Modules\Resume\app\Services\CVAssignmentResolverService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobDiscoveryService
{
    /** @var array<JobSourceInterface> */
    protected array $adapters = [];

    public function __construct(
        protected FingerprintService $fingerprintService,
        protected DuplicateCheckerService $duplicateChecker,
        protected CVAssignmentResolverService $cvResolver,
        ?HimalayasJobAdapter $himalayas = null,
        ?RemoteOKJobAdapter $remoteOk = null,
        ?RemotiveJobAdapter $remotive = null,
        ?AdzunaJobAdapter $adzuna = null,
        ?JoobleJobAdapter $jooble = null,
        ?ATSBoardJobAdapter $atsBoard = null
    ) {
        $this->adapters = [
            $himalayas ?? app(HimalayasJobAdapter::class),
            $remoteOk ?? app(RemoteOKJobAdapter::class),
            $remotive ?? app(RemotiveJobAdapter::class),
            $adzuna ?? app(AdzunaJobAdapter::class),
            $jooble ?? app(JoobleJobAdapter::class),
            $atsBoard ?? app(ATSBoardJobAdapter::class),
        ];
    }

    /**
     * Get all registered job source adapters.
     *
     * @return array<JobSourceInterface>
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * Query all available adapters and ingest vacancies into the database.
     *
     * @return array{fetched: int, new_vacancies: int, duplicates: int, provider_stats: array}
     */
    public function fetchAndIngestAll(User $user, array $criteria = []): array
    {
        $totalFetched = 0;
        $newVacanciesCount = 0;
        $duplicateCount = 0;
        $providerStats = [];

        foreach ($this->adapters as $adapter) {
            $key = $adapter->getProviderKey();
            $items = $adapter->fetchJobs($criteria, $user);
            $totalFetched += $items->count();
            $providerNew = 0;

            foreach ($items as $dto) {
                $ingestResult = $this->ingestJobDTO($dto, $user);
                if ($ingestResult['is_new']) {
                    $newVacanciesCount++;
                    $providerNew++;
                } else {
                    $duplicateCount++;
                }
            }

            $providerStats[$key] = [
                'name' => $adapter->getProviderName(),
                'total' => $items->count(),
                'new' => $providerNew,
            ];
        }

        return [
            'fetched' => $totalFetched,
            'new_vacancies' => $newVacanciesCount,
            'duplicates' => $duplicateCount,
            'provider_stats' => $providerStats,
        ];
    }

    /**
     * Ingest a single JobVacancyDTO: resolves company, computes fingerprint, and creates records.
     *
     * @return array{vacancy: JobVacancy, is_new: bool}
     */
    public function ingestJobDTO(JobVacancyDTO $dto, ?User $user = null): array
    {
        $canonicalFingerprint = $this->fingerprintService->generate(
            companyName: $dto->company_name,
            jobTitle: $dto->job_title,
            location: $dto->location,
            externalReqId: $dto->external_job_id,
            canonicalUrl: $dto->apply_url
        );

        $normalizedCompany = $this->fingerprintService->normalizeCompanyName($dto->company_name);

        // Find or create Company record
        $company = Company::firstOrCreate(
            ['normalized_name' => $normalizedCompany],
            [
                'name' => $dto->company_name,
                'location' => $dto->location,
                'website' => !empty($dto->apply_url) ? parse_url($dto->apply_url, PHP_URL_SCHEME) . '://' . parse_url($dto->apply_url, PHP_URL_HOST) : null,
            ]
        );

        // Check if vacancy with fingerprint already exists
        $existing = JobVacancy::where('vacancy_fingerprint', $canonicalFingerprint)
            ->orWhere(function ($q) use ($company, $dto) {
                if (!empty($dto->external_job_id)) {
                    $q->where('company_id', $company->id)->where('external_job_id', $dto->external_job_id);
                }
            })
            ->first();

        if ($existing) {
            return ['vacancy' => $existing, 'is_new' => false];
        }

        // Create new JobVacancy record
        $vacancy = JobVacancy::create([
            'company_id' => $company->id,
            'company_name' => $dto->company_name,
            'job_title' => $dto->job_title,
            'location' => $dto->location,
            'work_mode' => $dto->work_mode,
            'employment_type' => $dto->employment_type,
            'salary_min' => $dto->salary_min,
            'salary_max' => $dto->salary_max,
            'currency' => $dto->currency ?? 'USD',
            'skills' => $dto->skills,
            'description' => $dto->description,
            'posted_date' => $dto->posted_date ?: date('Y-m-d'),
            'apply_method' => $dto->apply_method ?? 'Link',
            'apply_url' => $dto->apply_url,
            'source_url' => $dto->source_url,
            'source_provider' => $dto->source_provider,
            'external_job_id' => $dto->external_job_id,
            'hr_name' => $dto->hr_name,
            'hr_email' => $dto->hr_email,
            'vacancy_fingerprint' => $canonicalFingerprint,
            'status' => 'New',
        ]);

        // Auto-register in candidate ApplicationRegistry if user provided
        if ($user) {
            ApplicationRegistry::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'canonical_fingerprint' => $canonicalFingerprint,
                ],
                [
                    'company_name_normalized' => $normalizedCompany,
                    'job_title_normalized' => $this->fingerprintService->normalizeJobTitle($dto->job_title),
                    'location_normalized' => $this->fingerprintService->normalizeLocation($dto->location),
                    'external_requisition_id' => $dto->external_job_id,
                    'source_url_hash' => hash('sha256', $dto->apply_url),
                    'status' => 'Discovered',
                ]
            );
        }

        return ['vacancy' => $vacancy, 'is_new' => true];
    }

    /**
     * One-Click Stage for Outreach: binds optimal CV, detects duplicates, and stages an Application.
     *
     * @return array{application: Application, cv_recommendation: array, duplicate_status: array}
     */
    public function stageApplication(
        User $user,
        int $jobVacancyId,
        ?int $overrideResumeId = null,
        ?int $overrideTemplateId = null
    ): array {
        $vacancy = JobVacancy::with('company')->findOrFail($jobVacancyId);

        // 1. Duplicate check
        $dupResult = $this->duplicateChecker->check(
            userId: $user->id,
            companyName: $vacancy->company_name,
            jobTitle: $vacancy->job_title,
            location: $vacancy->location,
            externalReqId: $vacancy->external_job_id,
            applyUrl: $vacancy->apply_url
        );

        // 2. CV Assignment Resolver (Mode 1–4)
        $cvRecommendation = $this->cvResolver->resolveCV(
            user: $user,
            overrideResumeId: $overrideResumeId,
            vacancyContext: [
                'job_title' => $vacancy->job_title,
                'description' => $vacancy->description ?? '',
                'skills' => $vacancy->skills ?? [],
            ]
        );

        // 3. Application Registry binding
        $registry = ApplicationRegistry::firstOrCreate(
            [
                'user_id' => $user->id,
                'canonical_fingerprint' => $vacancy->vacancy_fingerprint,
            ],
            [
                'company_name_normalized' => $this->fingerprintService->normalizeCompanyName($vacancy->company_name),
                'job_title_normalized' => $this->fingerprintService->normalizeJobTitle($vacancy->job_title),
                'location_normalized' => $this->fingerprintService->normalizeLocation($vacancy->location),
                'external_requisition_id' => $vacancy->external_job_id,
                'source_url_hash' => hash('sha256', (string) $vacancy->apply_url),
                'status' => 'Staged',
            ]
        );
        $registry->update(['status' => 'Staged']);

        // 4. Default template resolution if not provided
        $templateId = $overrideTemplateId;
        if (!$templateId) {
            $defaultTemplate = EmailTemplate::where('user_id', $user->id)->where('is_default', true)->first();
            $templateId = $defaultTemplate?->id;
        }

        // 5. Create Application in Draft / Staged state
        $application = Application::create([
            'user_id' => $user->id,
            'registry_id' => $registry->id,
            'job_vacancy_id' => $vacancy->id,
            'role_profile_id' => $cvRecommendation['resume']?->role_profile_id,
            'resume_id' => $cvRecommendation['resume']?->id,
            'resume_version_id' => $cvRecommendation['version']?->id,
            'applied_via' => str_contains(strtolower($vacancy->source_provider), 'direct') ? 'Direct' : 'JobBoard',
            'discovered_from' => $vacancy->source_provider,
            'email_template_id' => $templateId,
            'application_status' => 'Draft',
            'email_delivery_status' => 'NotApplicable',
            'notes' => "Staged via Job Discovery API ({$vacancy->source_provider}). " . $cvRecommendation['reasoning'],
        ]);

        // 6. Record Lifecycle Audit Event
        ApplicationEvent::create([
            'application_id' => $application->id,
            'event_type' => 'APPLICATION_STAGED',
            'payload' => [
                'vacancy_id' => $vacancy->id,
                'provider' => $vacancy->source_provider,
                'cv_mode' => $cvRecommendation['mode'],
                'cv_title' => $cvRecommendation['resume']?->title,
                'match_score' => $cvRecommendation['match_score'],
            ],
        ]);

        $vacancy->update(['status' => 'Staged']);

        return [
            'application' => $application,
            'cv_recommendation' => $cvRecommendation,
            'duplicate_status' => $dupResult,
        ];
    }
}

