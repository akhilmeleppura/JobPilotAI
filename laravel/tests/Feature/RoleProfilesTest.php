<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_accessing_role_profiles(): void
    {
        $response = $this->get('/role-profiles');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_role_profiles_and_auto_seeds_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/role-profiles');

        $response->assertStatus(200);
        $response->assertSee('Career Role Profiles');
        $response->assertSee('Laravel & PHP Backend Specialist');
        $response->assertSee('WordPress & WooCommerce Specialist');
        $response->assertSee('Full Stack Web Engineer');
        $response->assertSee('General Web Developer');
        $response->assertSee('Freelance & Contract Consultant');

        $this->assertEquals(5, RoleProfile::where('user_id', $user->id)->count());
        $this->assertTrue(RoleProfile::where('user_id', $user->id)->where('is_default', true)->exists());
    }

    public function test_user_can_create_a_custom_role_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/role-profiles', [
            'name' => 'AI Automation & Prompt Engineer',
            'experience_years' => 3.5,
            'target_titles' => 'AI Engineer, LLM Integrator, Prompt Specialist',
            'technical_skills' => 'Python, OpenAI API, Gemini API, LangChain, Ollama',
            'secondary_skills' => 'FastAPI, Docker, Vector Databases',
            'summary' => 'Engineering robust agentic workflows and local LLM pipelines.',
            'work_modes' => ['Remote'],
            'location_preferences' => 'Remote Worldwide',
            'salary_min' => 70000,
            'salary_max' => 120000,
            'currency' => 'USD',
            'search_keywords' => 'AI Engineer, LLM, Agentic',
            'email_signature' => "Regards,\nAI Engineer",
        ]);

        $response->assertRedirect(route('roleprofiles.index'));
        $this->assertDatabaseHas('role_profiles', [
            'user_id' => $user->id,
            'name' => 'AI Automation & Prompt Engineer',
        ]);
    }

    public function test_user_can_update_a_role_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/role-profiles');

        $profile = RoleProfile::where('user_id', $user->id)->where('slug', 'laravel-php-backend')->firstOrFail();

        $response = $this->actingAs($user)->put("/role-profiles/{$profile->id}", [
            'name' => 'Principal Laravel & Cloud Architect',
            'experience_years' => 8.0,
            'target_titles' => 'Principal PHP Engineer, Lead Laravel Architect',
            'technical_skills' => 'PHP 8.4, Laravel 13, TiDB Cloud, Redis, Kubernetes',
            'secondary_skills' => 'AWS, Terraform, Livewire',
            'summary' => 'Enterprise Laravel architecture and distributed database scaling.',
            'work_modes' => ['Remote'],
            'location_preferences' => 'Global',
            'salary_min' => 90000,
            'salary_max' => 150000,
            'currency' => 'USD',
            'search_keywords' => 'Architect, Laravel, PHP',
            'email_signature' => 'Principal Architect',
        ]);

        $response->assertRedirect(route('roleprofiles.index'));
        $this->assertDatabaseHas('role_profiles', [
            'id' => $profile->id,
            'name' => 'Principal Laravel & Cloud Architect',
            'experience_years' => 8.0,
        ]);
    }

    public function test_user_can_set_new_default_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/role-profiles');

        $wpProfile = RoleProfile::where('user_id', $user->id)->where('slug', 'wordpress-woocommerce-specialist')->firstOrFail();

        $response = $this->actingAs($user)->post("/role-profiles/{$wpProfile->id}/set-default");
        $response->assertRedirect(route('roleprofiles.index'));

        $this->assertTrue($wpProfile->fresh()->is_default);
        $this->assertEquals(1, RoleProfile::where('user_id', $user->id)->where('is_default', true)->count());
    }

    public function test_user_can_toggle_active_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/role-profiles');

        $profile = RoleProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($profile->is_active);

        $response = $this->actingAs($user)->post("/role-profiles/{$profile->id}/toggle-active");
        $response->assertRedirect(route('roleprofiles.index'));

        $this->assertFalse($profile->fresh()->is_active);
    }

    public function test_user_cannot_delete_default_profile_but_can_delete_others(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/role-profiles');

        $defaultProfile = RoleProfile::where('user_id', $user->id)->where('is_default', true)->firstOrFail();
        $nonDefaultProfile = RoleProfile::where('user_id', $user->id)->where('is_default', false)->firstOrFail();

        // Attempt deleting default
        $response = $this->actingAs($user)->delete("/role-profiles/{$defaultProfile->id}");
        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('role_profiles', ['id' => $defaultProfile->id]);

        // Delete non-default
        $response2 = $this->actingAs($user)->delete("/role-profiles/{$nonDefaultProfile->id}");
        $response2->assertRedirect(route('roleprofiles.index'));
        $this->assertDatabaseMissing('role_profiles', ['id' => $nonDefaultProfile->id]);
    }
}

