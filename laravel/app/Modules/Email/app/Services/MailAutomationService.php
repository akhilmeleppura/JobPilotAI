<?php

namespace App\Modules\Email\app\Services;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Models\ApplicationEvent;
use App\Modules\Email\app\Contracts\MailProviderInterface;
use App\Modules\Email\app\DTOs\EmailDispatchResultDTO;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Exceptions\QuotaExceededException;
use App\Modules\Email\app\Models\EmailAccount;
use App\Modules\Email\app\Models\EmailLog;
use App\Modules\Email\app\Models\EmailProviderAccount;
use App\Modules\Email\app\Services\Providers\GmailAPIProvider;
use App\Modules\Email\app\Services\Providers\LogRelayProvider;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MailAutomationService
{
    /** @var array<string, MailProviderInterface> */
    protected array $providers = [];

    public function __construct(
        GmailAPIProvider $gmailProvider,
        LogRelayProvider $logRelayProvider,
        protected FreeEmailAllowanceService $allowanceService,
        protected AutomaticEmailSwitchingService $switchingService
    ) {
        $this->providers = [
            'gmail' => $gmailProvider,
            'log_relay' => $logRelayProvider,
        ];
    }

    /**
     * Dispatch an outbound email with automatic failover, quota safety, and idempotency protection.
     */
    public function dispatchEmail(User $user, EmailMessageDTO $message, ?EmailAccount $account = null): EmailLog
    {
        // 1. Resolve or seed default sending account
        $sendingAccount = $account ?: $this->getOrCreateDefaultAccount($user);

        if (!$sendingAccount->is_active) {
            throw new RuntimeException("Sender account [{$sendingAccount->email_address}] is currently paused: {$sendingAccount->pause_reason}");
        }

        // 2. Anti-abuse zero-guessed-emails verification
        $this->validateRecipients($message);

        // 3. Dynamic Quota & Policy Check via FreeEmailAllowanceService
        $sendingAccount->resetQuotaIfNewDay();
        $totalRecipients = $message->getTotalRecipientsCount();

        // Check explicit user custom safety cap if configured
        if ($sendingAccount->daily_cap !== null && ($sendingAccount->sent_today + $totalRecipients) > $sendingAccount->daily_cap) {
            $sendingAccount->update([
                'pause_reason' => "Custom safety sending cap reached ({$sendingAccount->sent_today}/{$sendingAccount->daily_cap}).",
                'is_active' => false,
            ]);

            EmailLog::create([
                'user_id' => $user->id,
                'application_id' => $message->applicationId,
                'email_account_id' => $sendingAccount->id,
                'to_recipients' => $message->to,
                'cc_recipients' => $message->cc,
                'bcc_recipients' => $message->bcc,
                'subject' => $message->subject,
                'body_html' => $message->body,
                'status' => 'QuotaPaused',
                'error_message' => "Dispatch halted: Custom safety cap of {$sendingAccount->daily_cap} exceeded.",
            ]);

            throw new QuotaExceededException("Outreach halted. Custom safety cap of {$sendingAccount->daily_cap} reached for {$sendingAccount->email_address}.");
        }

        // 4. Human-in-the-Loop review hold
        if ($message->requireReview) {
            $idempotencyKey = hash('sha256', "{$user->id}:{$message->applicationId}:" . implode(',', $message->to) . ":{$message->subject}");
            return EmailLog::create([
                'user_id' => $user->id,
                'application_id' => $message->applicationId,
                'attempt_uuid' => (string) Str::uuid(),
                'idempotency_key' => $idempotencyKey,
                'email_account_id' => $sendingAccount->id,
                'to_recipients' => $message->to,
                'cc_recipients' => $message->cc,
                'bcc_recipients' => $message->bcc,
                'subject' => $message->subject,
                'body_html' => $message->body,
                'status' => 'PendingReview',
            ]);
        }

        // 5. Send via Automatic Switching & Failover Engine
        return $this->switchingService->dispatchWithFailover($user, $message, $sendingAccount);
    }

    /**
     * Approve and execute a pending review email log.
     */
    public function approveAndSend(EmailLog $log): EmailLog
    {
        if ($log->status !== 'PendingReview') {
            throw new RuntimeException("Email log is in '{$log->status}' status and cannot be approved.");
        }

        $message = new EmailMessageDTO(
            to: $log->to_recipients,
            subject: $log->subject,
            body: $log->body_html,
            cc: $log->cc_recipients ?? [],
            bcc: $log->bcc_recipients ?? [],
            applicationId: $log->application_id
        );

        $log->update(['status' => 'APPROVED']);
        return $this->switchingService->dispatchWithFailover($log->user, $message, $log->account);
    }

    /**
     * Resolve default sender account or seed one with dynamic allowance.
     */
    public function getOrCreateDefaultAccount(User $user): EmailAccount
    {
        $existing = EmailAccount::where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        return EmailAccount::create([
            'user_id' => $user->id,
            'provider_type' => 'Gmail',
            'email_address' => $user->email,
            'sender_name' => $user->name,
            'daily_cap' => null, // Dynamic allowance determined by provider
            'sent_today' => 0,
            'last_reset_date' => Carbon::today(),
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Validate recipients to enforce anti-abuse.
     */
    protected function validateRecipients(EmailMessageDTO $message): void
    {
        if (empty($message->to)) {
            throw new InvalidArgumentException('Email outreach requires at least one primary recipient (To).');
        }

        foreach ($message->to as $recipient) {
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email address '{$recipient}'. Zero-guessed emails policy requires valid recipient addresses.");
            }
        }

        foreach ($message->cc as $cc) {
            if (!filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid CC address '{$cc}'.");
            }
        }
    }
}
