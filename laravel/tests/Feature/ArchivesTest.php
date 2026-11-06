<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Applications\app\Services\ApplicationRegistryService;
use App\Modules\Archives\app\Models\ApplicationArchive;
use App\Modules\Archives\app\Services\ArchivePackagingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ArchivesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_archives(): void
    {
        $response = $this->get('/archives');
        $response->assertRedirect('/login');
    }

    public function test_user_can_generate_monthly_archive_and_verify_files(): void
    {
        $user = User::factory()->create();
        $appService = app(ApplicationRegistryService::class);

        // Stage applications for November 2026
        $app1 = $appService->stageApplication($user, [
            'company_name' => 'GitLab Inc.',
            'job_title' => 'Senior Backend Engineer, Ruby & PHP',
            'location' => 'Remote Worldwide',
            'apply_url' => 'https://about.gitlab.com/jobs/1234',
        ]);
        $appService->updateStatus($app1, 'Sent', 'Direct outreach to Engineering Director');

        $app2 = $appService->stageApplication($user, [
            'company_name' => 'Laravel LLC',
            'job_title' => 'Full Stack Cloud Developer',
            'location' => 'Remote',
            'apply_url' => 'https://laravel.com/careers/5678',
        ]);

        $response = $this->actingAs($user)->post('/archives/generate', [
            'year' => 2026,
            'month' => 11,
        ]);

        $response->assertRedirect(route('archives.index'));
        $this->assertDatabaseHas('application_archives', [
            'user_id' => $user->id,
            'archive_year' => 2026,
            'archive_month' => 11,
            'records_count' => 2,
        ]);

        $archive = ApplicationArchive::where('user_id', $user->id)->firstOrFail();
        
        // Verify JSONL.gz file on disk
        $jsonlPath = storage_path("app/{$archive->jsonl_filename}");
        $this->assertFileExists($jsonlPath);
        $this->assertEquals($archive->jsonl_checksum_sha256, hash_file('sha256', $jsonlPath));

        // Uncompress and check JSONL content
        $uncompressed = gzdecode(File::get($jsonlPath));
        $this->assertStringContainsString('GitLab Inc.', $uncompressed);
        $this->assertStringContainsString('Laravel LLC', $uncompressed);
        $this->assertStringContainsString('canonical_fingerprint', $uncompressed);

        // Verify CSV file on disk
        $csvPath = storage_path("app/{$archive->csv_filename}");
        $this->assertFileExists($csvPath);
        $this->assertEquals($archive->csv_checksum_sha256, hash_file('sha256', $csvPath));
        $csvContent = File::get($csvPath);
        $this->assertStringContainsString('GitLab Inc.', $csvContent);
    }

    public function test_user_can_download_archive_files(): void
    {
        $user = User::factory()->create();
        $packagingService = app(ArchivePackagingService::class);
        $archive = $packagingService->generateMonthlyArchive($user, 2026, 11);

        // Download JSONL
        $response1 = $this->actingAs($user)->get("/archives/{$archive->id}/download-jsonl");
        $response1->assertStatus(200);
        $response1->assertHeader('Content-Type', 'application/gzip');

        // Download CSV
        $response2 = $this->actingAs($user)->get("/archives/{$archive->id}/download-csv");
        $response2->assertStatus(200);
        $response2->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_user_can_verify_checksum_and_sync_drive(): void
    {
        $user = User::factory()->create();
        $packagingService = app(ArchivePackagingService::class);
        $archive = $packagingService->generateMonthlyArchive($user, 2026, 11);

        // Verify Checksum endpoint
        $resVerify = $this->actingAs($user)->post("/archives/{$archive->id}/verify-checksum");
        $resVerify->assertRedirect(route('archives.index'));
        $this->assertEquals('Verified', $archive->fresh()->status);

        // Trigger Sync endpoint
        $resSync = $this->actingAs($user)->post("/archives/{$archive->id}/sync-drive");
        $resSync->assertRedirect(route('archives.index'));
        $this->assertEquals('Synced', $archive->fresh()->status);
        $this->assertNotNull($archive->fresh()->gdrive_file_id);
    }

    public function test_artisan_archive_command_runs_successfully(): void
    {
        $user = User::factory()->create();

        $this->artisan('jobpilot:archive-monthly', [
            '--user_id' => $user->id,
            '--year' => 2026,
            '--month' => 11,
        ])->assertSuccessful();

        $this->assertDatabaseHas('application_archives', [
            'user_id' => $user->id,
            'archive_year' => 2026,
            'archive_month' => 11,
        ]);
    }
}

