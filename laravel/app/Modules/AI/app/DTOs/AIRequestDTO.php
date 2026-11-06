<?php

namespace App\Modules\AI\app\DTOs;

use App\Shared\DTOs\BaseDTO;

class AIRequestDTO extends BaseDTO
{
    public function __construct(
        public string $prompt,
        public ?string $systemPrompt = null,
        public float $temperature = 0.7,
        public ?int $maxTokens = 2048,
        public ?string $model = null,
        public ?string $preferredProvider = null
    ) {}
}

