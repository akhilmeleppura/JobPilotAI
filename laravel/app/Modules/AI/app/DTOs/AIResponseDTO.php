<?php

namespace App\Modules\AI\app\DTOs;

use App\Shared\DTOs\BaseDTO;

class AIResponseDTO extends BaseDTO
{
    public function __construct(
        public string $content,
        public string $provider,
        public string $model,
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $totalTokens = 0,
        public float $costUsd = 0.000000,
        public bool $isFreeTier = true,
        public int $durationMs = 0
    ) {}
}

