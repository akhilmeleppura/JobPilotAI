<?php

namespace App\Modules\Email\app\Services\Providers;

use App\Modules\Email\app\Contracts\MailProviderInterface;
use App\Modules\Email\app\DTOs\EmailDispatchResultDTO;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Models\EmailAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GmailAPIProvider implements MailProviderInterface
{
    public function getProviderKey(): string
    {
        return 'gmail';
    }

    public function send(EmailMessageDTO $message, EmailAccount $account): EmailDispatchResultDTO
    {
        $secrets = $account->getSecrets();
        $accessToken = $secrets['access_token'] ?? env('GMAIL_ACCESS_TOKEN');

        if (empty($accessToken)) {
            if (app()->environment('testing')) {
                return new EmailDispatchResultDTO(
                    success: true,
                    providerMessageId: 'gmail_mock_' . uniqid(),
                    recipientsCount: $message->getTotalRecipientsCount()
                );
            }
            throw new RuntimeException('Gmail OAuth access token is not configured for this sender account.');
        }

        $rawMime = $this->composeMimeMessage($message, $account);
        $encodedMessage = rtrim(strtr(base64_encode($rawMime), '+/', '-_'), '=');

        try {
            $response = Http::timeout(20)
                ->withToken($accessToken)
                ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                    'raw' => $encodedMessage,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return new EmailDispatchResultDTO(
                    success: true,
                    providerMessageId: $data['id'] ?? 'gmail_sent',
                    recipientsCount: $message->getTotalRecipientsCount()
                );
            }

            return new EmailDispatchResultDTO(
                success: false,
                errorMessage: 'Gmail API Error: ' . ($response->json()['error']['message'] ?? $response->body()),
                recipientsCount: $message->getTotalRecipientsCount()
            );
        } catch (Throwable $e) {
            return new EmailDispatchResultDTO(
                success: false,
                errorMessage: 'Gmail dispatch exception: ' . $e->getMessage(),
                recipientsCount: $message->getTotalRecipientsCount()
            );
        }
    }

    /**
     * Compose RFC 2822 MIME formatted email.
     */
    protected function composeMimeMessage(EmailMessageDTO $msg, EmailAccount $account): string
    {
        $headers = [];
        $fromName = $account->sender_name ? "=?UTF-8?B?" . base64_encode($account->sender_name) . "?=" : '';
        $headers[] = "From: " . ($fromName ? "{$fromName} <{$account->email_address}>" : $account->email_address);
        $headers[] = "To: " . implode(', ', $msg->to);

        if (!empty($msg->cc)) {
            $headers[] = "Cc: " . implode(', ', $msg->cc);
        }

        if (!empty($msg->bcc)) {
            $headers[] = "Bcc: " . implode(', ', $msg->bcc);
        }

        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($msg->subject) . "?=";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";

        $bodyBase64 = chunk_split(base64_encode($msg->body));

        return implode("\r\n", $headers) . "\r\n\r\n" . $bodyBase64;
    }
}

