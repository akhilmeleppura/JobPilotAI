<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Monitoring\app\Models\SystemBackup;
use App\Modules\Monitoring\app\Services\BackupService;
use App\Modules\Monitoring\app\Services\SystemMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser(): User
    {
        return User::factory()->create([
            'name' => 'DevOps Engineer',
            'email' => 'devops@example.com',
        ]);
    }

    public function test_guests_are_redirected_from_monitoring_dashboard(): void
    {
        $response = $this->get(route('monitoring.index'));
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_monitoring_dashboard_and_views_health_scorecard(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get(route('monitoring.index'));

        $response->assertStatus(200);
        $response->assertSee('System Health, Security', false);
        $response->assertSee('Health Rating', false);
        $response->assertSee('Database Backups', false);
        $response->assertSee('Oracle Cloud Always Free VM (ARM64 Architecture)');
    }

    public function test_system_monitoring_service_evaluates_all_subsystems_cleanly(): void
    {
        $service = app(SystemMonitoringService::class);
        $health = $service->getOverallHealth();

        $this->assertArrayHasKey('overall_status', $health);
        $this->assertArrayHasKey('overall_score', $health);
        $this->assertGreaterThanOrEqual(70, $health['overall_score']);

        $subsystems = $health['subsystems'];
        $this->assertArrayHasKey('database', $subsystems);
        $this->assertArrayHasKey('storage', $subsystems);
        $this->assertArrayHasKey('ai_gateway', $subsystems);
        $this->assertArrayHasKey('queues', $subsystems);
        $this->assertArrayHasKey('security', $subsystems);

        $this->assertEquals('healthy', $subsystems['database']['status']);
    }

    public function test_backup_service_creates_database_backup_with_sha256_checksum(): void
    {
        $backupService = app(BackupService::class);
        $backup = $backupService->createBackup();

        $this->assertNotNull($backup);
        $this->assertDatabaseHas('system_backups', ['id' => $backup->id]);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals(64, strlen($backup->checksum_sha256));

        $fullPath = storage_path("app/{$backup->path}");
        $this->assertTrue(File::exists($fullPath));
        $this->assertTrue($backupService->verifyBackup($backup));
    }

    public function test_backup_rotation_prunes_older_backups(): void
    {
        $backupService = app(BackupService::class);

        // Create 8 backups
        for ($i = 0; $i < 8; $i++) {
            $backupService->createBackup();
        }

        // Must retain at most 7
        $this->assertLessThanOrEqual(7, SystemBackup::count());
    }

    public function test_artisan_backup_command_runs_successfully(): void
    {
        $this->artisan('jobpilot:backup')
            ->expectsOutputToContain('Database backup completed successfully!')
            ->assertExitCode(0);
    }

    public function test_user_can_trigger_backup_via_http_and_download_stream(): void
    {
        $user = $this->setupUser();

        // 1. Trigger backup via HTTP POST
        $createResponse = $this->actingAs($user)->post(route('monitoring.backup'));
        $createResponse->assertRedirect(route('monitoring.index'));

        $backup = SystemBackup::latest()->first();
        $this->assertNotNull($backup);

        // 2. Download backup stream
        $downloadResponse = $this->actingAs($user)->get(route('monitoring.backup.download', $backup->id));
        $downloadResponse->assertStatus(200);
        $this->assertTrue($downloadResponse->headers->has('content-disposition'));
        $this->assertStringContainsString($backup->filename, $downloadResponse->headers->get('content-disposition'));
    }

    public function test_security_headers_middleware_attaches_security_headers_to_responses(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get(route('monitoring.index'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_health_json_api_endpoint_returns_json_scorecard(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->getJson(route('monitoring.api'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overall_status',
                'overall_score',
                'timestamp',
                'subsystems' => [
                    'database',
                    'storage',
                    'ai_gateway',
                    'queues',
                    'security',
                ],
            ]);
    }
}
