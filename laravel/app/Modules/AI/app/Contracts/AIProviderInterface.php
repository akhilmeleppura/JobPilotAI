<?php

namespace App\Modules\AI\app\Contracts;

use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\DTOs\AIResponseDTO;

interface AIProviderInterface
{
    /**
     * Get unique provider identifier key (e.g. 'gemini', 'groq', 'openrouter', 'ollama').
     */
    public function getProviderKey(): string;

    /**
     * Determine if this provider adapter operates on free tier only.
     */
    public function isFreeTierOnly(): bool;

    /**
     * Generate text completion using this provider.
     */
    public function generate(AIRequestDTO $request, ?string $apiKey = null, ?array $settings = null): AIResponseDTO;

    /**
     * List verified free-tier models supported by this provider.
     */
    public function getAvailableFreeModels(): array;
}

