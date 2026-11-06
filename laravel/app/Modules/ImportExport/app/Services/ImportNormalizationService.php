<?php

namespace App\Modules\ImportExport\app\Services;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationEvent;
use App\Modules\Applications\app\Models\ApplicationRegistry;
use App\Modules\Applications\app\Services\DuplicateCheckerService;
use App\Modules\Applications\app\Services\FingerprintService;
use App\Modules\ImportExport\app\Models\ImportBatch;
use App\Modules\Jobs\app\Models\Company;
use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\Resume\app\Services\CVAssignmentResolverService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ImportNormalizationService
{
    public function __construct(
        protected FingerprintService $fingerprintService,
        protected DuplicateCheckerService $duplicateChecker,
        protected CVAssignmentResolverService $cvResolver
    ) {}

    /**
     * Ingest and normalize raw rows into the JobPilot AI registry.
     *
     * @param array<array<string, string>> $rows
     * @param array<string, string> $mapping Header -> Canonical key
     * @return array{batch: ImportBatch, imported: int, duplicates: int, failed: int}
     */
    public function importRows(
        User $user,
        array $rows,
        array $mapping,
        string $filename = 'spreadsheet_import.csv',
        string $fileType = 'CSV'
    ): array {
        $batch = ImportBatch::create([
            'user_id' => $user->id,
            'filename' => $filename,
            'file_type' => $fileType,
            'total_rows' => count($rows),
            'column_mapping' => $mapping,
            'status' => 'Processing',
        ]);

        $imported = 0;
        $duplicates = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $rawRow) {
            try {
                // Map raw row fields to canonical keys
                $canonical = [];
                foreach ($mapping as $origHeader => $canonicalKey) {
                    if (isset($rawRow[$origHeader])) {
                        $canonical[$canonicalKey] = trim($rawRow[$origHeader]);
                    }
                }

                $companyName = $canonical['company_name'] ?? null;
                $jobTitle = $canonical['job_title'] ?? null;

                if (empty($companyName) || empty($jobTitle)) {
                    $failed++;
                    $errors[] = "Row #" . ($rowIndex + 1) . ": Missing required Company Name or Job Title.";
                    continue;
                }

                $location = !empty($canonical['location']) ? $canonical['location'] : 'Remote';
                $applyUrl = !empty($canonical['apply_url']) ? $canonical['apply_url'] : null;
                $notes = $canonical['notes'] ?? null;
                $status = $this->normalizeStatus($canonical['status'] ?? 'Applied');
                $appliedDate = $this->normalizeDate($canonical['applied_date'] ?? null);

                // 1. Generate canonical fingerprint
                $fingerprint = $this->fingerprintService->generate(
                    companyName: $companyName,
                    jobTitle: $jobTitle,
                    location: $location,
                    canonicalUrl: $applyUrl
                );

                $normalizedCompany = $this->fingerprintService->normalizeCompanyName($companyName);

                // 2. Check if application already exists in user's registry
                $existingRegistry = ApplicationRegistry::where('user_id', $user->id)
                    ->where('canonical_fingerprint', $fingerprint)
                    ->first();

                if ($existingRegistry) {
                    $duplicates++;
                    continue;
                }

                // 3. Find or create Company
                $company = Company::firstOrCreate(
                    ['normalized_name' => $normalizedCompany],
                    [
                        'name' => $companyName,
                        'location' => $location,
                    ]
                );

                // 4. Find or create JobVacancy
                $vacancy = JobVacancy::firstOrCreate(
                    ['vacancy_fingerprint' => $fingerprint],
                    [
                        'company_id' => $company->id,
                        'company_name' => $companyName,
                        'job_title' => $jobTitle,
                        'location' => $location,
                        'work_mode' => str_contains(strtolower($location), 'remote') ? 'Remote' : 'Onsite',
                        'apply_url' => $applyUrl ?: '',
                        'source_provider' => "Import ({$fileType})",
                        'status' => $status === 'Draft' ? 'New' : 'Applied',
                    ]
                );

                // 5. Create ApplicationRegistry
                $registry = ApplicationRegistry::create([
                    'user_id' => $user->id,
                    'canonical_fingerprint' => $fingerprint,
                    'company_name_normalized' => $normalizedCompany,
                    'job_title_normalized' => $this->fingerprintService->normalizeJobTitle($jobTitle),
                    'location_normalized' => $this->fingerprintService->normalizeLocation($location),
                    'source_url_hash' => $applyUrl ? hash('sha256', $applyUrl) : null,
                    'first_discovered_at' => $appliedDate,
                    'last_applied_at' => $status !== 'Draft' ? $appliedDate : null,
                    'status' => $status === 'Draft' ? 'Staged' : 'Applied',
                ]);

                // 6. Resolve CV match for this vacancy
                $cvMatch = $this->cvResolver->resolveCV(
                    user: $user,
                    vacancyContext: [
                        'job_title' => $jobTitle,
                        'description' => $notes ?? '',
                    ]
                );

                // 7. Create Application record
                $application = Application::create([
                    'user_id' => $user->id,
                    'registry_id' => $registry->id,
                    'job_vacancy_id' => $vacancy->id,
                    'role_profile_id' => $cvMatch['resume']?->role_profile_id,
                    'resume_id' => $cvMatch['resume']?->id,
                    'resume_version_id' => $cvMatch['version']?->id,
                    'applied_via' => 'SpreadsheetImport',
                    'discovered_from' => $fileType,
                    'application_status' => $status,
                    'email_delivery_status' => 'NotApplicable',
                    'notes' => $notes ?: "Imported from {$filename} ({$fileType}).",
                    'sent_at' => $status !== 'Draft' ? $appliedDate : null,
                ]);

                // 8. Record audit event
                ApplicationEvent::create([
                    'application_id' => $application->id,
                    'event_type' => 'APPLICATION_IMPORTED',
                    'payload' => [
                        'batch_id' => $batch->id,
                        'filename' => $filename,
                        'original_status' => $canonical['status'] ?? 'Applied',
                        'assigned_cv' => $cvMatch['resume']?->title,
                    ],
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Row #" . ($rowIndex + 1) . ": " . $e->getMessage();
            }
        }

        $batch->update([
            'imported_count' => $imported,
            'duplicates_count' => $duplicates,
            'failed_count' => $failed,
            'status' => 'Completed',
            'error_summary' => !empty($errors) ? implode("\n", array_slice($errors, 0, 10)) : null,
        ]);

        return [
            'batch' => $batch,
            'imported' => $imported,
            'duplicates' => $duplicates,
            'failed' => $failed,
        ];
    }

    /**
     * Map fuzzy status string to canonical JobPilot status.
     */
    public function normalizeStatus(string $status): string
    {
        $s = strtolower(trim($status));

        return match (true) {
            str_contains($s, 'interview') || str_contains($s, 'screen') || str_contains($s, 'technical') => 'Interview',
            str_contains($s, 'offer') || str_contains($s, 'accepted') || str_contains($s, 'hired') => 'Offer',
            str_contains($s, 'reject') || str_contains($s, 'decline') || str_contains($s, 'closed') || str_contains($s, 'pass') => 'Rejected',
            str_contains($s, 'draft') || str_contains($s, 'stage') || str_contains($s, 'plan') => 'Draft',
            default => 'Applied',
        };
    }

    /**
     * Parse and normalize date strings.
     */
    public function normalizeDate(?string $dateStr): Carbon
    {
        if (empty($dateStr)) {
            return now();
        }

        try {
            return Carbon::parse($dateStr);
        } catch (\Throwable) {
            return now();
        }
    }
}

