<?php

namespace App\Modules\Monitoring\app\Console\Commands;

use App\Modules\Monitoring\app\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'jobpilot:backup';

    protected $description = 'Create automated timestamped database backup with SHA-256 verification and rotation';

    public function handle(BackupService $backupService): int
    {
        $this->info('Starting JobPilot AI database backup...');

        try {
            $backup = $backupService->createBackup();

            $this->info("✓ Database backup completed successfully!");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Filename', $backup->filename],
                    ['Path', $backup->path],
                    ['Size', $backup->formatted_size],
                    ['Checksum (SHA-256)', $backup->checksum_sha256],
                    ['Engine', $backup->database_type],
                    ['Status', $backup->status],
                ]
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}

