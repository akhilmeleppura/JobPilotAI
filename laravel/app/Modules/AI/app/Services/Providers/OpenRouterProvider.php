<?php

namespace App\Modules\AI\app\Services\Providers;

use App\Modules\AI\app\Contracts\AIProviderInterface;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\DTOs\AIResponseDTO;
use App\Modules\AI\app\Exceptions\PaidResourceBlockedException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterProvider implements AIProviderInterface
{
    public function getProviderKey(): string
    {
        return 'openrouter';
    }

    public function isFreeTierOnly(): bool
    {
        return true;
    }

    public function getAvailableFreeModels(): array
    {
        return [
            'meta-llama/llama-3-8b-instruct:free' => 'Meta Llama 3 8B (Free)',
            'mistralai/mistral-7b-instruct:free' => 'Mistral 7B Instruct (Free)',
            'google/gemma-2-9b-it:free' => 'Google Gemma 2 9B (Free)',
            'qwen/qwen-2.5-7b-instruct:free' => 'Qwen 2.5 7B (Free)',
        ];
    }

    public function generate(AIRequestDTO $request, ?string $apiKey = null, ?array $settings = null): AIResponseDTO
    {
        $key = $apiKey ?: env('OPENROUTER_API_KEY');
        if (empty($key)) {
            throw new RuntimeException('OpenRouter API Key is not configured.');
        }

        $model = $request->model ?: ($settings['default_model'] ?? 'meta-llama/llama-3-8b-instruct:free');

        // Security check: Must end in :free to prevent accidental paid credit deduction
        if (!str_ends_with($model, ':free')) {
            throw new PaidResourceBlockedException("Model '{$model}' is not a verified free model. Only models ending in ':free' are permitted.");
        }

        $messages = [];
        if (!empty($request->systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $request->systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $request->prompt];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens ?? 2048,
        ];

        $startTime = microtime(true);
        $response = Http::timeout(25)
            ->withToken($key)
            ->withHeaders([
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => 'JobPilot AI',
            ])
            ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            throw new RuntimeException('OpenRouter Error: ' . ($response->json()['error']['message'] ?? $response->body()));
        }

        $json = $response->json();
        $text = $json['choices'][0]['message']['content'] ?? '';
        $usage = $json['usage'] ?? [];

        return new AIResponseDTO(
            content: trim($text),
            provider: 'openrouter',
            model: $model,
            promptTokens: $usage['prompt_tokens'] ?? 0,
            completionTokens: $usage['completion_tokens'] ?? 0,
            totalTokens: $usage['total_tokens'] ?? 0,
            costUsd: 0.000000,
            isFreeTier: true,
            durationMs: $durationMs
        );
    }
}

