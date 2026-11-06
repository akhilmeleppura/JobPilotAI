<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\DTOs\AIResponseDTO;
use App\Modules\AI\app\Exceptions\PaidResourceBlockedException;
use App\Modules\AI\app\Models\AIRequestLog;
use App\Modules\AI\app\Services\AIGatewayService;
use App\Modules\AI\app\Services\Providers\GeminiProvider;
use App\Modules\AI\app\Services\Providers\GroqProvider;
use App\Modules\AI\app\Services\Providers\OllamaProvider;
use App\Modules\AI\app\Services\Providers\OpenRouterProvider;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_ai_chat(): void
    {
        $response = $this->get('/ai-chat');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_ai_gateway_playground(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/ai-chat');
        $response->assertStatus(200);
        $response->assertSee('Free-Only AI Gateway');
        $response->assertSee('Zero-Spend Enforcement');
        $response->assertSee('gemini-2.5-flash');
        $response->assertSee('llama-3.3-70b-versatile');
    }

    public function test_free_resources_only_blocks_known_paid_models(): void
    {
        $user = User::factory()->create();
        $vault = app(CredentialVaultService::class);
        $vault->getOrSeedIntegrationsForUser($user);

        $gateway = app(AIGatewayService::class);

        $this->expectException(PaidResourceBlockedException::class);

        $request = new AIRequestDTO(
            prompt: 'Test prompt',
            model: 'gpt-4o-2024-08-06',
            preferredProvider: 'openrouter'
        );

        $gateway->complete($user, $request);

        $this->assertDatabaseHas('ai_request_logs', [
            'user_id' => $user->id,
            'status' => 'blocked_by_policy',
        ]);
    }

    public function test_openrouter_strictly_rejects_non_free_models(): void
    {
        $provider = new OpenRouterProvider();

        $this->expectException(PaidResourceBlockedException::class);

        $request = new AIRequestDTO(
            prompt: 'Test prompt',
            model: 'meta-llama/llama-3-70b-instruct' // Missing :free suffix
        );

        $provider->generate($request, 'test-key');
    }

    public function test_gateway_executes_completion_and_logs_zero_cost(): void
    {
        $user = User::factory()->create();
        $vault = app(CredentialVaultService::class);
        $vault->getOrSeedIntegrationsForUser($user);

        $groqCred = \App\Modules\Integrations\app\Models\IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'groq')
            ->firstOrFail();
        $vault->updateIntegration($groqCred, ['api_key' => 'gsk_mock_test_key_12345']);

        // Mock Groq API
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'High quality free AI completion response.']],
                ],
                'usage' => [
                    'prompt_tokens' => 12,
                    'completion_tokens' => 8,
                    'total_tokens' => 20,
                ],
            ], 200),
        ]);

        $gateway = app(AIGatewayService::class);

        $request = new AIRequestDTO(
            prompt: 'Explain Laravel modular monolith.',
            preferredProvider: 'groq'
        );

        $response = $gateway->complete($user, $request);

        $this->assertEquals('High quality free AI completion response.', $response->content);
        $this->assertEquals('groq', $response->provider);
        $this->assertEquals(0.000000, $response->costUsd);
        $this->assertTrue($response->isFreeTier);

        $this->assertDatabaseHas('ai_request_logs', [
            'user_id' => $user->id,
            'provider' => 'groq',
            'status' => 'success',
            'total_tokens' => 20,
            'cost_estimate_usd' => 0.000000,
        ]);
    }

    public function test_gateway_fallback_chain_switches_provider_on_failure(): void
    {
        $user = User::factory()->create();
        $vault = app(CredentialVaultService::class);
        $vault->getOrSeedIntegrationsForUser($user);

        $geminiCred = \App\Modules\Integrations\app\Models\IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'google_ai_studio')
            ->firstOrFail();
        $vault->updateIntegration($geminiCred, ['api_key' => 'AIzaSyMockGeminiKey']);

        $groqCred = \App\Modules\Integrations\app\Models\IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'groq')
            ->firstOrFail();
        $vault->updateIntegration($groqCred, ['api_key' => 'gsk_mock_test_key_12345']);

        // Mock Gemini failing with 429 rate limit
        // Mock Groq succeeding
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Rate limit exceeded']], 429),
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Fallback Groq response.']],
                ],
                'usage' => [
                    'prompt_tokens' => 15,
                    'completion_tokens' => 5,
                    'total_tokens' => 20,
                ],
            ], 200),
        ]);

        $gateway = app(AIGatewayService::class);

        $request = new AIRequestDTO(
            prompt: 'Test prompt with automatic fallback',
            preferredProvider: 'gemini'
        );

        $response = $gateway->complete($user, $request);

        $this->assertEquals('Fallback Groq response.', $response->content);
        $this->assertEquals('groq', $response->provider);

        $this->assertDatabaseHas('ai_request_logs', [
            'user_id' => $user->id,
            'provider' => 'groq',
            'status' => 'fallback_used',
        ]);
    }
}
