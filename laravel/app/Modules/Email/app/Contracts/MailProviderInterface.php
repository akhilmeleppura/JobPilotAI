<?php

namespace App\Modules\Email\app\Contracts;

use App\Modules\Email\app\DTOs\EmailDispatchResultDTO;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Models\EmailAccount;

interface MailProviderInterface
{
    /**
     * Get unique provider key ('gmail', 'microsoft_graph', 'log_relay').
     */
    public function getProviderKey(): string;

    /**
     * Send email message via provider API.
     */
    public function send(EmailMessageDTO $message, EmailAccount $account): EmailDispatchResultDTO;
}

