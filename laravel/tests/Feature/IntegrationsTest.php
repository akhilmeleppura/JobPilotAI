<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\Integrations\app\Services\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_settings(): void
    {
        $response = $this->get('/settings');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_settings_and_auto_seeds_providers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings');
        $response->assertStatus(200);
        $response->assertSee('Central Settings');
        $response->assertSee('Google AI Studio');
        $response->assertSee('Groq API');
        $response->assertSee('Local Ollama');
        $response->assertSee('Gmail API');
        $response->assertSee('FREE_RESOURCES_ONLY');

        $this->assertDatabaseHas('integration_credentials', [
            'user_id' => $user->id,
            'provider_key' => 'google_ai_studio',
        ]);
        $this->assertDatabaseHas('integration_credentials', [
            'user_id' => $user->id,
            'provider_key' => 'policy_governance',
        ]);
    }

    public function test_secrets_are_stored_encrypted_and_never_leaked_in_html(): void
    {
        $user = User::factory()->create();
        $vaultService = app(CredentialVaultService::class);
        $integrations = $vaultService->getOrSeedIntegrationsForUser($user);

        $gemini = $integrations->firstWhere('provider_key', 'google_ai_studio');
        $rawSecret = 'AIzaSyASecretKey1234567890';

        $vaultService->updateIntegration($gemini, ['api_key' => $rawSecret]);

        // Verify that database column is encrypted and does not contain plain text
        $dbRecord = IntegrationCredential::find($gemini->id);
        $this->assertNotEmpty($dbRecord->encrypted_secrets);
        $this->assertStringNotContainsString($rawSecret, $dbRecord->encrypted_secrets);

        // Verify decryption matches
        $this->assertEquals($rawSecret, $dbRecord->getSecrets()['api_key']);

        // Verify HTML output contains masked value, not plain text
        $response = $this->actingAs($user)->get('/settings');
        $response->assertDontSee($rawSecret);
        $response->assertSee('AIza');
        $response->assertSee('7890');
    }

    public function test_user_can_update_settings_via_http(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/settings');

        $groq = IntegrationCredential::where('user_id', $user->id)->where('provider_key', 'groq')->firstOrFail();

        $response = $this->actingAs($user)->put("/settings/{$groq->id}", [
            'secrets' => [
                'api_key' => 'gsk_TestApiKey9988776655',
            ],
            'settings' => [
                'default_model' => 'llama-3.3-70b-versatile',
            ],
            'quota_limit_daily' => 500,
        ]);

        $response->assertRedirect(route('settings.index'));

        $groq->refresh();
        $this->assertEquals(500, $groq->quota_limit_daily);
        $this->assertEquals('llama-3.3-70b-versatile', $groq->settings['default_model']);
        $this->assertEquals('gsk_TestApiKey9988776655', $groq->getSecrets()['api_key']);
    }

    public function test_user_can_toggle_provider(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/settings');

        $ollama = IntegrationCredential::where('user_id', $user->id)->where('provider_key', 'ollama')->firstOrFail();
        $this->assertTrue($ollama->is_enabled);

        $response = $this->actingAs($user)->post("/settings/{$ollama->id}/toggle");
        $response->assertRedirect(route('settings.index'));
        $this->assertFalse($ollama->fresh()->is_enabled);
    }

    public function test_user_can_trigger_health_check_ping(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/settings');

        $policy = IntegrationCredential::where('user_id', $user->id)->where('provider_key', 'policy_governance')->firstOrFail();

        $response = $this->actingAs($user)->post("/settings/{$policy->id}/ping");
        $response->assertRedirect(route('settings.index'));

        $this->assertEquals('Healthy', $policy->fresh()->health_status);
        $this->assertNotNull($policy->fresh()->last_health_check_at);
    }
}
