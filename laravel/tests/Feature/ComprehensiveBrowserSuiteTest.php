<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Jobs\app\Models\Company;
use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\ContentStudio\app\Models\ContentPost;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComprehensiveBrowserSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setupCandidate(): User
    {
        $user = User::factory()->create([
            'name' => 'Alex QAEngineer',
            'email' => 'alex.qa@jobpilot.test',
            'password' => Hash::make('Password123!'),
        ]);

        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        app(ResumeService::class)->getOrSeedResumesForUser($user);
        app(CredentialVaultService::class)->getOrSeedIntegrationsForUser($user);

        return $user;
    }

    public function test_complete_authentication_workflow(): void
    {
        // 1. Visit Register Page
        $registerPage = $this->get('/register');
        $registerPage->assertStatus(200);

        // 2. Submit Valid Registration
        $newUserEmail = 'newcandidate_' . time() . '@jobpilot.test';
        $registerSubmit = $this->post('/register', [
            'name' => 'New Candidate',
            'email' => $newUserEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $registerSubmit->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        // 3. Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();

        // 4. Login with newly created credentials
        $loginResponse = $this->post('/login', [
            'email' => $newUserEmail,
            'password' => 'Password123!',
        ]);

        $loginResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_all_sixteen_core_dashboard_and_module_views_render_with_security_headers(): void
    {
        $user = $this->setupCandidate();

        // Verify cv-studio redirect route
        $cvStudioRedirect = $this->actingAs($user)->get('/cv-studio');
        $cvStudioRedirect->assertRedirect('/cv-studio/assignment');

        $routes = [
            '/',
            '/role-profiles',
            '/cv-studio/assignment',
            '/jobs',
            '/jobs/ai-search',
            '/applications',
            '/import',
            '/email-studio',
            '/email-studio/templates',
            '/ai-chat',
            '/linkedin-optimizer',
            '/content-studio',
            '/linkedin/publishing',
            '/archives',
            '/settings',
            '/system-health',
        ];

        foreach ($routes as $route) {
            $start = microtime(true);
            $response = $this->actingAs($user)->get($route);
            $elapsedMs = (microtime(true) - $start) * 1000;

            $response->assertStatus(200, "Route {$route} failed to render with HTTP 200.");
            
            // Verify Security Headers
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');

            // Verify Performance ceiling (< 500ms in-memory execution)
            $this->assertLessThan(500, $elapsedMs, "Route {$route} rendered slower than 500ms ({$elapsedMs}ms).");
        }
    }

    public function test_multi_user_data_isolation_and_cross_tenant_access_control(): void
    {
        $userA = $this->setupCandidate();
        $userB = User::factory()->create(['name' => 'Separate Tenant', 'email' => 'userb@jobpilot.test']);

        $registryService = app(\App\Modules\Applications\app\Services\ApplicationRegistryService::class);
        $appA = $registryService->stageApplication($userA, [
            'company_name' => 'Confidential Corp',
            'job_title' => 'Chief Architect',
            'location' => 'Remote',
            'apply_url' => 'https://confidential.test/jobs/1',
        ]);

        $postA = ContentPost::create([
            'user_id' => $userA->id,
            'title' => 'Private Draft Strategy',
            'topic' => 'Architecture',
            'content' => 'Top secret candidate strategy',
            'status' => 'draft',
        ]);

        // User B views applications and content studio: must NOT see User A's data
        $responseApps = $this->actingAs($userB)->get('/applications');
        $responseApps->assertStatus(200);
        $responseApps->assertDontSee('Confidential Corp');

        $responseContent = $this->actingAs($userB)->get('/content-studio');
        $responseContent->assertStatus(200);
        $responseContent->assertDontSee('Private Draft Strategy');
    }

    public function test_performance_synthetic_scale_queries(): void
    {
        $user = $this->setupCandidate();

        // Seed 50 applications synthetically
        $registryService = app(\App\Modules\Applications\app\Services\ApplicationRegistryService::class);
        for ($i = 1; $i <= 50; $i++) {
            $registryService->stageApplication($user, [
                'company_name' => "Scale Corp {$i}",
                'job_title' => "Engineer #{$i}",
                'location' => 'Remote',
                'apply_url' => "https://scalecorp.test/jobs/{$i}",
            ]);
        }

        $start = microtime(true);
        $response = $this->actingAs($user)->get('/applications');
        $durationMs = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(400, $durationMs, "Applications page with 50 records loaded in {$durationMs}ms (exceeded 400ms)");
    }
}
