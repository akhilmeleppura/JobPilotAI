<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Career Command Center');
        $response->assertSee('FREE_RESOURCES_ONLY Active');
        $response->assertSee('FREE EMAIL PROVIDER ALLOWANCE MONITOR');
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign In');
    }

    public function test_register_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Create Account');
    }

    public function test_users_can_register_and_authenticate(): void
    {
        $response = $this->post('/register', [
            'name' => 'Lead Developer',
            'email' => 'developer@jobpilot.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'developer@jobpilot.test',
        ]);
    }
}

