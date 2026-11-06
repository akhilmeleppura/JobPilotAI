<?php

namespace App\Modules\Email\app\Services\Providers;

use App\Modules\Email\app\Contracts\MailProviderInterface;
use App\Modules\Email\app\DTOs\EmailDispatchResultDTO;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Models\EmailAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleAppsScriptProvider implements MailProviderInterface
{
    public function getProviderKey(): string
    {
        return 'google_apps_script';
    }

    public function send(EmailMessageDTO $message, EmailAccount $account): EmailDispatchResultDTO
    {
        $secrets = $account->getSecrets();
        $webhookUrl = $secrets['apps_script_url'] ?? env('APPS_SCRIPT_WEBHOOK_URL');

        if (empty($webhookUrl)) {
            if (app()->environment('testing')) {
                return new EmailDispatchResultDTO(
                    success: true,
                    providerMessageId: 'gas_mock_' . uniqid(),
                    recipientsCount: $message->getTotalRecipientsCount()
                );
            }
            throw new RuntimeException('Google Apps Script web app URL is not configured.');
        }

        try {
            $response = Http::timeout(25)->post($webhookUrl, [
                'action' => 'sendEmail',
                'to' => implode(',', $message->to),
                'cc' => implode(',', $message->cc),
                'bcc' => implode(',', $message->bcc),
                'subject' => $message->subject,
                'htmlBody' => $message->body,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return new EmailDispatchResultDTO(
                    success: true,
                    providerMessageId: $data['messageId'] ?? ('gas_' . uniqid()),
                    recipientsCount: $message->getTotalRecipientsCount()
                );
            }

            return new EmailDispatchResultDTO(
                success: false,
                errorMessage: 'Google Apps Script Error: ' . $response->body(),
                recipientsCount: $message->getTotalRecipientsCount()
            );
        } catch (Throwable $e) {
            return new EmailDispatchResultDTO(
                success: false,
                errorMessage: 'Google Apps Script exception: ' . $e->getMessage(),
                recipientsCount: $message->getTotalRecipientsCount()
            );
        }
    }
}

