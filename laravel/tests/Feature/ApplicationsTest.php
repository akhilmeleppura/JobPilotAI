<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationEvent;
use App\Modules\Applications\app\Models\ApplicationIdentityKey;
use App\Modules\Applications\app\Models\ApplicationRegistry;
use App\Modules\Applications\app\Services\DuplicateCheckerService;
use App\Modules\Applications\app\Services\FingerprintService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_applications(): void
    {
        $response = $this->get('/applications');
        $response->assertRedirect('/login');
    }

    public function test_fingerprint_service_normalizes_and_hashes_consistently(): void
    {
        $service = app(FingerprintService::class);

        // Same company with legal suffixes and different whitespace/cases
        $hash1 = $service->generate('Acme Technologies Inc.', 'Sr. PHP / Laravel Engineer', 'Remote Worldwide', 'REQ-101');
        $hash2 = $service->generate('Acme Corp', 'Senior Laravel / PHP Engineer', 'Remote', 'REQ-101');

        $this->assertEquals(64, strlen($hash1));
        $this->assertEquals($hash1, $hash2);

        // URL normalization stripping UTM parameters
        $url1 = 'https://careers.example.com/jobs/42?utm_source=linkedin&utm_medium=feed';
        $url2 = 'https://careers.example.com/jobs/42?ref=twitter';
        $hashUrl1 = $service->generate('Google', 'Software Engineer', 'Mountain View', null, $url1);
        $hashUrl2 = $service->generate('Google', 'Software Engineer', 'Mountain View', null, $url2);

        $this->assertEquals($hashUrl1, $hashUrl2);
    }

    public function test_user_can_stage_new_application_with_registry_and_keys(): void
    {
        $user = User::factory()->create();
        $roleProfile = RoleProfile::create([
            'user_id' => $user->id,
            'name' => 'Backend Specialist',
            'slug' => 'backend-specialist',
            'experience_years' => 5.0,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->post('/applications', [
            'company_name' => 'Automattic Inc.',
            'job_title' => 'Senior WordPress Engineer',
            'location' => 'Remote Worldwide',
            'work_mode' => 'Remote',
            'apply_method' => 'Email',
            'apply_url' => 'https://automattic.com/work-with-us/wp-senior/?utm_source=adzuna',
            'external_job_id' => 'WP-7721',
            'hr_email' => 'jobs@automattic.com',
            'role_profile_id' => $roleProfile->id,
            'notes' => 'Discovered on Jooble feed.',
        ]);

        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
            'applied_via' => 'Email',
            'application_status' => 'Staged',
        ]);

        $application = Application::where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect(route('applications.show', $application->id));

        // Verify permanent registry
        $this->assertDatabaseHas('application_registry', [
            'user_id' => $user->id,
            'company_name_normalized' => 'automattic',
            'job_title_normalized' => 'senior wordpress engineer',
        ]);

        // Verify identity keys
        $this->assertDatabaseHas('application_identity_keys', [
            'key_type' => 'REQ_ID',
            'key_value' => 'WP-7721',
        ]);

        // Verify initial lifecycle event
        $this->assertDatabaseHas('application_events', [
            'application_id' => $application->id,
            'event_type' => 'STAGED',
        ]);
    }

    public function test_duplicate_checker_blocks_exact_duplicate_within_60_days(): void
    {
        $user = User::factory()->create();
        $service = app(\App\Modules\Applications\app\Services\ApplicationRegistryService::class);

        $app = $service->stageApplication($user, [
            'company_name' => 'Stripe Inc.',
            'job_title' => 'Software Engineer, Payments',
            'location' => 'Remote',
            'apply_url' => 'https://stripe.com/jobs/1',
        ]);

        // Transition to Sent
        $service->updateStatus($app, 'Sent', 'Outreach email sent to hiring manager');

        // Attempting to stage same job again without override should be blocked
        $response = $this->actingAs($user)->post('/applications', [
            'company_name' => 'Stripe',
            'job_title' => 'Software Engineer, Payments',
            'location' => 'Remote',
            'work_mode' => 'Remote',
            'apply_method' => 'Email',
            'apply_url' => 'https://stripe.com/jobs/1?utm_source=twitter',
        ]);

        $response->assertSessionHasErrors('duplicate');
        $this->assertEquals(1, Application::where('user_id', $user->id)->count());
    }

    public function test_user_can_update_application_status_and_logs_event(): void
    {
        $user = User::factory()->create();
        $service = app(\App\Modules\Applications\app\Services\ApplicationRegistryService::class);

        $app = $service->stageApplication($user, [
            'company_name' => 'Shopify',
            'job_title' => 'Staff Backend Developer',
            'location' => 'Remote',
        ]);

        $response = $this->actingAs($user)->post("/applications/{$app->id}/status", [
            'status' => 'Interview',
            'note' => 'Screening call scheduled with hiring manager.',
        ]);

        $response->assertRedirect(route('applications.show', $app->id));
        $this->assertEquals('Interview', $app->fresh()->application_status);

        $this->assertDatabaseHas('application_events', [
            'application_id' => $app->id,
            'event_type' => 'STATUS_CHANGE',
        ]);
    }

    public function test_reopened_vacancy_after_60_days_allows_override(): void
    {
        $user = User::factory()->create();
        $service = app(\App\Modules\Applications\app\Services\ApplicationRegistryService::class);

        $app = $service->stageApplication($user, [
            'company_name' => 'GitHub',
            'job_title' => 'Senior Systems Engineer',
            'location' => 'Remote',
        ]);

        // Simulate application sent 75 days ago
        $app->update(['sent_at' => Carbon::now()->subDays(75)]);
        $app->registry->update(['last_applied_at' => Carbon::now()->subDays(75)]);

        // Try submitting with override checkbox
        $response = $this->actingAs($user)->post('/applications', [
            'company_name' => 'GitHub',
            'job_title' => 'Senior Systems Engineer',
            'location' => 'Remote',
            'work_mode' => 'Remote',
            'apply_method' => 'Portal',
            'override_duplicate' => 1,
        ]);

        $response->assertSessionDoesntHaveErrors();
    }
}

