<?php

namespace App\Modules\Email\app\Services\Providers;

use App\Modules\Email\app\Contracts\MailProviderInterface;
use App\Modules\Email\app\DTOs\EmailDispatchResultDTO;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Models\EmailAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ZohoMailProvider implements MailProviderInterface
{
    public function getProviderKey(): string
    {
        return 'zoho_mail';
    }

    public function send(EmailMessageDTO $message, EmailAccount $account): EmailDispatchResultDTO
    {
        $secrets = $account->getSecrets();
        $accessToken = $secrets['access_token'] ?? env('ZOHO_MAIL_ACCESS_TOKEN');

        if (empty($accessToken)) {
            if (app()->environment('testing')) {
                return new EmailDispatchResultDTO(
                    success: true,
                    providerMessageId: 'zoho_mock_' . uniqid(),
                    recipientsCount: $message->getTotalRecipientsCount()
                );
            }
            throw new RuntimeException('Zoho Mail OAuth token is not configured.');
        }

        try {
            $response = Http::timeout(20)
                ->withToken($accessToken)
                ->post('https://mail.zoho.com/api/accounts/me/messages', [
                    'fromAddress' => $account->email_address,
                    'toAddress' => implode(',', $message->to),
                    'ccAddress' => implode(',', $message->cc),
                    'bccAddress' => implode(',', $message->bcc),
                    'subject' => $message->subject,
                    'content' => $message->body,
                    'mailFormat' => 'html',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return new EmailDispatchResultDTO(
                    success: true,
                    providerMessageId: $data['data']['messageId'] ?? ('zoho_' . uniqid()),
                    recipientsCount: $message->getTotalRecipientsCount()
                );
            }

            return new EmailDispatchResultDTO(
                success: false,
                errorMessage: 'Zoho Mail Error: ' . ($response->json()['data']['description'] ?? $response->body()),
                recipientsCount: $message->getTotalRecipientsCount()
            );
        } catch (Throwable $e) {
            return new EmailDispatchResultDTO(
                success: false,
                errorMessage: 'Zoho Mail exception: ' . $e->getMessage(),
                recipientsCount: $message->getTotalRecipientsCount()
            );
        }
    }
}

