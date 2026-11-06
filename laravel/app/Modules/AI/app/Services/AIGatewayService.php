<?php

namespace App\Modules\AI\app\Services;

use App\Models\User;
use App\Modules\AI\app\Contracts\AIProviderInterface;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\DTOs\AIResponseDTO;
use App\Modules\AI\app\Exceptions\PaidResourceBlockedException;
use App\Modules\AI\app\Models\AIRequestLog;
use App\Modules\AI\app\Services\Providers\GeminiProvider;
use App\Modules\AI\app\Services\Providers\GroqProvider;
use App\Modules\AI\app\Services\Providers\OllamaProvider;
use App\Modules\AI\app\Services\Providers\OpenRouterProvider;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AIGatewayService
{
    /** @var array<string, AIProviderInterface> */
    protected array $providers = [];

    public function __construct(
        protected CredentialVaultService $vaultService,
        GeminiProvider $gemini,
        GroqProvider $groq,
        OpenRouterProvider $openRouter,
        OllamaProvider $ollama
    ) {
        $this->providers = [
            'gemini' => $gemini,
            'groq' => $groq,
            'openrouter' => $openRouter,
            'ollama' => $ollama,
        ];
    }

    /**
     * Get a registered provider by key.
     */
    public function getProvider(string $key): ?AIProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    /**
     * Get all registered free providers.
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Generate text with strict Free-First enforcement and resilient fallback.
     */
    public function complete(User $user, AIRequestDTO $request): AIResponseDTO
    {
        // 1. Enforce FREE_RESOURCES_ONLY policy
        $policyCredential = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'policy_governance')
            ->first();

        $freeOnly = $policyCredential ? ($policyCredential->settings['FREE_RESOURCES_ONLY'] ?? true) : true;

        if ($freeOnly && $this->isKnownPaidModel($request->model)) {
            AIRequestLog::create([
                'user_id' => $user->id,
                'provider' => $request->preferredProvider ?? 'unknown',
                'model' => $request->model ?? 'unknown',
                'status' => 'blocked_by_policy',
                'error_message' => "Execution aborted: Model '{$request->model}' is a paid resource. FREE_RESOURCES_ONLY is enforced.",
            ]);

            throw new PaidResourceBlockedException("Requested model '{$request->model}' is a paid endpoint. Access is blocked by FREE_RESOURCES_ONLY policy.");
        }

        // 2. Order providers: Preferred first, followed by default fallback chain
        $chain = $this->buildFallbackChain($request->preferredProvider);

        $lastException = null;
        $isFallback = false;

        foreach ($chain as $index => $providerKey) {
            $provider = $this->providers[$providerKey] ?? null;
            if (!$provider) continue;

            $integration = IntegrationCredential::where('user_id', $user->id)
                ->where('provider_key', $this->mapProviderKeyToIntegration($providerKey))
                ->first();

            if ($integration && !$integration->is_enabled) {
                continue;
            }

            $secrets = $integration?->getSecrets() ?? [];
            $settings = $integration?->settings ?? [];
            $apiKey = $secrets['api_key'] ?? ($secrets['access_token'] ?? null);

            try {
                $response = $provider->generate($request, $apiKey, $settings);

                // Log success
                AIRequestLog::create([
                    'user_id' => $user->id,
                    'provider' => $providerKey,
                    'model' => $response->model,
                    'prompt_tokens' => $response->promptTokens,
                    'completion_tokens' => $response->completionTokens,
                    'total_tokens' => $response->totalTokens,
                    'cost_estimate_usd' => 0.000000,
                    'duration_ms' => $response->durationMs,
                    'status' => $isFallback ? 'fallback_used' : 'success',
                ]);

                // Increment today's request count on credential
                if ($integration) {
                    $integration->increment('requests_today');
                }

                return $response;
            } catch (Throwable $e) {
                $lastException = $e;
                $isFallback = true;
                Log::warning("AI Provider [{$providerKey}] failed, attempting next in fallback chain.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // If all providers in chain failed, log failure and rethrow
        AIRequestLog::create([
            'user_id' => $user->id,
            'provider' => $request->preferredProvider ?? 'all_providers',
            'model' => $request->model ?? 'default',
            'status' => 'failed',
            'error_message' => $lastException?->getMessage() ?? 'All AI providers in fallback chain failed.',
        ]);

        throw new RuntimeException("All free AI providers in fallback chain failed. Last error: " . $lastException?->getMessage(), 0, $lastException);
    }

    /**
     * Map internal provider key to integration credential key.
     */
    protected function mapProviderKeyToIntegration(string $providerKey): string
    {
        return match ($providerKey) {
            'gemini' => 'google_ai_studio',
            'groq' => 'groq',
            'openrouter' => 'openrouter',
            'ollama' => 'ollama',
            default => $providerKey,
        };
    }

    /**
     * Build prioritized fallback sequence.
     */
    protected function buildFallbackChain(?string $preferred): array
    {
        $defaultOrder = ['gemini', 'groq', 'openrouter', 'ollama'];

        if ($preferred && in_array($preferred, $defaultOrder)) {
            return array_unique(array_merge([$preferred], $defaultOrder));
        }

        return $defaultOrder;
    }

    /**
     * Check if a model name belongs to a known paid tier.
     */
    protected function isKnownPaidModel(?string $model): bool
    {
        if (empty($model)) {
            return false;
        }

        $paidSignatures = [
            'gpt-4',
            'gpt-4o',
            'gpt-3.5',
            'claude-3',
            'claude-2',
            'o1-preview',
            'o1-mini',
            'gemini-1.5-pro',
        ];

        foreach ($paidSignatures as $sig) {
            if (str_contains(strtolower($model), $sig)) {
                return true;
            }
        }

        return false;
    }
}
