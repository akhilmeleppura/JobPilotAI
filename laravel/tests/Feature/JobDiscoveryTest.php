<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationEvent;
use App\Modules\Applications\app\Models\ApplicationRegistry;
use App\Modules\Jobs\app\DTOs\JobVacancyDTO;
use App\Modules\Jobs\app\Models\Company;
use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\Jobs\app\Services\Adapters\AdzunaJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\ATSBoardJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\HimalayasJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\JoobleJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\RemoteOKJobAdapter;
use App\Modules\Jobs\app\Services\Adapters\RemotiveJobAdapter;
use App\Modules\Jobs\app\Services\JobDiscoveryService;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class JobDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser(): User
    {
        $user = User::factory()->create();
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        app(ResumeService::class)->getOrSeedResumesForUser($user);
        return $user;
    }

    public function test_guests_are_redirected_from_job_discovery(): void
    {
        $response = $this->get('/jobs');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_job_discovery_dashboard(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get('/jobs');
        $response->assertStatus(200);
        $response->assertSee('Job Discovery');
        $response->assertSee('Fetch Openings');
        $response->assertSee('TOTAL DISCOVERED JOBS');
    }

    public function test_all_free_job_adapters_return_valid_vacancy_dtos(): void
    {
        $user = $this->setupUser();

        $adapters = [
            app(HimalayasJobAdapter::class),
            app(RemoteOKJobAdapter::class),
            app(RemotiveJobAdapter::class),
            app(AdzunaJobAdapter::class),
            app(JoobleJobAdapter::class),
            app(ATSBoardJobAdapter::class),
        ];

        foreach ($adapters as $adapter) {
            $jobs = $adapter->fetchJobs(['query' => 'PHP'], $user);
            $this->assertNotEmpty($jobs, "Adapter {$adapter->getProviderKey()} returned empty collection.");

            $first = $jobs->first();
            $this->assertInstanceOf(JobVacancyDTO::class, $first);
            $this->assertNotEmpty($first->company_name);
            $this->assertNotEmpty($first->job_title);
            $this->assertNotEmpty($first->apply_url);
            $this->assertNotEmpty($first->source_provider);
        }
    }

    public function test_job_discovery_service_ingests_and_deduplicates_vacancies(): void
    {
        $user = $this->setupUser();
        $service = app(JobDiscoveryService::class);

        // First run
        $result1 = $service->fetchAndIngestAll($user, ['query' => 'PHP Laravel']);

        $this->assertGreaterThan(0, $result1['fetched']);
        $this->assertGreaterThan(0, $result1['new_vacancies']);
        $initialCount = JobVacancy::count();
        $this->assertEquals($result1['new_vacancies'], $initialCount);

        // Verify ApplicationRegistry auto-discovered entries
        $registryCount = ApplicationRegistry::where('user_id', $user->id)->count();
        $this->assertEquals($initialCount, $registryCount);

        // Second run: exact same criteria should deduplicate
        $result2 = $service->fetchAndIngestAll($user, ['query' => 'PHP Laravel']);
        $this->assertEquals(0, $result2['new_vacancies']);
        $this->assertEquals($result2['fetched'], $result2['duplicates']);
        $this->assertEquals($initialCount, JobVacancy::count());
    }

    public function test_user_can_filter_vacancies_by_search_and_provider(): void
    {
        $user = $this->setupUser();
        $service = app(JobDiscoveryService::class);
        $service->fetchAndIngestAll($user, ['query' => 'PHP Laravel']);

        // Filter by text search
        $response = $this->actingAs($user)->get('/jobs?query=Automattic');
        $response->assertStatus(200);
        $response->assertSee('Automattic');

        // Filter by provider
        $responseProvider = $this->actingAs($user)->get('/jobs?provider=Himalayas');
        $responseProvider->assertStatus(200);
        $responseProvider->assertSee('Himalayas');
    }

    public function test_user_can_stage_application_with_auto_resolved_cv(): void
    {
        $user = $this->setupUser();
        $service = app(JobDiscoveryService::class);
        $service->fetchAndIngestAll($user, ['query' => 'PHP']);

        $vacancy = JobVacancy::firstOrFail();

        $response = $this->actingAs($user)->post("/jobs/{$vacancy->id}/stage");
        $response->assertRedirect('/applications');
        $response->assertSessionHas('success');

        // Verify Application created
        $application = Application::where('user_id', $user->id)
            ->where('job_vacancy_id', $vacancy->id)
            ->firstOrFail();

        $this->assertEquals('Draft', $application->application_status);
        $this->assertNotNull($application->resume_id);

        // Verify audit event logged
        $event = ApplicationEvent::where('application_id', $application->id)->firstOrFail();
        $this->assertEquals('APPLICATION_STAGED', $event->event_type);

        // Verify vacancy status
        $vacancy->refresh();
        $this->assertEquals('Staged', $vacancy->status);
    }

    public function test_artisan_fetch_jobs_command_executes_successfully(): void
    {
        $user = $this->setupUser();

        $exitCode = Artisan::call('jobpilot:fetch-jobs', [
            '--user-id' => $user->id,
            '--query' => 'PHP',
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertGreaterThan(0, JobVacancy::count());
    }
}
