<?php

namespace App\Modules\Email\app\Services\Providers;

use App\Modules\Email\app\Contracts\MailProviderInterface;
use App\Modules\Email\app\DTOs\EmailDispatchResultDTO;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Models\EmailAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class MicrosoftGraphProvider implements MailProviderInterface
{
    public function getProviderKey(): string
    {
        return 'microsoft_graph';
    }

    public function send(EmailMessageDTO $message, EmailAccount $account): EmailDispatchResultDTO
    {
        $secrets = $account->getSecrets();
        $accessToken = $secrets['access_token'] ?? env('MICROSOFT_GRAPH_ACCESS_TOKEN');

        if (empty($accessToken)) {
            // In testing or without token, simulate fallback or mock
            if (app()->environment('testing')) {
                return new EmailDispatchResultDTO(
                    success: true,
                    providerMessageId: 'ms_graph_mock_' . uniqid(),
                    recipientsCount: $message->getTotalRecipientsCount()
                );
            }
            throw new RuntimeException('Microsoft Graph OAuth access token is not configured.');
        }

        $payload = [
            'message' => [
                'subject' => $message->subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $message->body,
                ],
                'toRecipients' => array_map(fn($email) => ['emailAddress' => ['address' => $email]], $message->to),
                'ccRecipients' => array_map(fn($email) => ['emailAddress' => ['address' => $email]], $message->cc),
                'bccRecipients' => array_map(fn($email) => ['emailAddress' => ['address' => $email]], $message->bcc),
            ],
            'saveToSentItems' => 'true',
        ];

        try {
            $response = Http::timeout(20)
                ->withToken($accessToken)
                ->post('https://graph.microsoft.com/v1.0/me/sendMail', $payload);

            if ($response->successful()) {
                return new EmailDispatchResultDTO(
                    success: true,
                    providerMessageId: 'msg_' . uniqid(),
                    recipientsCount: $message->getTotalRecipientsCount()
                );
            }

            return new EmailDispatchResultDTO(
                success: false,
                errorMessage: 'Microsoft Graph Error: ' . ($response->json()['error']['message'] ?? $response->body()),
                recipientsCount: $message->getTotalRecipientsCount()
            );
        } catch (Throwable $e) {
            return new EmailDispatchResultDTO(
                success: false,
                errorMessage: 'Microsoft Graph dispatch exception: ' . $e->getMessage(),
                recipientsCount: $message->getTotalRecipientsCount()
            );
        }
    }
}

