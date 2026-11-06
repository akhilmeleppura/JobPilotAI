<?php

namespace App\Modules\Email\app\DTOs;

use App\Shared\DTOs\BaseDTO;

class EmailMessageDTO extends BaseDTO
{
    /**
     * @param array<string> $to
     * @param array<string> $cc
     * @param array<string> $bcc
     * @param array<string> $attachments
     */
    public function __construct(
        public array $to,
        public string $subject,
        public string $body,
        public array $cc = [],
        public array $bcc = [],
        public array $attachments = [],
        public ?int $applicationId = null,
        public bool $requireReview = false
    ) {}

    public function getTotalRecipientsCount(): int
    {
        return count($this->to) + count($this->cc) + count($this->bcc);
    }
}

