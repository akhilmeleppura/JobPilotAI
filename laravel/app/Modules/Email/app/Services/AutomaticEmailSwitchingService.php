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
use App\Modules\Email\app\Models\EmailProviderSwitch;
use App\Modules\Email\app\Models\EmailSwitchingSetting;
use App\Modules\Email\app\Services\Providers\GmailAPIProvider;
use App\Modules\Email\app\Services\Providers\GoogleAppsScriptProvider;
use App\Modules\Email\app\Services\Providers\LogRelayProvider;
use App\Modules\Email\app\Services\Providers\MicrosoftGraphProvider;
use App\Modules\Email\app\Services\Providers\ZohoMailProvider;
use App\Shared\Services\EmergencyStopService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AutomaticEmailSwitchingService
{
    /** @var array<string, MailProviderInterface> */
    protected array $providerAdapters = [];

    public function __construct(
        protected FreeEmailAllowanceService $allowanceService,
        protected EmergencyStopService $emergencyStop,
        GmailAPIProvider $gmailProvider,
        MicrosoftGraphProvider $microsoftProvider,
        GoogleAppsScriptProvider $appsScriptProvider,
        ZohoMailProvider $zohoProvider,
        LogRelayProvider $logRelayProvider
    ) {
        $this->providerAdapters = [
            'gmail_api' => $gmailProvider,
            'gmail' => $gmailProvider,
            'microsoft_graph' => $microsoftProvider,
            'outlook_graph' => $microsoftProvider,
            'google_apps_script' => $appsScriptProvider,
            'zoho_mail' => $zohoProvider,
            'log_relay' => $logRelayProvider,
        ];
    }

    /**
     * Retrieve or seed user switching settings.
     */
    public function getSettings(User $user): EmailSwitchingSetting
    {
        return EmailSwitchingSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'automatic_switching' => true,
                'provider_mode' => 'AUTOMATIC',
                'allow_technical_failover' => true,
                'allow_quota_switch' => false, // strictly disabled by default
                'free_only_mode' => true,
            ]
        );
    }

    /**
     * Update user email switching settings.
     */
    public function updateSettings(User $user, array $data): EmailSwitchingSetting
    {
        $settings = $this->getSettings($user);
        $settings->update([
            'automatic_switching' => (bool) ($data['automatic_switching'] ?? $settings->automatic_switching),
            'provider_mode' => in_array($data['provider_mode'] ?? '', ['MANUAL', 'AUTOMATIC']) ? $data['provider_mode'] : $settings->provider_mode,
            'allow_technical_failover' => (bool) ($data['allow_technical_failover'] ?? $settings->allow_technical_failover),
            'allow_quota_switch' => (bool) ($data['allow_quota_switch'] ?? $settings->allow_quota_switch),
            'free_only_mode' => true, // Always enforced
        ]);

        return $settings;
    }

    /**
     * Reorder provider priority list.
     */
    public function updateProviderPriority(User $user, array $orderedIds): void
    {
        DB::transaction(function () use ($user, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                EmailProviderAccount::where('user_id', $user->id)
                    ->where('id', $id)
                    ->update([
                        'priority' => $index + 1,
                        'is_primary' => $index === 0,
                    ]);
            }
        });
    }

    /**
     * Evaluate comprehensive provider eligibility (Section 2).
     */
    public function evaluateProviderEligibility(EmailProviderAccount $account, int $recipientCount = 1): array
    {
        // 1. Emergency stop check
        if ($this->emergencyStop->isEngaged()) {
            return [
                'status' => 'TEMPORARILY_UNAVAILABLE',
                'is_eligible' => false,
                'reason' => 'Global Emergency Stop active. All outbound mail is paused.',
            ];
        }

        // 2. Free-only policy check
        if (in_array(strtolower($account->provider), ['sendgrid_paid', 'mailgun_paid', 'postmark_paid'])) {
            return [
                'status' => 'PAID_PROVIDER_BLOCKED',
                'is_eligible' => false,
                'reason' => 'Paid provider detected. FREE_RESOURCES_ONLY policy strictly prohibits paid channels.',
            ];
        }

        // 3. Acceptable-use policy verification
        if ($account->policy_status === 'PROHIBITED') {
            return [
                'status' => 'POLICY_NOT_ALLOWED',
                'is_eligible' => false,
                'reason' => "Platform policy prohibits unsolicited recruitment outreach ({$account->providerDisplayName()}).",
            ];
        }

        if ($account->policy_status === 'UNKNOWN' || $account->policy_status === 'REQUIRES_REVIEW') {
            return [
                'status' => 'POLICY_NOT_ALLOWED',
                'is_eligible' => false,
                'reason' => "Provider terms require manual compliance review ({$account->policy_status}).",
            ];
        }

        // 4. Account operational status
        if (!$account->enabled) {
            return [
                'status' => 'TEMPORARILY_UNAVAILABLE',
                'is_eligible' => false,
                'reason' => 'Provider account is manually disabled by candidate.',
            ];
        }

        if ($account->is_paused) {
            $reason = strtolower($account->pause_reason ?? '');
            if (str_contains($reason, 'suspended') || str_contains($reason, 'abuse') || str_contains($reason, 'spam')) {
                return [
                    'status' => 'ACCOUNT_SUSPENDED',
                    'is_eligible' => false,
                    'reason' => "Account suspended or restricted by provider: {$account->pause_reason}",
                ];
            }

            if (str_contains($reason, 'quota') || str_contains($reason, '0 remaining')) {
                return [
                    'status' => 'QUOTA_EXHAUSTED',
                    'is_eligible' => false,
                    'reason' => "Normal daily allowance reached: {$account->pause_reason}",
                ];
            }

            return [
                'status' => 'TEMPORARILY_UNAVAILABLE',
                'is_eligible' => false,
                'reason' => "Provider temporarily paused: {$account->pause_reason}",
            ];
        }

        // 5. Connection & OAuth credentials check
        if ($account->connection_status === 'EXPIRED') {
            return [
                'status' => 'AUTHENTICATION_REQUIRED',
                'is_eligible' => false,
                'reason' => 'OAuth credential or API token expired. Re-authentication required in Settings.',
            ];
        }

        if ($account->connection_status !== 'CONNECTED') {
            return [
                'status' => 'TEMPORARILY_UNAVAILABLE',
                'is_eligible' => false,
                'reason' => "Provider connection status is [{$account->connection_status}].",
            ];
        }

        // 6. Allowance / Quota availability check
        $canSendCheck = $this->allowanceService->canSend($account, $recipientCount);
        if (!$canSendCheck['can_send']) {
            if (str_contains(strtolower($canSendCheck['reason']), 'quota') || str_contains(strtolower($canSendCheck['reason']), 'ceiling')) {
                return [
                    'status' => 'QUOTA_EXHAUSTED',
                    'is_eligible' => false,
                    'reason' => $canSendCheck['reason'],
                ];
            }
            return [
                'status' => 'TEMPORARILY_UNAVAILABLE',
                'is_eligible' => false,
                'reason' => $canSendCheck['reason'],
            ];
        }

        return [
            'status' => 'ELIGIBLE',
            'is_eligible' => true,
            'reason' => 'Provider account is verified, connected, and within free quota limits.',
        ];
    }

    /**
     * Select eligible provider with priority, health verification, and failover support (Sections 1 & 3).
     */
    public function selectProviderForOutreach(
        User $user,
        int $recipientCount = 1,
        ?int $preferredAccountId = null,
        ?string $attemptUuid = null
    ): array {
        $settings = $this->getSettings($user);
        $accounts = EmailProviderAccount::where('user_id', $user->id)
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($accounts->isEmpty()) {
            $this->allowanceService->getConnectedProviderAccounts($user);
            $accounts = EmailProviderAccount::where('user_id', $user->id)
                ->orderBy('priority', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }

        // 1. Manual mode handling
        if ($settings->provider_mode === 'MANUAL' && $preferredAccountId) {
            $preferred = $accounts->firstWhere('id', $preferredAccountId);
            if ($preferred) {
                $eval = $this->evaluateProviderEligibility($preferred, $recipientCount);
                if ($eval['is_eligible']) {
                    return [
                        'account' => $preferred,
                        'was_switched' => false,
                        'reason' => 'Manual candidate selection accepted.',
                    ];
                }

                // If technical failover is disabled, stop immediately
                if (!$settings->allow_technical_failover) {
                    return [
                        'account' => null,
                        'was_switched' => false,
                        'reason' => "Selected provider {$preferred->providerDisplayName()} is not eligible: {$eval['reason']}",
                    ];
                }
            }
        }

        // 2. Automatic selection & failover by priority order
        $primaryAccount = $accounts->first();
        $primaryEval = $primaryAccount ? $this->evaluateProviderEligibility($primaryAccount, $recipientCount) : ['is_eligible' => false, 'status' => 'UNKNOWN'];

        // If primary is eligible, select immediately
        if ($primaryAccount && $primaryEval['is_eligible']) {
            return [
                'account' => $primaryAccount,
                'was_switched' => false,
                'reason' => 'Primary provider selected.',
            ];
        }

        // Primary is unavailable - check switching eligibility
        if (!$settings->automatic_switching) {
            return [
                'account' => null,
                'was_switched' => false,
                'reason' => 'Primary provider unavailable and automatic switching is disabled in Settings.',
            ];
        }

        // Evaluate failover restrictions
        $isTechnicalFailure = in_array($primaryEval['status'], ['TEMPORARILY_UNAVAILABLE', 'AUTHENTICATION_REQUIRED']);
        $isQuotaExhausted = ($primaryEval['status'] === 'QUOTA_EXHAUSTED');

        if ($isTechnicalFailure && !$settings->allow_technical_failover) {
            return [
                'account' => null,
                'was_switched' => false,
                'reason' => 'Technical failover is disabled in candidate settings.',
            ];
        }

        if ($isQuotaExhausted && !$settings->allow_quota_switch) {
            return [
                'account' => null,
                'was_switched' => false,
                'reason' => 'Automatic switching on quota exhaustion is disabled. Pending emails preserved.',
            ];
        }

        // Find next eligible alternative
        foreach ($accounts as $candidate) {
            if ($primaryAccount && $candidate->id === $primaryAccount->id) {
                continue;
            }

            // Anti-Abuse / Anti-Rotation Rules (Section 3):
            // Rule A: Do not rotate multiple Gmail accounts to artificially increase daily capacity
            if ($primaryAccount && $candidate->provider === $primaryAccount->provider && $candidate->account_email !== $primaryAccount->account_email) {
                continue; // Skip multi-account rotation for the same provider
            }

            // Rule B: Google Apps Script sharing same email as Gmail API does not provide additive quota
            if ($primaryAccount && $primaryAccount->provider === 'gmail_api' && $candidate->provider === 'google_apps_script' && $candidate->account_email === $primaryAccount->account_email) {
                if ($isQuotaExhausted) {
                    continue; // Underlying Google account recipient pool is already exhausted
                }
            }

            $candEval = $this->evaluateProviderEligibility($candidate, $recipientCount);
            if ($candEval['is_eligible']) {
                $switchReason = $isQuotaExhausted
                    ? "Primary provider ({$primaryAccount->providerDisplayName()}) quota exhausted. Switched to independent alternative {$candidate->providerDisplayName()}."
                    : "Primary provider ({$primaryAccount->providerDisplayName()}) experienced technical issue: {$primaryEval['reason']}. Failed over to {$candidate->providerDisplayName()}.";

                return [
                    'account' => $candidate,
                    'was_switched' => true,
                    'previous_account' => $primaryAccount,
                    'reason' => $switchReason,
                ];
            }
        }

        return [
            'account' => null,
            'was_switched' => false,
            'reason' => 'All eligible free providers are unavailable. Pending applications have been safely preserved.',
        ];
    }

    /**
     * Dispatch email with strict duplicate prevention and automatic failover (Section 4).
     */
    public function dispatchWithFailover(
        User $user,
        EmailMessageDTO $message,
        ?EmailAccount $fallbackAccount = null
    ): EmailLog {
        // 1. Emergency stop verification
        if ($this->emergencyStop->isEngaged()) {
            throw new RuntimeException('Global Emergency Stop is active. Email outreach is suspended.');
        }

        $totalRecipients = $message->getTotalRecipientsCount();

        // 2. Generate unique idempotency key
        $idempotencyKey = hash('sha256', "{$user->id}:{$message->applicationId}:" . implode(',', $message->to) . ":{$message->subject}");
        $attemptUuid = (string) Str::uuid();

        // 3. Database lock & duplicate check
        return DB::transaction(function () use ($user, $message, $fallbackAccount, $totalRecipients, $idempotencyKey, $attemptUuid) {
            // Check for existing log with this idempotency key
            $existing = EmailLog::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if ($existing) {
                // If previous attempt already succeeded or accepted by provider, do NOT send duplicate!
                if (in_array($existing->status, ['Sent', 'PROVIDER_ACCEPTED'])) {
                    return $existing;
                }

                // If previous attempt is currently SUBMITTING or UNKNOWN, halt to avoid duplicate delivery
                if (in_array($existing->status, ['SUBMITTING', 'UNKNOWN'])) {
                    throw new RuntimeException("Email attempt is in '{$existing->status}' state. Automatic resend is blocked until reconciled.");
                }
            }

            // 4. Select eligible provider
            $selection = $this->selectProviderForOutreach(
                $user,
                $totalRecipients,
                $fallbackAccount?->id,
                $attemptUuid
            );

            if (!$selection['account']) {
                // Preserve pending email log in QuotaPaused / PausedProvider status
                $pausedStatus = str_contains($selection['reason'], 'quota') ? 'QuotaPaused' : 'PausedProvider';

                $resolvedAccountId = $fallbackAccount?->id;
                if (!$resolvedAccountId) {
                    $fallback = EmailAccount::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'sender_name' => $user->name,
                            'email_address' => $user->email,
                            'provider_type' => 'System',
                            'is_active' => true,
                            'last_reset_date' => Carbon::today(),
                        ]
                    );
                    $resolvedAccountId = $fallback->id;
                }

                $log = $existing ?? EmailLog::create([
                    'user_id' => $user->id,
                    'application_id' => $message->applicationId,
                    'attempt_uuid' => $attemptUuid,
                    'idempotency_key' => $idempotencyKey,
                    'email_account_id' => $resolvedAccountId,
                    'to_recipients' => $message->to,
                    'cc_recipients' => $message->cc,
                    'bcc_recipients' => $message->bcc,
                    'subject' => $message->subject,
                    'body_html' => $message->body,
                    'status' => $pausedStatus,
                    'error_message' => $selection['reason'],
                ]);

                throw new QuotaExceededException($selection['reason']);
            }

            /** @var EmailProviderAccount $chosenProviderAccount */
            $chosenProviderAccount = $selection['account'];

            // Map or resolve EmailAccount model for backwards-compatibility
            $emailAccount = EmailAccount::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'email_address' => $chosenProviderAccount->account_email,
                ],
                [
                    'sender_name' => $user->name,
                    'provider_type' => $chosenProviderAccount->providerDisplayName(),
                    'is_active' => true,
                    'last_reset_date' => Carbon::today(),
                ]
            );

            // 5. Create or update log in SUBMITTING state
            $log = $existing ?? EmailLog::create([
                'user_id' => $user->id,
                'application_id' => $message->applicationId,
                'attempt_uuid' => $attemptUuid,
                'idempotency_key' => $idempotencyKey,
                'email_account_id' => $emailAccount->id,
                'to_recipients' => $message->to,
                'cc_recipients' => $message->cc,
                'bcc_recipients' => $message->bcc,
                'subject' => $message->subject,
                'body_html' => $message->body,
                'status' => 'SUBMITTING',
            ]);

            if ($selection['was_switched']) {
                $prevProv = $selection['previous_account']?->provider ?? 'primary';
                $log->update([
                    'previous_provider' => $prevProv,
                    'switch_count' => $log->switch_count + 1,
                ]);

                // Record provider switch in audit log (Section 7)
                $this->logSwitch(
                    user: $user,
                    applicationId: $message->applicationId,
                    emailLogId: $log->id,
                    attemptId: $attemptUuid,
                    previousProvider: $prevProv,
                    newProvider: $chosenProviderAccount->provider,
                    reason: $selection['reason'],
                    quotaSource: $chosenProviderAccount->allowances()->first()?->quota_source ?? 'UNKNOWN',
                    policyStatus: $chosenProviderAccount->policy_status,
                    result: 'SWITCHED'
                );
            }

            // 6. Execute dispatch via provider adapter
            $adapterKey = $chosenProviderAccount->provider;
            $adapter = $this->providerAdapters[$adapterKey] ?? $this->providerAdapters['log_relay'];

            try {
                $result = $adapter->send($message, $emailAccount);
            } catch (Throwable $e) {
                // If timeout or network dropped mid-send, mark UNKNOWN so we never send duplicate blindly
                $isTimeout = str_contains(strtolower($e->getMessage()), 'timeout')
                    || str_contains(strtolower($e->getMessage()), 'timed out');

                $status = $isTimeout ? 'UNKNOWN' : 'FAILED';
                $log->update([
                    'status' => $status,
                    'error_message' => "Dispatch error ({$chosenProviderAccount->provider}): " . $e->getMessage(),
                ]);

                $this->allowanceService->recordError($chosenProviderAccount, $e->getMessage(), 'DISPATCH_EXCEPTION');

                throw new RuntimeException("Outreach failed on {$chosenProviderAccount->providerDisplayName()}: {$e->getMessage()}");
            }

            // 7. Process provider response
            if ($result->success) {
                $log->update([
                    'status' => 'Sent',
                    'provider_message_id' => $result->providerMessageId,
                    'sent_at' => Carbon::now(),
                    'error_message' => null,
                ]);

                $emailAccount->increment('sent_today', $result->recipientsCount);

                // Record usage in dynamic allowance tables
                $this->allowanceService->recordUsage(
                    $chosenProviderAccount,
                    $result->recipientsCount,
                    $result->providerMessageId,
                    'SENT'
                );

                // Update application lifecycle if attached
                if ($message->applicationId) {
                    $application = Application::find($message->applicationId);
                    if ($application) {
                        $application->update([
                            'email_delivery_status' => 'Sent',
                            'application_status' => 'Sent',
                            'sent_at' => Carbon::now(),
                        ]);

                        ApplicationEvent::create([
                            'application_id' => $application->id,
                            'event_type' => 'EMAIL_SENT',
                            'event_payload' => [
                                'sender' => $emailAccount->email_address,
                                'provider' => $chosenProviderAccount->provider,
                                'message_id' => $result->providerMessageId,
                                'recipients_count' => $result->recipientsCount,
                                'was_switched' => $selection['was_switched'],
                            ],
                        ]);
                    }
                }

                return $log;
            }

            // Provider explicitly rejected
            $log->update([
                'status' => 'FAILED',
                'error_message' => $result->errorMessage,
            ]);

            $this->allowanceService->recordError($chosenProviderAccount, $result->errorMessage ?? 'Provider send failed');

            throw new RuntimeException("Dispatch rejected by {$chosenProviderAccount->providerDisplayName()}: {$result->errorMessage}");
        });
    }

    /**
     * Record switch event in audit log (Section 7).
     */
    public function logSwitch(
        User $user,
        ?int $applicationId,
        ?int $emailLogId,
        string $attemptId,
        string $previousProvider,
        string $newProvider,
        string $reason,
        string $quotaSource,
        string $policyStatus,
        string $result = 'SWITCHED',
        ?string $providerResponse = null
    ): EmailProviderSwitch {
        return EmailProviderSwitch::create([
            'user_id' => $user->id,
            'application_id' => $applicationId,
            'email_log_id' => $emailLogId,
            'email_attempt_id' => $attemptId,
            'previous_provider' => $previousProvider,
            'new_provider' => $newProvider,
            'reason' => $reason,
            'quota_source' => $quotaSource,
            'provider_response' => $providerResponse,
            'policy_status' => $policyStatus,
            'result' => $result,
        ]);
    }

    /**
     * Compile monitor and status data for dashboard (Section 5).
     */
    public function getSwitchingMonitorData(User $user): array
    {
        $settings = $this->getSettings($user);
        $accounts = EmailProviderAccount::where('user_id', $user->id)
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($accounts->isEmpty()) {
            $this->allowanceService->getConnectedProviderAccounts($user);
            $accounts = EmailProviderAccount::where('user_id', $user->id)
                ->orderBy('priority', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }

        $primary = $accounts->first();
        $alternative = $accounts->skip(1)->firstWhere(fn($acc) => $acc->isReadyToSend());

        $evaluations = $accounts->map(function ($acc) {
            $eval = $this->evaluateProviderEligibility($acc, 1);
            $card = $this->allowanceService->getProviderCardData($acc);
            return [
                'id' => $acc->id,
                'provider' => $acc->provider,
                'provider_name' => $acc->providerDisplayName(),
                'icon' => $acc->providerIcon(),
                'account_email' => $acc->account_email,
                'priority' => $acc->priority,
                'is_primary' => $acc->is_primary,
                'eligibility_status' => $eval['status'],
                'is_eligible' => $eval['is_eligible'],
                'reason' => $eval['reason'],
                'sent_today' => $card['sent_today'],
                'remaining_display' => $card['remaining_display'],
                'policy_status' => $acc->policy_status,
                'connection_status' => $acc->connection_status,
                'is_paused' => $acc->is_paused,
            ];
        });

        $activeProvider = $primary && $this->evaluateProviderEligibility($primary)['is_eligible']
            ? $primary
            : ($alternative ?: null);

        $history = EmailProviderSwitch::where('user_id', $user->id)
            ->latest('created_at')
            ->limit(10)
            ->get();

        $lastSwitch = $history->first();

        // Notification calculation
        $notification = null;
        if ($primary && !$this->evaluateProviderEligibility($primary)['is_eligible']) {
            if ($alternative) {
                $notification = [
                    'type' => 'warning',
                    'message' => 'Primary provider unavailable. Alternative provider selected successfully.',
                ];
            } else {
                $notification = [
                    'type' => 'danger',
                    'message' => 'All eligible free providers are unavailable. Pending applications have been preserved.',
                ];
            }
        } else {
            $notification = [
                'type' => 'info',
                'message' => 'Auto-Switching Nominal: All free providers operating normally within verified limits.',
            ];
        }

        return [
            'settings' => $settings,
            'active_provider' => $activeProvider ? [
                'name' => $activeProvider->providerDisplayName(),
                'email' => $activeProvider->account_email,
                'provider' => $activeProvider->provider,
                'icon' => $activeProvider->providerIcon(),
            ] : null,
            'primary_provider' => $primary ? [
                'name' => $primary->providerDisplayName(),
                'email' => $primary->account_email,
                'provider' => $primary->provider,
            ] : null,
            'selected_alternative' => $alternative ? [
                'name' => $alternative->providerDisplayName(),
                'email' => $alternative->account_email,
                'provider' => $alternative->provider,
            ] : null,
            'provider_scorecard' => $evaluations,
            'history' => $history,
            'last_switch' => $lastSwitch,
            'notification' => $notification,
        ];
    }
}
