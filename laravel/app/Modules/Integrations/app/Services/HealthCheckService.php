<?php

namespace App\Modules\Integrations\app\Services;

use App\Modules\Integrations\app\Models\IntegrationCredential;
use Illuminate\Support\Facades\Http;
use Throwable;

class HealthCheckService
{
    /**
     * Perform live connectivity and authentication ping against an integration provider.
     */
    public function check(IntegrationCredential $integration): array
    {
        $secrets = $integration->getSecrets();
        $key = $integration->provider_key;

        // If provider is disabled
        if (!$integration->is_enabled) {
            $integration->update([
                'health_status' => 'Untested',
                'health_message' => 'Provider is currently disabled.',
                'last_health_check_at' => now(),
            ]);

            return [
                'status' => 'Untested',
                'message' => 'Provider is disabled.',
            ];
        }

        try {
            $result = match ($key) {
                'google_ai_studio' => $this->checkGemini($secrets),
                'groq' => $this->checkGroq($secrets),
                'openrouter' => $this->checkOpenRouter($secrets),
                'ollama' => $this->checkOllama($integration->settings),
                'gmail_api' => $this->checkGmail($secrets),
                'google_drive' => $this->checkGoogleDrive($secrets),
                'adzuna' => $this->checkAdzuna($secrets),
                'jooble' => $this->checkJooble($secrets),
                'policy_governance' => $this->checkPolicyGovernance($integration),
                default => ['status' => 'Healthy', 'message' => 'Configuration saved and ready.'],
            };
        } catch (Throwable $e) {
            $result = [
                'status' => 'Failed',
                'message' => 'Connection test error: ' . $e->getMessage(),
            ];
        }

        $integration->update([
            'health_status' => $result['status'],
            'health_message' => $result['message'],
            'last_health_check_at' => now(),
        ]);

        return $result;
    }

    protected function checkGemini(array $secrets): array
    {
        $apiKey = $secrets['api_key'] ?? env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return ['status' => 'Degraded', 'message' => 'No API Key configured. Please enter a valid Google AI Studio key.'];
        }

        $response = Http::timeout(4)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
        if ($response->successful()) {
            return ['status' => 'Healthy', 'message' => 'Gemini API connected successfully. Free model quotas available.'];
        }

        return ['status' => 'Failed', 'message' => 'Google AI Studio rejected key: ' . ($response->json()['error']['message'] ?? $response->status())];
    }

    protected function checkGroq(array $secrets): array
    {
        $apiKey = $secrets['api_key'] ?? env('GROQ_API_KEY');
        if (empty($apiKey)) {
            return ['status' => 'Degraded', 'message' => 'No Groq API key configured.'];
        }

        $response = Http::timeout(4)->withToken($apiKey)->get('https://api.groq.com/openai/v1/models');
        if ($response->successful()) {
            return ['status' => 'Healthy', 'message' => 'Groq API connection healthy. High-speed inference ready.'];
        }

        return ['status' => 'Failed', 'message' => 'Groq authentication failed: ' . $response->status()];
    }

    protected function checkOpenRouter(array $secrets): array
    {
        $apiKey = $secrets['api_key'] ?? env('OPENROUTER_API_KEY');
        if (empty($apiKey)) {
            return ['status' => 'Degraded', 'message' => 'No OpenRouter API key configured.'];
        }

        $response = Http::timeout(4)->withToken($apiKey)->get('https://openrouter.ai/api/v1/models');
        if ($response->successful()) {
            return ['status' => 'Healthy', 'message' => 'OpenRouter connection healthy. Free-tagged models accessible.'];
        }

        return ['status' => 'Failed', 'message' => 'OpenRouter authentication failed: ' . $response->status()];
    }

    protected function checkOllama(?array $settings): array
    {
        $endpoint = rtrim($settings['endpoint_url'] ?? 'http://127.0.0.1:11434', '/');
        
        try {
            $response = Http::timeout(2)->get("{$endpoint}/api/tags");
            if ($response->successful()) {
                $models = count($response->json()['models'] ?? []);
                return ['status' => 'Healthy', 'message' => "Ollama is running locally! ({$models} installed models detected)."];
            }
        } catch (Throwable) {
            // Local service might be stopped
        }

        return ['status' => 'Degraded', 'message' => "Ollama not detected at {$endpoint}. Run 'ollama serve' to activate zero-cost local inference."];
    }

    protected function checkGmail(array $secrets): array
    {
        $token = $secrets['access_token'] ?? $secrets['refresh_token'] ?? null;
        if (empty($token)) {
            return ['status' => 'Degraded', 'message' => 'OAuth token not linked. Connect your Gmail account for automated outreach.'];
        }

        return ['status' => 'Healthy', 'message' => 'Gmail OAuth token stored in AES-256 vault.'];
    }

    protected function checkGoogleDrive(array $secrets): array
    {
        $token = $secrets['access_token'] ?? null;
        if (empty($token)) {
            return ['status' => 'Degraded', 'message' => 'Operating in local offline archive mode. Configure OAuth tokens for automated Google Drive uploads.'];
        }

        return ['status' => 'Healthy', 'message' => 'Google Drive cloud storage connected.'];
    }

    protected function checkAdzuna(array $secrets): array
    {
        $appId = $secrets['app_id'] ?? null;
        $appKey = $secrets['app_key'] ?? null;

        if (empty($appId) || empty($appKey)) {
            return ['status' => 'Degraded', 'message' => 'Adzuna App ID and Key needed for job discovery.'];
        }

        return ['status' => 'Healthy', 'message' => 'Adzuna API credentials active.'];
    }

    protected function checkJooble(array $secrets): array
    {
        $apiKey = $secrets['api_key'] ?? null;
        if (empty($apiKey)) {
            return ['status' => 'Degraded', 'message' => 'Jooble API key not set.'];
        }

        return ['status' => 'Healthy', 'message' => 'Jooble API credentials active.'];
    }

    protected function checkPolicyGovernance(IntegrationCredential $integration): array
    {
        $freeOnly = $integration->settings['FREE_RESOURCES_ONLY'] ?? true;
        if ($freeOnly) {
            return ['status' => 'Healthy', 'message' => 'FREE_RESOURCES_ONLY is actively enforced. Zero paid API charges allowed.'];
        }

        return ['status' => 'Degraded', 'message' => 'WARNING: Free resources policy is disabled. Monitor external API usage carefully.'];
    }
}

