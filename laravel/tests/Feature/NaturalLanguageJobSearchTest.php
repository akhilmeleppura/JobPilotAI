<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Jobs\app\Models\Company;
use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\Jobs\app\Services\JobDiscoveryService;
use App\Modules\Jobs\app\Services\JobMatchScoringService;
use App\Modules\Jobs\app\Services\NaturalLanguageJobSearchService;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NaturalLanguageJobSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser(): User
    {
        $user = User::factory()->create();
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        app(ResumeService::class)->getOrSeedResumesForUser($user);
        return $user;
    }

    public function test_guests_are_redirected_from_ai_job_search(): void
    {
        $response = $this->get('/jobs/ai-search');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_ai_job_search_workbench(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get('/jobs/ai-search');
        $response->assertStatus(200);
        $response->assertSee('Natural Language AI Job Search');
        $response->assertSee('Search with AI');
        $response->assertSee('Quick Prompts:');
    }

    public function test_natural_language_parser_extracts_structured_parameters_heuristically(): void
    {
        $service = app(NaturalLanguageJobSearchService::class);

        $prompt = "Find high-paying remote senior Laravel and PHP backend jobs over $130k with TiDB or Redis";
        $parsed = $service->parseWithHeuristics($prompt);

        $this->assertEquals('Remote', $parsed['work_mode']);
        $this->assertEquals(130000, $parsed['salary_min']);
        $this->assertContains('Laravel', $parsed['keywords']);
        $this->assertContains('Redis', $parsed['keywords']);
        $this->assertNotEmpty($parsed['intent_summary']);
    }

    public function test_job_match_scoring_service_computes_multi_factor_score(): void
    {
        $user = $this->setupUser();
        $profile = RoleProfile::where('user_id', $user->id)
            ->where('slug', 'laravel-php-backend')
            ->firstOrFail();

        $company = Company::create(['name' => 'Automattic', 'normalized_name' => 'automattic']);
        $vacancy = JobVacancy::create([
            'company_id' => $company->id,
            'company_name' => 'Automattic',
            'job_title' => 'Senior Laravel & PHP Backend Specialist',
            'location' => 'Remote Worldwide',
            'work_mode' => 'Remote',
            'salary_min' => 140000,
            'salary_max' => 170000,
            'currency' => 'USD',
            'skills' => ['PHP 8.4', 'Laravel 13', 'MySQL', 'Redis', 'Docker'],
            'description' => 'Architecting resilient backend systems with Laravel and distributed caching.',
            'vacancy_fingerprint' => 'hash_test_scoring_1',
            'status' => 'New',
        ]);

        $scoringService = app(JobMatchScoringService::class);
        $evaluation = $scoringService->evaluateMatch($vacancy, $profile, [
            'salary_min' => 130000,
            'keywords' => ['Laravel', 'Redis'],
        ]);

        $this->assertGreaterThanOrEqual(80, $evaluation['score']);
        $this->assertEquals('Strong Match', $evaluation['grade']);
        $this->assertNotEmpty($evaluation['matched_skills']);
        $this->assertNotEmpty($evaluation['pros']);
        $this->assertStringContainsString('Stage immediately', $evaluation['recommendation']);
    }

    public function test_user_can_execute_conversational_job_search_and_receive_ranked_matches(): void
    {
        $user = $this->setupUser();
        app(JobDiscoveryService::class)->fetchAndIngestAll($user, ['query' => 'PHP']);

        $response = $this->actingAs($user)->get('/jobs/ai-search?prompt=remote+senior+laravel+backend+paying+over+120k');
        $response->assertStatus(200);
        $response->assertSee('AI Query Understanding');
        $response->assertSee('Ranked Match Results');
        $response->assertSee('MATCH');
    }

    public function test_match_score_api_endpoint_returns_json_evaluation(): void
    {
        $user = $this->setupUser();
        app(JobDiscoveryService::class)->fetchAndIngestAll($user, ['query' => 'PHP']);

        $vacancy = JobVacancy::firstOrFail();

        $response = $this->actingAs($user)->getJson("/jobs/{$vacancy->id}/match-score");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'vacancy_id',
            'job_title',
            'company_name',
            'role_profile',
            'evaluation' => [
                'score',
                'grade',
                'matched_skills',
                'missing_skills',
                'pros',
                'cons',
                'recommendation',
            ],
        ]);
    }
}
