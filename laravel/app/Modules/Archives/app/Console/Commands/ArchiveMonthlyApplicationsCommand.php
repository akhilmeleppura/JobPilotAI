<?php

namespace App\Modules\Archives\app\Console\Commands;

use App\Models\User;
use App\Modules\Archives\app\Services\ArchivePackagingService;
use App\Modules\Archives\app\Services\GoogleDriveArchiveService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ArchiveMonthlyApplicationsCommand extends Command
{
    protected $signature = 'jobpilot:archive-monthly {--user_id= : Specific user ID} {--year= : Target year} {--month= : Target month}';

    protected $description = 'Compile monthly applications archive (JSONL.gz and CSV) with SHA-256 integrity checksum and Google Drive sync';

    public function handle(ArchivePackagingService $packagingService, GoogleDriveArchiveService $driveService): int
    {
        $userId = $this->option('user_id');
        $targetYear = $this->option('year') ? (int) $this->option('year') : Carbon::now()->year;
        $targetMonth = $this->option('month') ? (int) $this->option('month') : Carbon::now()->month;

        $users = $userId ? User::where('id', $userId)->get() : User::all();

        if ($users->isEmpty()) {
            $this->warn('No users found to process for monthly archive.');
            return self::SUCCESS;
        }

        $this->info("Starting monthly archive compilation for {$targetYear}-{$targetMonth}...");

        foreach ($users as $user) {
            $this->line("Processing candidate: {$user->name} ({$user->email})...");
            $archive = $packagingService->generateMonthlyArchive($user, $targetYear, $targetMonth);

            $this->info("✓ Archive generated: {$archive->records_count} records. File: {$archive->jsonl_filename} (Checksum: {$archive->jsonl_checksum_sha256})");

            $syncResult = $driveService->syncArchive($archive);
            if ($syncResult['success']) {
                $this->info("✓ Cloud Sync: {$syncResult['message']}");
            } else {
                $this->warn("⚠ Cloud Sync note: {$syncResult['message']}");
            }
        }

        $this->info('Monthly archive cycle complete.');
        return self::SUCCESS;
    }
}

