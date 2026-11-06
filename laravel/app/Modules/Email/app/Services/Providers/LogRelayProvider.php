<?php

namespace App\Modules\Email\app\Services\Providers;

use App\Modules\Email\app\Contracts\MailProviderInterface;
use App\Modules\Email\app\DTOs\EmailDispatchResultDTO;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Models\EmailAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogRelayProvider implements MailProviderInterface
{
    public function getProviderKey(): string
    {
        return 'log_relay';
    }

    public function send(EmailMessageDTO $message, EmailAccount $account): EmailDispatchResultDTO
    {
        $messageId = 'log_relay_' . Str::uuid();

        Log::info("Email dispatched via LogRelay [{$account->email_address}]", [
            'to' => $message->to,
            'cc' => $message->cc,
            'subject' => $message->subject,
            'message_id' => $messageId,
        ]);

        return new EmailDispatchResultDTO(
            success: true,
            providerMessageId: $messageId,
            recipientsCount: $message->getTotalRecipientsCount()
        );
    }
}

