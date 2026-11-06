<?php

namespace Tests\Feature;

use App\Models\User;
use App\Shared\Services\EmergencyStopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EmergencyStopTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_stop_defaults_to_false(): void
    {
        $service = app(EmergencyStopService::class);
        $service->resume(); // Ensure clear state

        $this->assertFalse($service->isStopped());
        $status = $service->getStatus();
        $this->assertFalse($status['is_engaged']);
    }

    public function test_emergency_stop_can_be_triggered_and_resumed(): void
    {
        $service = app(EmergencyStopService::class);
        $user = User::factory()->create();

        $service->trigger('Test maintenance shutdown', $user->id);
        $this->assertTrue($service->isStopped());

        $status = $service->getStatus();
        $this->assertTrue($status['is_engaged']);
        $this->assertEquals('Test maintenance shutdown', $status['reason']);
        $this->assertEquals($user->id, $status['engaged_by']);

        $service->resume($user->id);
        $this->assertFalse($service->isStopped());
    }

    public function test_artisan_emergency_stop_command(): void
    {
        $service = app(EmergencyStopService::class);
        $service->resume();

        Artisan::call('jobpilot:emergency-stop', ['--reason' => 'Database migration safety lock']);
        $this->assertTrue($service->isStopped());

        Artisan::call('jobpilot:emergency-stop', ['--resume' => true]);
        $this->assertFalse($service->isStopped());
    }
}

