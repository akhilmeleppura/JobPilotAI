<?php

namespace App\Modules\Email\app\DTOs;

use App\Shared\DTOs\BaseDTO;

class EmailDispatchResultDTO extends BaseDTO
{
    public function __construct(
        public bool $success,
        public ?string $providerMessageId = null,
        public ?string $errorMessage = null,
        public int $recipientsCount = 1
    ) {}
}

