<?php

namespace App\Modules\AI\app\Http\Controllers;

use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\Exceptions\PaidResourceBlockedException;
use App\Modules\AI\app\Models\AIRequestLog;
use App\Modules\AI\app\Services\AIGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class AIController extends Controller
{
    public function __construct(
        protected AIGatewayService $gatewayService
    ) {}

    /**
     * Display AI playground, free provider statuses, and token counters.
     */
    public function index(): View
    {
        $userId = Auth::id();
        $providers = $this->gatewayService->getProviders();

        $logs = AIRequestLog::where('user_id', $userId)
            ->latest()
            ->take(15)
            ->get();

        $metrics = [
            'total_requests' => AIRequestLog::where('user_id', $userId)->count(),
            'total_tokens' => AIRequestLog::where('user_id', $userId)->sum('total_tokens'),
            'total_spend' => 0.00, // Guaranteed 0.00
            'blocked_paid_attempts' => AIRequestLog::where('user_id', $userId)->where('status', 'blocked_by_policy')->count(),
        ];

        return view('ai::index', compact('providers', 'logs', 'metrics'));
    }

    /**
     * Execute test prompt through the free AI gateway.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:5000'],
            'system_prompt' => ['nullable', 'string', 'max:2000'],
            'provider' => ['nullable', 'string', 'in:gemini,groq,openrouter,ollama'],
            'model' => ['nullable', 'string', 'max:100'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
        ]);

        $dto = new AIRequestDTO(
            prompt: $validated['prompt'],
            systemPrompt: $validated['system_prompt'] ?? null,
            temperature: (float) ($validated['temperature'] ?? 0.7),
            model: $validated['model'] ?? null,
            preferredProvider: $validated['provider'] ?? null
        );

        try {
            $response = $this->gatewayService->complete(Auth::user(), $dto);

            return redirect()->route('ai.index')->with([
                'success' => "Response received from {$response->provider} ({$response->model}) in {$response->durationMs}ms!",
                'ai_response' => $response->content,
                'ai_meta' => [
                    'provider' => $response->provider,
                    'model' => $response->model,
                    'tokens' => $response->totalTokens,
                    'cost' => '$0.00 (Free Tier)',
                    'duration' => "{$response->durationMs}ms",
                ],
            ]);
        } catch (PaidResourceBlockedException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'AI Gateway Error: ' . $e->getMessage()]);
        }
    }
}

