<?php

namespace App\Modules\Integrations\app\Http\Controllers;

use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\Integrations\app\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function __construct(
        protected CredentialVaultService $vaultService,
        protected HealthCheckService $healthService
    ) {}

    /**
     * Display integration cards, secret vault, and health monitors.
     */
    public function index(): View
    {
        $user = Auth::user();
        $integrations = $this->vaultService->getOrSeedIntegrationsForUser($user);

        $grouped = $integrations->groupBy('provider_group');

        $metrics = [
            'total' => $integrations->count(),
            'healthy' => $integrations->where('health_status', 'Healthy')->count(),
            'degraded' => $integrations->where('health_status', 'Degraded')->count(),
            'failed' => $integrations->where('health_status', 'Failed')->count(),
            'free_policy' => $integrations->firstWhere('provider_key', 'policy_governance')?->settings['FREE_RESOURCES_ONLY'] ?? true,
        ];

        return view('integrations::index', compact('grouped', 'metrics'));
    }

    /**
     * Update integration credentials and settings in the encrypted vault.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $integration = IntegrationCredential::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'secrets' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'quota_limit_daily' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['quota_limit_daily'])) {
            $integration->quota_limit_daily = $validated['quota_limit_daily'];
        }

        $this->vaultService->updateIntegration(
            $integration,
            $validated['secrets'] ?? [],
            $validated['settings'] ?? [],
            $request->has('is_enabled') ? $request->boolean('is_enabled') : null
        );

        // Run health check right after credential updates
        $check = $this->healthService->check($integration);

        $msg = "Configuration for '{$integration->provider_name}' saved to AES-256 vault. Health check status: {$check['status']}.";

        return redirect()->route('settings.index')->with('success', $msg);
    }

    /**
     * Run real-time health check test.
     */
    public function ping(int $id): RedirectResponse
    {
        $user = Auth::user();
        $integration = IntegrationCredential::where('user_id', $user->id)->findOrFail($id);

        $result = $this->healthService->check($integration);

        return redirect()->route('settings.index')->with(
            $result['status'] === 'Failed' ? 'error' : 'success',
            "Health Check [{$integration->provider_name}]: {$result['status']} — {$result['message']}"
        );
    }

    /**
     * Toggle enabled status.
     */
    public function toggle(int $id): RedirectResponse
    {
        $user = Auth::user();
        $integration = IntegrationCredential::where('user_id', $user->id)->findOrFail($id);

        $integration->is_enabled = !$integration->is_enabled;
        $integration->save();

        $status = $integration->is_enabled ? 'enabled' : 'disabled';
        return redirect()->route('settings.index')->with('success', "Provider '{$integration->provider_name}' has been {$status}.");
    }
}

