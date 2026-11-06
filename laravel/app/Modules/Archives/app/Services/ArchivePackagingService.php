<?php

namespace App\Modules\Archives\app\Services;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Archives\app\Models\ApplicationArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ArchivePackagingService
{
    /**
     * Compile and compress monthly applications archive (JSONL.gz + CSV + SHA-256 Checksums).
     */
    public function generateMonthlyArchive(User $user, int $year, int $month): ApplicationArchive
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Fetch all applications and events for this month
        $applications = Application::with(['vacancy', 'roleProfile', 'registry.identityKeys', 'events'])
            ->where('user_id', $user->id)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->orWhereBetween('sent_at', [$startDate, $endDate]);
            })
            ->orderBy('created_at')
            ->get();

        $monthPadded = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        $relativeDir = "archives/{$year}/{$monthPadded}";
        $fullDir = storage_path("app/{$relativeDir}");

        if (!File::isDirectory($fullDir)) {
            File::makeDirectory($fullDir, 0755, true);
        }

        $jsonlFileName = "Application_Events_{$year}_{$monthPadded}.jsonl.gz";
        $csvFileName = "Applications_{$year}_{$monthPadded}.csv";
        $checksumFileName = "Monthly_Archive_Checksums.sha256";

        // 2. Build JSONL lines
        $jsonlLines = [];
        foreach ($applications as $app) {
            $record = [
                'application_id' => $app->id,
                'canonical_fingerprint' => $app->registry?->canonical_fingerprint,
                'company_name' => $app->vacancy?->company_name,
                'job_title' => $app->vacancy?->job_title,
                'location' => $app->vacancy?->location,
                'work_mode' => $app->vacancy?->work_mode,
                'role_profile' => $app->roleProfile?->name,
                'applied_via' => $app->applied_via,
                'discovered_from' => $app->discovered_from,
                'application_status' => $app->application_status,
                'notes' => $app->notes,
                'sent_at' => $app->sent_at?->toIso8601String(),
                'created_at' => $app->created_at->toIso8601String(),
                'vacancy' => [
                    'salary_min' => $app->vacancy?->salary_min,
                    'salary_max' => $app->vacancy?->salary_max,
                    'currency' => $app->vacancy?->currency,
                    'apply_url' => $app->vacancy?->apply_url,
                    'hr_email' => $app->vacancy?->hr_email,
                    'description_excerpt' => substr((string) ($app->vacancy?->description ?? ''), 0, 500),
                ],
                'identity_keys' => $app->registry?->identityKeys->map(fn($k) => [
                    'key_type' => $k->key_type,
                    'key_value' => $k->key_value,
                ])->toArray(),
                'events' => $app->events->map(fn($e) => [
                    'event_type' => $e->event_type,
                    'payload' => $e->event_payload,
                    'occurred_at' => $e->created_at->toIso8601String(),
                ])->toArray(),
            ];

            $jsonlLines[] = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $rawJsonl = implode("\n", $jsonlLines);
        $compressedJsonl = gzencode($rawJsonl, 9);
        $jsonlPath = "{$fullDir}/{$jsonlFileName}";
        File::put($jsonlPath, $compressedJsonl);
        $jsonlChecksum = hash_file('sha256', $jsonlPath);
        $fileSize = filesize($jsonlPath);

        // 3. Build CSV spreadsheet
        $csvPath = "{$fullDir}/{$csvFileName}";
        $csvHandle = fopen($csvPath, 'w');
        fputcsv($csvHandle, [
            'ID',
            'Company',
            'Job Title',
            'Location',
            'Work Mode',
            'Role Profile',
            'Applied Via',
            'Discovered From',
            'Status',
            'Created Date',
            'Sent Date',
            'Canonical Fingerprint',
        ]);

        foreach ($applications as $app) {
            fputcsv($csvHandle, [
                $app->id,
                $app->vacancy?->company_name ?? 'N/A',
                $app->vacancy?->job_title ?? 'N/A',
                $app->vacancy?->location ?? 'Remote',
                $app->vacancy?->work_mode ?? 'Remote',
                $app->roleProfile?->name ?? 'None',
                $app->applied_via,
                $app->discovered_from,
                $app->application_status,
                $app->created_at->format('Y-m-d H:i:s'),
                $app->sent_at?->format('Y-m-d H:i:s') ?? 'Not Sent',
                $app->registry?->canonical_fingerprint ?? 'N/A',
            ]);
        }
        fclose($csvHandle);
        $csvChecksum = hash_file('sha256', $csvPath);

        // 4. Write Checksums verification manifest
        $manifestPath = "{$fullDir}/{$checksumFileName}";
        $manifestContent = "{$jsonlChecksum}  {$jsonlFileName}\n{$csvChecksum}  {$csvFileName}\n";
        File::put($manifestPath, $manifestContent);

        // 5. Store / Update ApplicationArchive record
        return ApplicationArchive::updateOrCreate(
            [
                'user_id' => $user->id,
                'archive_year' => $year,
                'archive_month' => $month,
            ],
            [
                'records_count' => $applications->count(),
                'jsonl_filename' => "{$relativeDir}/{$jsonlFileName}",
                'jsonl_checksum_sha256' => $jsonlChecksum,
                'file_size_bytes' => $fileSize,
                'csv_filename' => "{$relativeDir}/{$csvFileName}",
                'csv_checksum_sha256' => $csvChecksum,
                'status' => 'Generated',
                'archived_at' => now(),
            ]
        );
    }
}

