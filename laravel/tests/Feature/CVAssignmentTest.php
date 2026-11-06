<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationRegistry;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Services\CVAssignmentResolverService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CVAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_cv_assignment(): void
    {
        $response = $this->get('/cv-studio/assignment');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_assignment_manager_and_auto_seeds_resumes(): void
    {
        $user = User::factory()->create();

        // Ensure role profiles are seeded first so resumes can link to them
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);

        $response = $this->actingAs($user)->get('/cv-studio/assignment');
        $response->assertStatus(200);
        $response->assertSee('Global CV Assignment Manager');
        $response->assertSee('Senior Laravel & PHP Backend Specialist CV');
        $response->assertSee('WordPress & WooCommerce Architect CV');

        $this->assertEquals(5, Resume::where('user_id', $user->id)->count());
    }

    public function test_mode_1_user_can_switch_global_default_resume(): void
    {
        $user = User::factory()->create();
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);

        $this->actingAs($user)->get('/cv-studio/assignment');

        $wpResume = Resume::where('user_id', $user->id)
            ->where('slug', 'wordpress-woocommerce-architect-cv')
            ->firstOrFail();

        $this->assertFalse((bool) $wpResume->is_global_default);

        $response = $this->actingAs($user)->post("/cv-studio/assignment/{$wpResume->id}/set-default");
        $response->assertRedirect('/cv-studio/assignment');
        $response->assertSessionHas('success');

        $wpResume->refresh();
        $this->assertTrue((bool) $wpResume->is_global_default);

        $laravelResume = Resume::where('user_id', $user->id)
            ->where('slug', 'senior-laravel-php-backend-cv')
            ->firstOrFail();
        $this->assertFalse((bool) $laravelResume->is_global_default);

        // Verify resolver picks it as Mode 1
        $resolver = app(CVAssignmentResolverService::class);
        $result = $resolver->resolveCV(user: $user);
        $this->assertEquals($wpResume->id, $result['resume']->id);
        $this->assertEquals(CVAssignmentResolverService::MODE_GLOBAL_DEFAULT, $result['mode']);
    }

    public function test_mode_2_user_can_map_resume_to_role_profile(): void
    {
        $user = User::factory()->create();
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        $this->actingAs($user)->get('/cv-studio/assignment');

        $customRole = RoleProfile::create([
            'user_id' => $user->id,
            'name' => 'DevOps Cloud Architect',
            'slug' => 'devops-cloud-architect',
            'is_default' => false,
            'is_active' => true,
        ]);

        $generalResume = Resume::where('user_id', $user->id)
            ->where('slug', 'general-web-solutions-cv')
            ->firstOrFail();

        $response = $this->actingAs($user)->post("/cv-studio/assignment/{$generalResume->id}/map-role", [
            'role_profile_id' => $customRole->id,
        ]);

        $response->assertRedirect('/cv-studio/assignment');
        $response->assertSessionHas('success');

        $generalResume->refresh();
        $this->assertEquals($customRole->id, $generalResume->role_profile_id);

        // Verify resolver resolves this resume when targeting this role profile
        $resolver = app(CVAssignmentResolverService::class);
        $result = $resolver->resolveCV(user: $user, roleProfileId: $customRole->id);
        $this->assertEquals($generalResume->id, $result['resume']->id);
        $this->assertEquals(CVAssignmentResolverService::MODE_ROLE_LINKED, $result['mode']);
    }

    public function test_mode_3_application_explicit_override_takes_precedence(): void
    {
        $user = User::factory()->create();
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        $this->actingAs($user)->get('/cv-studio/assignment');

        $contractResume = Resume::where('user_id', $user->id)
            ->where('slug', 'contract-freelance-mvp-cv')
            ->firstOrFail();

        $registry = ApplicationRegistry::create([
            'user_id' => $user->id,
            'canonical_fingerprint' => 'hash123',
            'company_name_normalized' => 'Stripe Partner',
            'job_title_normalized' => 'Senior PHP Engineer',
        ]);

        $application = Application::create([
            'user_id' => $user->id,
            'registry_id' => $registry->id,
            'resume_id' => $contractResume->id, // Mode 3 override
            'application_status' => 'Draft',
        ]);

        $resolver = app(CVAssignmentResolverService::class);
        $result = $resolver->resolveCV(user: $user, application: $application);

        $this->assertEquals($contractResume->id, $result['resume']->id);
        $this->assertEquals(CVAssignmentResolverService::MODE_APP_OVERRIDE, $result['mode']);
        $this->assertEquals(100, $result['match_score']);
    }

    public function test_mode_4_ai_semantic_keyword_match_endpoint_and_resolver(): void
    {
        $user = User::factory()->create();
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        $this->actingAs($user)->get('/cv-studio/assignment');

        // Test the JSON endpoint for test matching
        $response = $this->actingAs($user)->postJson('/cv-studio/assignment/test-match', [
            'job_title' => 'Principal WordPress WooCommerce Architect',
            'description' => 'Must have deep knowledge of WooCommerce custom plugins, hooks, filters, and PHP 8.x',
            'skills' => 'WordPress Core, WooCommerce, Stripe',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recommended_resume',
            'template',
            'mode',
            'match_score',
            'reasoning',
        ]);

        $data = $response->json();
        $this->assertEquals('WordPress & WooCommerce Architect CV', $data['recommended_resume']);
        $this->assertGreaterThanOrEqual(75, $data['match_score']);
    }
}
