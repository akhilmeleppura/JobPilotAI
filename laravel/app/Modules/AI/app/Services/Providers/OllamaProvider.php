<?php

namespace App\Modules\AI\app\Services\Providers;

use App\Modules\AI\app\Contracts\AIProviderInterface;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\DTOs\AIResponseDTO;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaProvider implements AIProviderInterface
{
    public function getProviderKey(): string
    {
        return 'ollama';
    }

    public function isFreeTierOnly(): bool
    {
        return true;
    }

    public function getAvailableFreeModels(): array
    {
        return [
            'llama3:latest' => 'Llama 3 (Local)',
            'mistral:latest' => 'Mistral (Local)',
            'qwen2.5-coder:latest' => 'Qwen 2.5 Coder (Local)',
            'deepseek-r1:latest' => 'DeepSeek R1 (Local Reasoning)',
        ];
    }

    public function generate(AIRequestDTO $request, ?string $apiKey = null, ?array $settings = null): AIResponseDTO
    {
        $endpoint = rtrim($settings['endpoint_url'] ?? 'http://127.0.0.1:11434', '/');
        $model = $request->model ?: ($settings['default_model'] ?? 'llama3:latest');

        $promptText = '';
        if (!empty($request->systemPrompt)) {
            $promptText .= "[SYSTEM INSTRUCTION]\n{$request->systemPrompt}\n\n";
        }
        $promptText .= "[USER REQUEST]\n{$request->prompt}";

        $payload = [
            'model' => $model,
            'prompt' => $promptText,
            'stream' => false,
            'options' => [
                'temperature' => $request->temperature,
                'num_predict' => $request->maxTokens ?? 2048,
            ],
        ];

        $startTime = microtime(true);
        $response = Http::timeout(30)->post("{$endpoint}/api/generate", $payload);
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            throw new RuntimeException("Local Ollama Error: Service unreachable or returned status {$response->status()}");
        }

        $json = $response->json();
        $text = $json['response'] ?? '';
        $promptEvalCount = $json['prompt_eval_count'] ?? 0;
        $evalCount = $json['eval_count'] ?? 0;

        return new AIResponseDTO(
            content: trim($text),
            provider: 'ollama',
            model: $model,
            promptTokens: $promptEvalCount,
            completionTokens: $evalCount,
            totalTokens: $promptEvalCount + $evalCount,
            costUsd: 0.000000,
            isFreeTier: true,
            durationMs: $durationMs
        );
    }
}

