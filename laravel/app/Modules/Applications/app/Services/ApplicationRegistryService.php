<?php

namespace App\Modules\Applications\app\Services;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationEvent;
use App\Modules\Applications\app\Models\ApplicationIdentityKey;
use App\Modules\Applications\app\Models\ApplicationRegistry;
use App\Modules\Jobs\app\Models\JobVacancy;
use Illuminate\Support\Facades\DB;

class ApplicationRegistryService
{
    public function __construct(
        protected FingerprintService $fingerprintService,
        protected DuplicateCheckerService $duplicateChecker
    ) {}

    /**
     * Register a new job application with canonical deduplication fingerprinting.
     */
    public function stageApplication(
        User $user,
        array $vacancyData,
        ?int $roleProfileId = null,
        string $appliedVia = 'Direct',
        string $discoveredFrom = 'Manual',
        ?string $notes = null
    ): Application {
        return DB::transaction(function () use ($user, $vacancyData, $roleProfileId, $appliedVia, $discoveredFrom, $notes) {
            $companyName = $vacancyData['company_name'];
            $jobTitle = $vacancyData['job_title'];
            $location = $vacancyData['location'] ?? null;
            $externalReqId = $vacancyData['external_job_id'] ?? null;
            $applyUrl = $vacancyData['apply_url'] ?? ($vacancyData['source_url'] ?? null);

            $fingerprint = $this->fingerprintService->generate(
                $companyName,
                $jobTitle,
                $location,
                $externalReqId,
                $applyUrl
            );

            // 1. Create or retrieve canonical application_registry entry
            $registry = ApplicationRegistry::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'canonical_fingerprint' => $fingerprint,
                ],
                [
                    'company_name_normalized' => $this->fingerprintService->normalizeCompanyName($companyName),
                    'job_title_normalized' => $this->fingerprintService->normalizeJobTitle($jobTitle),
                    'location_normalized' => $this->fingerprintService->normalizeLocation($location),
                    'external_requisition_id' => $externalReqId,
                    'source_url_hash' => $applyUrl ? hash('sha256', $this->fingerprintService->normalizeUrl($applyUrl)) : null,
                    'first_discovered_at' => now(),
                    'status' => 'Staged',
                ]
            );

            // 2. Attach identity keys for cross-platform indexing
            ApplicationIdentityKey::firstOrCreate([
                'registry_id' => $registry->id,
                'key_type' => 'SHA256_FINGERPRINT',
                'key_value' => $fingerprint,
            ]);

            if (!empty($externalReqId)) {
                ApplicationIdentityKey::firstOrCreate([
                    'registry_id' => $registry->id,
                    'key_type' => 'REQ_ID',
                    'key_value' => $externalReqId,
                ]);
            }

            if (!empty($applyUrl)) {
                ApplicationIdentityKey::firstOrCreate([
                    'registry_id' => $registry->id,
                    'key_type' => 'APPLY_URL',
                    'key_value' => $this->fingerprintService->normalizeUrl($applyUrl),
                ]);
            }

            // 3. Create or link JobVacancy model if not already linked
            $vacancy = null;
            if (!empty($vacancyData['id'])) {
                $vacancy = JobVacancy::find($vacancyData['id']);
            } else {
                $vacancy = JobVacancy::create([
                    'company_name' => $companyName,
                    'job_title' => $jobTitle,
                    'location' => $location,
                    'work_mode' => $vacancyData['work_mode'] ?? 'Remote',
                    'employment_type' => $vacancyData['employment_type'] ?? 'Full-time',
                    'salary_min' => $vacancyData['salary_min'] ?? null,
                    'salary_max' => $vacancyData['salary_max'] ?? null,
                    'currency' => $vacancyData['currency'] ?? 'USD',
                    'skills' => $vacancyData['skills'] ?? [],
                    'description' => $vacancyData['description'] ?? null,
                    'apply_method' => $appliedVia,
                    'apply_url' => $applyUrl,
                    'source_provider' => $discoveredFrom,
                    'external_job_id' => $externalReqId,
                    'hr_email' => $vacancyData['hr_email'] ?? null,
                    'vacancy_fingerprint' => $fingerprint,
                    'status' => 'Staged',
                ]);
            }

            // 4. Create the Application record
            $application = Application::create([
                'user_id' => $user->id,
                'registry_id' => $registry->id,
                'job_vacancy_id' => $vacancy?->id,
                'role_profile_id' => $roleProfileId,
                'applied_via' => $appliedVia,
                'discovered_from' => $discoveredFrom,
                'application_status' => 'Staged',
                'notes' => $notes,
            ]);

            // 5. Log audit lifecycle event
            ApplicationEvent::create([
                'application_id' => $application->id,
                'event_type' => 'STAGED',
                'event_payload' => [
                    'canonical_fingerprint' => $fingerprint,
                    'company' => $companyName,
                    'title' => $jobTitle,
                    'via' => $appliedVia,
                    'staged_by' => $user->email,
                ],
            ]);

            return $application;
        });
    }

    /**
     * Transition application lifecycle status with audit event.
     */
    public function updateStatus(Application $application, string $newStatus, ?string $note = null): Application
    {
        $oldStatus = $application->application_status;

        $application->update([
            'application_status' => $newStatus,
            'sent_at' => in_array($newStatus, ['Sent', 'Replied', 'Interview', 'Offer']) && !$application->sent_at ? now() : $application->sent_at,
        ]);

        if (in_array($newStatus, ['Sent', 'Replied', 'Interview', 'Offer'])) {
            $application->registry->update([
                'status' => 'Applied',
                'last_applied_at' => $application->sent_at ?? now(),
            ]);
        }

        ApplicationEvent::create([
            'application_id' => $application->id,
            'event_type' => 'STATUS_CHANGE',
            'event_payload' => [
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $note,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        return $application;
    }
}

