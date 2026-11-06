<?php

namespace App\Modules\Integrations\app\Services;

use App\Models\User;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use Illuminate\Support\Collection;

class CredentialVaultService
{
    /**
     * Seed or get all standard integration slots for a candidate.
     */
    public function getOrSeedIntegrationsForUser(User $user): Collection
    {
        $defaults = [
            // AI Providers
            [
                'provider_group' => 'ai',
                'provider_key' => 'google_ai_studio',
                'provider_name' => 'Google AI Studio (Gemini 2.5/Flash)',
                'quota_limit_daily' => 1500, // 1500 RPD free tier
                'settings' => [
                    'default_model' => 'gemini-2.5-flash',
                    'free_tier_only' => true,
                    'rpm_limit' => 15,
                ],
            ],
            [
                'provider_group' => 'ai',
                'provider_key' => 'groq',
                'provider_name' => 'Groq API (Llama 3 High-Speed Inference)',
                'quota_limit_daily' => 1000,
                'settings' => [
                    'default_model' => 'llama-3.3-70b-versatile',
                    'free_tier_only' => true,
                ],
            ],
            [
                'provider_group' => 'ai',
                'provider_key' => 'openrouter',
                'provider_name' => 'OpenRouter (Free Tier Tagged Models)',
                'quota_limit_daily' => 200,
                'settings' => [
                    'default_model' => 'meta-llama/llama-3-8b-instruct:free',
                    'free_tier_only' => true,
                ],
            ],
            [
                'provider_group' => 'ai',
                'provider_key' => 'ollama',
                'provider_name' => 'Local Ollama (Zero-Cost / Private)',
                'quota_limit_daily' => 99999, // Unlimited local
                'settings' => [
                    'endpoint_url' => 'http://127.0.0.1:11434',
                    'default_model' => 'llama3:latest',
                    'free_tier_only' => true,
                ],
            ],
            [
                'provider_group' => 'ai',
                'provider_key' => 'cloudflare_workers_ai',
                'provider_name' => 'Cloudflare Workers AI (Free 10,000 Neurons/Day)',
                'quota_limit_daily' => 10000,
                'settings' => [
                    'account_id' => '',
                    'default_model' => '@cf/stabilityai/stable-diffusion-xl-base-1.0',
                    'free_tier_only' => true,
                ],
            ],

            // Email Outreach (Dynamic Provider Allowances)
            [
                'provider_group' => 'email',
                'provider_key' => 'gmail_api',
                'provider_name' => 'Gmail API (OAuth2 Outreach)',
                'quota_limit_daily' => null, // Dynamic provider allowance
                'settings' => [
                    'daily_cap' => null,
                    'batch_size' => 10,
                    'dynamic_quota' => true,
                ],
            ],
            [
                'provider_group' => 'email',
                'provider_key' => 'microsoft_graph',
                'provider_name' => 'Microsoft Graph / Outlook',
                'quota_limit_daily' => null,
                'settings' => [
                    'daily_cap' => null,
                    'dynamic_quota' => true,
                ],
            ],

            // Cloud Storage & Archives
            [
                'provider_group' => 'cloud_storage',
                'provider_key' => 'google_drive',
                'provider_name' => 'Google Drive & Google Sheets Long-Term Archive',
                'quota_limit_daily' => 500,
                'settings' => [
                    'folder_name' => 'JobPilot AI/Application History',
                ],
            ],

            // Job Discovery Sources
            [
                'provider_group' => 'job_sources',
                'provider_key' => 'adzuna',
                'provider_name' => 'Adzuna Jobs API',
                'quota_limit_daily' => 250,
                'settings' => [
                    'country' => 'us',
                ],
            ],
            [
                'provider_group' => 'job_sources',
                'provider_key' => 'jooble',
                'provider_name' => 'Jooble Search Engine API',
                'quota_limit_daily' => 500,
                'settings' => [],
            ],

            // Professional Social Publishing
            [
                'provider_group' => 'social_publishing',
                'provider_key' => 'linkedin_oauth',
                'provider_name' => 'Official LinkedIn Member API (OAuth2)',
                'quota_limit_daily' => 25, // Recommended safe post frequency
                'settings' => [
                    'client_id' => '',
                    'client_secret' => '',
                    'redirect_uri' => '',
                    'member_urn' => '',
                    'member_name' => '',
                    'requires_human_review' => true,
                ],
            ],

            // System Governance
            [
                'provider_group' => 'system',
                'provider_key' => 'policy_governance',
                'provider_name' => 'Resource Policy & Application Ceilings',
                'quota_limit_daily' => null, // Dynamic allowance: no fixed application-side ceiling
                'settings' => [
                    'FREE_RESOURCES_ONLY' => true,
                    'global_daily_application_ceiling' => null,
                    'enable_duplicate_blocking_window_days' => 60,
                ],
            ],
        ];

        foreach ($defaults as $data) {
            IntegrationCredential::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'provider_key' => $data['provider_key'],
                ],
                [
                    'provider_group' => $data['provider_group'],
                    'provider_name' => $data['provider_name'],
                    'quota_limit_daily' => $data['quota_limit_daily'],
                    'settings' => $data['settings'],
                    'is_enabled' => true,
                ]
            );
        }

        return IntegrationCredential::where('user_id', $user->id)
            ->orderBy('provider_group')
            ->orderBy('provider_name')
            ->get();
    }

    /**
     * Update integration credential secrets and optional configuration settings.
     */
    public function updateIntegration(
        IntegrationCredential $credential,
        array $secrets,
        array $settings = [],
        ?bool $isEnabled = null
    ): IntegrationCredential {
        $existingSecrets = $credential->getSecrets();

        // Merge secrets, keeping existing if field was left blank/unchanged
        foreach ($secrets as $k => $v) {
            if ($v !== null && $v !== '') {
                $existingSecrets[$k] = trim($v);
            }
        }

        $credential->setSecrets($existingSecrets);

        if (!empty($settings)) {
            $credential->settings = array_merge($credential->settings ?? [], $settings);
        }

        if ($isEnabled !== null) {
            $credential->is_enabled = $isEnabled;
        }

        $credential->save();

        return $credential;
    }

    /**
     * Retrieve decrypted secret for a specific provider.
     */
    public function getSecret(User $user, string $providerKey, string $secretKey): ?string
    {
        $integration = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', $providerKey)
            ->where('is_enabled', true)
            ->first();

        if (!$integration) {
            return null;
        }

        $secrets = $integration->getSecrets();
        return $secrets[$secretKey] ?? null;
    }
}

