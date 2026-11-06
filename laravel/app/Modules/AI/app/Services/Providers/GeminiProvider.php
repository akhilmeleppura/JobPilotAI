<?php

namespace App\Modules\AI\app\Services\Providers;

use App\Modules\AI\app\Contracts\AIProviderInterface;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\DTOs\AIResponseDTO;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AIProviderInterface
{
    public function getProviderKey(): string
    {
        return 'gemini';
    }

    public function isFreeTierOnly(): bool
    {
        return true;
    }

    public function getAvailableFreeModels(): array
    {
        return [
            'gemini-2.5-flash' => 'Gemini 2.5 Flash (Generous Free Quota)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast Low-Latency)',
            'gemini-1.5-flash-8b' => 'Gemini 1.5 Flash 8B (High Throughput)',
        ];
    }

    public function generate(AIRequestDTO $request, ?string $apiKey = null, ?array $settings = null): AIResponseDTO
    {
        $key = $apiKey ?: env('GEMINI_API_KEY');
        if (empty($key)) {
            throw new RuntimeException('Google AI Studio API Key is not configured.');
        }

        $model = $request->model ?: ($settings['default_model'] ?? 'gemini-2.5-flash');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $contents = [];
        if (!empty($request->systemPrompt)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => "SYSTEM INSTRUCTION:\n" . $request->systemPrompt]],
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'Understood. I will strictly adhere to these instructions.']],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $request->prompt]],
        ];

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $request->temperature,
                'maxOutputTokens' => $request->maxTokens ?? 2048,
            ],
        ];

        $startTime = microtime(true);
        $response = Http::timeout(25)->post($url, $payload);
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            throw new RuntimeException('Gemini API Error: ' . ($response->json()['error']['message'] ?? $response->body()));
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $json['usageMetadata'] ?? [];

        return new AIResponseDTO(
            content: trim($text),
            provider: 'gemini',
            model: $model,
            promptTokens: $usage['promptTokenCount'] ?? 0,
            completionTokens: $usage['candidatesTokenCount'] ?? 0,
            totalTokens: $usage['totalTokenCount'] ?? 0,
            costUsd: 0.000000,
            isFreeTier: true,
            durationMs: $durationMs
        );
    }
}

