<?php

namespace App\Modules\AI\app\Services\Providers;

use App\Modules\AI\app\Contracts\AIProviderInterface;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\DTOs\AIResponseDTO;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqProvider implements AIProviderInterface
{
    public function getProviderKey(): string
    {
        return 'groq';
    }

    public function isFreeTierOnly(): bool
    {
        return true;
    }

    public function getAvailableFreeModels(): array
    {
        return [
            'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile (Recommended)',
            'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant (Ultra-Fast)',
            'mixtral-8x7b-32768' => 'Mixtral 8x7B (32k Context)',
        ];
    }

    public function generate(AIRequestDTO $request, ?string $apiKey = null, ?array $settings = null): AIResponseDTO
    {
        $key = $apiKey ?: env('GROQ_API_KEY');
        if (empty($key)) {
            throw new RuntimeException('Groq API Key is not configured.');
        }

        $model = $request->model ?: ($settings['default_model'] ?? 'llama-3.3-70b-versatile');

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
        $response = Http::timeout(25)->withToken($key)->post('https://api.groq.com/openai/v1/chat/completions', $payload);
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            throw new RuntimeException('Groq API Error: ' . ($response->json()['error']['message'] ?? $response->body()));
        }

        $json = $response->json();
        $text = $json['choices'][0]['message']['content'] ?? '';
        $usage = $json['usage'] ?? [];

        return new AIResponseDTO(
            content: trim($text),
            provider: 'groq',
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

