<?php

namespace App\Modules\Email\app\Services;

use App\Models\User;
use App\Modules\Email\app\Models\EmailLog;
use App\Modules\Email\app\Models\EmailProviderAccount;
use App\Modules\Email\app\Models\EmailProviderAllowance;
use App\Modules\Email\app\Models\EmailProviderError;
use App\Modules\Email\app\Models\EmailProviderUsage;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FreeEmailAllowanceService
{
    /**
     * Documented Free Plan Catalog & Policies.
     */
    public const PROVIDER_CATALOG = [
        'gmail_api' => [
            'name' => 'Gmail API',
            'default_plan' => 'Gmail Personal Free',
            'icon' => 'bi-google',
            'allowance_type' => 'daily',
            'documented_limit' => 500, // Documented maximum daily recipient guidance for web/API
            'quota_source' => 'LOCALLY_MEASURED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'reset_window' => 'rolling_24h',
            'reset_description' => '24-hour rolling window from first send',
            'guidance' => 'Google Gmail does not expose a real-time remaining quota API endpoint. Usage is locally tracked against Google recipient guidance.',
            'shares_pool_with' => 'google_apps_script',
        ],
        'google_apps_script' => [
            'name' => 'Google Apps Script',
            'default_plan' => 'Apps Script Consumer Free',
            'icon' => 'bi-file-earmark-code-fill',
            'allowance_type' => 'daily',
            'documented_limit' => 100, // Consumer Gmail MailApp.getRemainingDailyQuota()
            'quota_source' => 'PROVIDER_REPORTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'reset_window' => 'daily',
            'reset_description' => 'Resets ~1:00 AM PST (Google servers)',
            'guidance' => 'Live recipient quota is read directly from MailApp.getRemainingDailyQuota(). Note: Shares underlying Gmail sending pool if using the same Google account.',
            'shares_pool_with' => 'gmail_api',
        ],
        'outlook_graph' => [
            'name' => 'Outlook Graph',
            'default_plan' => 'Outlook.com Personal Free',
            'icon' => 'bi-microsoft',
            'allowance_type' => 'daily',
            'documented_limit' => 300, // Documented recipient guidance for consumer Microsoft accounts
            'quota_source' => 'LOCALLY_MEASURED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'reset_window' => 'rolling_24h',
            'reset_description' => 'Rolling 24-hour window',
            'guidance' => 'Microsoft Graph does not expose an exact live remaining quota for free personal accounts. Usage is locally tracked.',
            'shares_pool_with' => null,
        ],
        'zoho_mail' => [
            'name' => 'Zoho Mail',
            'default_plan' => 'Zoho Forever Free (Webmail)',
            'icon' => 'bi-envelope-at-fill',
            'allowance_type' => 'daily',
            'documented_limit' => 150, // Webmail limit
            'quota_source' => 'LOCALLY_MEASURED',
            'policy_status' => 'REQUIRES_REVIEW',
            'reset_window' => 'daily',
            'reset_description' => 'Midnight local time',
            'guidance' => 'Zoho free accounts restrict SMTP relay. Usage is permitted via web client or authenticated API if configured.',
            'shares_pool_with' => null,
        ],
        'mailjet' => [
            'name' => 'Mailjet',
            'default_plan' => 'Mailjet Free Tier (200/day, 6k/mo)',
            'icon' => 'bi-send-fill',
            'allowance_type' => 'daily',
            'documented_limit' => 200,
            'quota_source' => 'DOCUMENTED_PLAN',
            'policy_status' => 'PROHIBITED',
            'reset_window' => 'daily',
            'reset_description' => 'Midnight UTC',
            'guidance' => 'PROHIBITED for unsolicited HR outreach. Mailjet acceptable-use policy mandates explicit recipient opt-in and unsubscribe mechanisms.',
            'shares_pool_with' => null,
        ],
        'smtp2go' => [
            'name' => 'SMTP2GO',
            'default_plan' => 'SMTP2GO Free Tier (1,000/mo)',
            'icon' => 'bi-cloud-upload-fill',
            'allowance_type' => 'monthly',
            'documented_limit' => 1000,
            'quota_source' => 'DOCUMENTED_PLAN',
            'policy_status' => 'PROHIBITED',
            'reset_window' => 'monthly',
            'reset_description' => '1st of calendar month',
            'guidance' => 'PROHIBITED for cold job applications. Policy strictly prohibits unverified single-opt-in cold emails.',
            'shares_pool_with' => null,
        ],
    ];

    /**
     * Retrieve or seed connected email provider accounts for user.
     */
    public function getConnectedProviderAccounts(User $user): Collection
    {
        $accounts = EmailProviderAccount::where('user_id', $user->id)
            ->with(['allowances', 'errors' => fn ($q) => $q->latest('occurred_at')->limit(3)])
            ->get();

        if ($accounts->isEmpty()) {
            $this->seedInitialProviderAccounts($user);
            $accounts = EmailProviderAccount::where('user_id', $user->id)
                ->with(['allowances', 'errors' => fn ($q) => $q->latest('occurred_at')->limit(3)])
                ->get();
        }

        return $accounts;
    }

    /**
     * Compute comprehensive allowance card presentation data for a provider account.
     */
    public function getProviderCardData(EmailProviderAccount $account): array
    {
        $catalog = self::PROVIDER_CATALOG[$account->provider] ?? [
            'name' => $account->providerDisplayName(),
            'default_plan' => $account->plan_name,
            'icon' => $account->providerIcon(),
            'allowance_type' => 'daily',
            'documented_limit' => null,
            'quota_source' => 'UNKNOWN',
            'policy_status' => $account->policy_status,
            'reset_window' => 'daily',
            'reset_description' => 'Midnight UTC',
            'guidance' => 'Custom provider with unknown free-tier terms.',
            'shares_pool_with' => null,
        ];

        // 1. Calculate actual usage from email_provider_usage
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisHour = Carbon::now()->subHour();

        $sentToday = (int) $account->usage()
            ->where('sent_at', '>=', $today)
            ->where('delivery_status', 'SENT')
            ->sum('recipient_count');

        $sentThisMonth = (int) $account->usage()
            ->where('sent_at', '>=', $thisMonth)
            ->where('delivery_status', 'SENT')
            ->sum('recipient_count');

        $sentThisHour = (int) $account->usage()
            ->where('sent_at', '>=', $thisHour)
            ->where('delivery_status', 'SENT')
            ->sum('recipient_count');

        // 2. Resolve Allowance record
        $allowance = $account->allowances()
            ->where('allowance_type', $catalog['allowance_type'])
            ->first();

        $quotaSource = $allowance?->quota_source ?? $catalog['quota_source'];
        $documentedLimit = $allowance?->allowance_limit ?? $catalog['documented_limit'];

        // Determine exact or estimated remaining
        $remainingCount = null;
        $isRemainingUnknown = false;
        $usagePercentage = null;

        if ($quotaSource === 'PROVIDER_REPORTED' && $allowance?->remaining_count !== null) {
            // Directly reported by provider (e.g. Apps Script)
            $remainingCount = $allowance->remaining_count;
            if ($documentedLimit && $documentedLimit > 0) {
                $usagePercentage = min(100, round((($documentedLimit - $remainingCount) / $documentedLimit) * 100, 1));
            }
        } elseif ($quotaSource === 'LOCALLY_MEASURED' || $quotaSource === 'ESTIMATED') {
            // Gmail API / Outlook Graph: We locally measure sent counts.
            // Gmail API does NOT expose exact remaining quota!
            if ($account->provider === 'gmail_api' || $account->provider === 'outlook_graph') {
                $isRemainingUnknown = true;
                $remainingCount = null; // NEVER invent an exact remaining number when provider does not expose it
            } elseif ($documentedLimit) {
                $remainingCount = max(0, $documentedLimit - $sentToday);
                $usagePercentage = min(100, round(($sentToday / $documentedLimit) * 100, 1));
            }
        } elseif ($quotaSource === 'DOCUMENTED_PLAN' && $documentedLimit) {
            $remainingCount = max(0, $documentedLimit - $sentToday);
            $usagePercentage = min(100, round(($sentToday / $documentedLimit) * 100, 1));
        } else {
            $isRemainingUnknown = true;
        }

        // 3. Shared pool detection (e.g. Gmail API + Apps Script using same email)
        $sharedPoolWarning = null;
        if ($catalog['shares_pool_with']) {
            $sharedAccount = EmailProviderAccount::where('user_id', $account->user_id)
                ->where('provider', $catalog['shares_pool_with'])
                ->where('account_email', $account->account_email)
                ->where('id', '!=', $account->id)
                ->first();

            if ($sharedAccount) {
                $otherName = self::PROVIDER_CATALOG[$catalog['shares_pool_with']]['name'] ?? $catalog['shares_pool_with'];
                $sharedPoolWarning = "Shares underlying Gmail recipient allowance with {$otherName} ({$account->account_email}). Allowances are not additive.";
            }
        }

        // 4. Last successful send & latest error
        $lastSend = $account->usage()
            ->where('delivery_status', 'SENT')
            ->latest('sent_at')
            ->first();

        $latestError = $account->errors()->latest('occurred_at')->first();

        // 5. Source badge config
        $sourceBadge = match ($quotaSource) {
            'PROVIDER_REPORTED' => ['label' => 'PROVIDER_REPORTED', 'class' => 'bg-success-subtle text-success border border-success-subtle'],
            'DOCUMENTED_PLAN' => ['label' => 'DOCUMENTED_PLAN', 'class' => 'bg-info-subtle text-info border border-info-subtle'],
            'LOCALLY_MEASURED' => ['label' => 'LOCALLY_MEASURED', 'class' => 'bg-primary-subtle text-primary border border-primary-subtle'],
            'ESTIMATED' => ['label' => 'ESTIMATED', 'class' => 'bg-warning-subtle text-warning border border-warning-subtle'],
            default => ['label' => 'UNKNOWN', 'class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle'],
        };

        // 6. Policy Badge
        $policyBadge = match ($account->policy_status) {
            'VERIFIED_ALLOWED' => ['label' => 'VERIFIED_ALLOWED', 'class' => 'bg-success text-white', 'icon' => 'bi-shield-check'],
            'REQUIRES_REVIEW' => ['label' => 'REQUIRES_REVIEW', 'class' => 'bg-warning text-dark', 'icon' => 'bi-exclamation-circle'],
            'PROHIBITED' => ['label' => 'PROHIBITED', 'class' => 'bg-danger text-white', 'icon' => 'bi-shield-slash'],
            default => ['label' => 'UNKNOWN', 'class' => 'bg-secondary text-white', 'icon' => 'bi-question-circle'],
        };

        return [
            'id' => $account->id,
            'provider' => $account->provider,
            'provider_name' => $catalog['name'],
            'icon' => $catalog['icon'],
            'account_email' => $account->account_email,
            'plan_name' => $account->plan_name,
            'connection_status' => $account->connection_status,
            'policy_status' => $account->policy_status,
            'policy_badge' => $policyBadge,
            'enabled' => $account->enabled,
            'is_paused' => $account->is_paused,
            'pause_reason' => $account->pause_reason,
            'guidance' => $catalog['guidance'],
            'allowance_type' => $catalog['allowance_type'],
            'documented_limit' => $documentedLimit,
            'quota_source' => $quotaSource,
            'source_badge' => $sourceBadge,
            'sent_today' => $sentToday,
            'sent_this_month' => $sentThisMonth,
            'sent_this_hour' => $sentThisHour,
            'remaining_count' => $remainingCount,
            'is_remaining_unknown' => $isRemainingUnknown,
            'remaining_display' => $isRemainingUnknown ? 'Exact remaining quota unavailable' : number_format($remainingCount) . ' available',
            'usage_percentage' => $usagePercentage,
            'reset_description' => $catalog['reset_description'],
            'shared_pool_warning' => $sharedPoolWarning,
            'last_successful_send' => $lastSend ? $lastSend->sent_at->diffForHumans() : 'No activity today',
            'latest_error' => $latestError ? "{$latestError->error_code}: {$latestError->error_message} (" . $latestError->occurred_at->diffForHumans() . ")" : null,
            'custom_safety_cap' => $account->custom_safety_cap,
        ];
    }

    /**
     * Compute top-level summary metrics for dashboard cockpit.
     */
    public function getDashboardSummary(User $user): array
    {
        $accounts = $this->getConnectedProviderAccounts($user);
        $cards = $accounts->map(fn ($acc) => $this->getProviderCardData($acc));

        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $accountIds = $accounts->pluck('id');

        $recipientsSentToday = (int) EmailProviderUsage::whereIn('provider_account_id', $accountIds)
            ->where('sent_at', '>=', $today)
            ->where('delivery_status', 'SENT')
            ->sum('recipient_count');

        $recipientsSentThisMonth = (int) EmailProviderUsage::whereIn('provider_account_id', $accountIds)
            ->where('sent_at', '>=', $thisMonth)
            ->where('delivery_status', 'SENT')
            ->sum('recipient_count');

        $pendingEmails = EmailLog::where('user_id', $user->id)
            ->whereIn('status', ['PendingReview', 'Queued', 'QuotaPaused'])
            ->count();

        $failedEmails = EmailProviderUsage::whereIn('provider_account_id', $accountIds)
            ->where('sent_at', '>=', $today)
            ->where('delivery_status', 'FAILED')
            ->count() + EmailProviderError::whereIn('provider_account_id', $accountIds)
            ->where('occurred_at', '>=', $today)
            ->count();

        $eligibleCount = $cards->where('policy_status', 'VERIFIED_ALLOWED')
            ->where('connection_status', 'CONNECTED')
            ->where('enabled', true)
            ->where('is_paused', false)
            ->count();

        $pausedCount = $cards->where('is_paused', true)->count();
        $unknownQuotaCount = $cards->where('is_remaining_unknown', true)->count();

        return [
            'connected_providers' => $accounts->count(),
            'free_providers_eligible' => $eligibleCount,
            'recipients_sent_today' => $recipientsSentToday,
            'recipients_sent_this_month' => $recipientsSentThisMonth,
            'pending_emails' => $pendingEmails,
            'failed_emails' => $failedEmails,
            'providers_paused' => $pausedCount,
            'providers_with_unknown_quota' => $unknownQuotaCount,
            'provider_cards' => $cards,
        ];
    }

    /**
     * Determine whether an email provider account can send another email.
     */
    public function canSend(EmailProviderAccount $account, int $recipientCount = 1): array
    {
        // 1. Connection check
        if ($account->connection_status !== 'CONNECTED') {
            return [
                'can_send' => false,
                'reason' => "Account connection status is {$account->connection_status}.",
            ];
        }

        // 2. Active / Paused check
        if (!$account->enabled || $account->is_paused) {
            return [
                'can_send' => false,
                'reason' => "Provider account is paused or disabled: {$account->pause_reason}",
            ];
        }

        // 3. Policy verification check (Strict Free-Only / Anti-Abuse policy)
        if ($account->policy_status !== 'VERIFIED_ALLOWED') {
            return [
                'can_send' => false,
                'reason' => "Provider policy status is [{$account->policy_status}]. Automated cold recruitment outreach is prohibited on this platform.",
            ];
        }

        // 4. Optional user safety cap check (disabled by default)
        if ($account->custom_safety_cap !== null && $account->custom_safety_cap > 0) {
            $today = Carbon::today();
            $sentToday = (int) $account->usage()
                ->where('sent_at', '>=', $today)
                ->where('delivery_status', 'SENT')
                ->sum('recipient_count');

            if (($sentToday + $recipientCount) > $account->custom_safety_cap) {
                return [
                    'can_send' => false,
                    'reason' => "User custom safety cap of {$account->custom_safety_cap} recipients reached for today ({$sentToday} sent).",
                ];
            }
        }

        // 5. Provider-reported remaining quota check (e.g. Apps Script)
        $allowance = $account->allowances()->where('allowance_type', 'daily')->first();
        if ($allowance && $allowance->quota_source === 'PROVIDER_REPORTED') {
            if ($allowance->remaining_count !== null && $allowance->remaining_count < $recipientCount) {
                return [
                    'can_send' => false,
                    'reason' => "Provider-reported remaining quota is {$allowance->remaining_count}, which is insufficient for {$recipientCount} recipient(s).",
                ];
            }
        }

        return [
            'can_send' => true,
            'reason' => null,
        ];
    }

    /**
     * Automatic selection of eligible free provider account.
     */
    public function selectEligibleProvider(User $user, int $recipientCount = 1): ?EmailProviderAccount
    {
        $accounts = EmailProviderAccount::where('user_id', $user->id)
            ->where('enabled', true)
            ->where('is_paused', false)
            ->where('connection_status', 'CONNECTED')
            ->where('policy_status', 'VERIFIED_ALLOWED')
            ->get();

        foreach ($accounts as $account) {
            $check = $this->canSend($account, $recipientCount);
            if ($check['can_send']) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Record email transmission usage against provider account.
     */
    public function recordUsage(
        EmailProviderAccount $account,
        int $recipientCount,
        ?string $messageId = null,
        string $status = 'SENT',
        ?string $providerResponse = null
    ): EmailProviderUsage {
        $usage = EmailProviderUsage::create([
            'provider_account_id' => $account->id,
            'message_id' => $messageId,
            'recipient_count' => $recipientCount,
            'sent_at' => Carbon::now(),
            'provider_response' => $providerResponse,
            'delivery_status' => $status,
        ]);

        $account->update(['last_sent_at' => Carbon::now()]);

        // Update allowance counters
        $allowance = $account->allowances()->where('allowance_type', 'daily')->first();
        if ($allowance) {
            $allowance->increment('used_count', $recipientCount);
            if ($allowance->remaining_count !== null && $allowance->quota_source === 'PROVIDER_REPORTED') {
                $allowance->decrement('remaining_count', $recipientCount);
                if ($allowance->remaining_count <= 0) {
                    $allowance->update(['status' => 'EXHAUSTED', 'remaining_count' => 0]);
                    $account->update([
                        'is_paused' => true,
                        'pause_reason' => 'Daily provider recipient quota exhausted (0 remaining reported).',
                    ]);
                }
            }
        }

        return $usage;
    }

    /**
     * Record provider error and evaluate whether to pause account.
     */
    public function recordError(
        EmailProviderAccount $account,
        string $errorMessage,
        ?string $errorCode = null
    ): EmailProviderError {
        $error = EmailProviderError::create([
            'provider_account_id' => $account->id,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'occurred_at' => Carbon::now(),
            'retry_status' => 'FAILED',
        ]);

        // Auto-pause if error is rate limit or authentication expired
        if (str_contains(strtolower($errorMessage), 'quota')
            || str_contains(strtolower($errorMessage), 'rate limit')
            || str_contains(strtolower($errorMessage), '429')
            || str_contains(strtolower($errorMessage), 'throttled')) {
            $account->update([
                'is_paused' => true,
                'pause_reason' => "Provider rate limit / quota pause: {$errorMessage}",
            ]);
        } elseif (str_contains(strtolower($errorMessage), 'expired') || str_contains(strtolower($errorMessage), 'unauthorized')) {
            $account->update([
                'connection_status' => 'EXPIRED',
                'is_paused' => true,
                'pause_reason' => 'OAuth token or provider credential expired. Please re-authenticate in Settings.',
            ]);
        }

        return $error;
    }

    /**
     * Sync Apps Script live quota reading from MailApp.getRemainingDailyQuota().
     */
    public function syncAppsScriptQuota(EmailProviderAccount $account, int $remainingQuota): void
    {
        $allowance = EmailProviderAllowance::updateOrCreate(
            [
                'provider_account_id' => $account->id,
                'allowance_type' => 'daily',
            ],
            [
                'allowance_limit' => 100, // Consumer Google Apps Script quota ceiling
                'remaining_count' => max(0, $remainingQuota),
                'quota_source' => 'PROVIDER_REPORTED',
                'measured_at' => Carbon::now(),
                'status' => $remainingQuota <= 0 ? 'EXHAUSTED' : ($remainingQuota <= 10 ? 'WARNING' : 'ACTIVE'),
            ]
        );

        if ($remainingQuota <= 0) {
            $account->update([
                'is_paused' => true,
                'pause_reason' => 'Google Apps Script MailApp.getRemainingDailyQuota() reported 0 recipients remaining.',
            ]);
        } else {
            if ($account->is_paused && str_contains($account->pause_reason ?? '', 'reported 0 recipients')) {
                $account->update([
                    'is_paused' => false,
                    'pause_reason' => null,
                ]);
            }
        }
    }

    /**
     * Seed initial realistic provider accounts for candidate dashboard.
     */
    protected function seedInitialProviderAccounts(User $user): void
    {
        // 1. Gmail API
        $gmail = EmailProviderAccount::create([
            'user_id' => $user->id,
            'provider' => 'gmail_api',
            'account_email' => $user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'is_paused' => false,
            'last_synced_at' => Carbon::now(),
        ]);
        EmailProviderAllowance::create([
            'provider_account_id' => $gmail->id,
            'allowance_type' => 'daily',
            'allowance_limit' => 500,
            'used_count' => 14,
            'remaining_count' => null, // UNKNOWN exact live counter from Gmail API
            'quota_source' => 'LOCALLY_MEASURED',
            'measured_at' => Carbon::now(),
            'status' => 'ACTIVE',
        ]);
        // Seed some sample usage
        EmailProviderUsage::create([
            'provider_account_id' => $gmail->id,
            'message_id' => 'msg_gmail_01',
            'recipient_count' => 14,
            'sent_at' => Carbon::now()->subMinutes(35),
            'delivery_status' => 'SENT',
        ]);

        // 2. Google Apps Script (same account to demonstrate shared pool warning)
        $gas = EmailProviderAccount::create([
            'user_id' => $user->id,
            'provider' => 'google_apps_script',
            'account_email' => $user->email,
            'plan_name' => 'Apps Script Consumer Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'is_paused' => false,
            'last_synced_at' => Carbon::now()->subMinutes(10),
        ]);
        EmailProviderAllowance::create([
            'provider_account_id' => $gas->id,
            'allowance_type' => 'daily',
            'allowance_limit' => 100,
            'used_count' => 8,
            'remaining_count' => 92, // PROVIDER_REPORTED via MailApp.getRemainingDailyQuota()
            'quota_source' => 'PROVIDER_REPORTED',
            'measured_at' => Carbon::now()->subMinutes(10),
            'status' => 'ACTIVE',
        ]);
        EmailProviderUsage::create([
            'provider_account_id' => $gas->id,
            'message_id' => 'msg_gas_01',
            'recipient_count' => 8,
            'sent_at' => Carbon::now()->subHours(2),
            'delivery_status' => 'SENT',
        ]);

        // 3. Outlook Graph
        $outlook = EmailProviderAccount::create([
            'user_id' => $user->id,
            'provider' => 'outlook_graph',
            'account_email' => 'candidate.outreach@outlook.com',
            'plan_name' => 'Outlook.com Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'is_paused' => false,
            'last_synced_at' => Carbon::now()->subMinutes(45),
        ]);
        EmailProviderAllowance::create([
            'provider_account_id' => $outlook->id,
            'allowance_type' => 'daily',
            'allowance_limit' => 300,
            'used_count' => 5,
            'remaining_count' => null, // Microsoft Graph personal doesn't expose live counter
            'quota_source' => 'LOCALLY_MEASURED',
            'measured_at' => Carbon::now(),
            'status' => 'ACTIVE',
        ]);
        EmailProviderUsage::create([
            'provider_account_id' => $outlook->id,
            'message_id' => 'msg_outlook_01',
            'recipient_count' => 5,
            'sent_at' => Carbon::now()->subHours(4),
            'delivery_status' => 'SENT',
        ]);

        // 4. Zoho Mail
        $zoho = EmailProviderAccount::create([
            'user_id' => $user->id,
            'provider' => 'zoho_mail',
            'account_email' => 'career@zohomail.com',
            'plan_name' => 'Zoho Forever Free (Webmail)',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'REQUIRES_REVIEW',
            'enabled' => true,
            'is_paused' => false,
            'last_synced_at' => Carbon::now()->subHours(1),
        ]);
        EmailProviderAllowance::create([
            'provider_account_id' => $zoho->id,
            'allowance_type' => 'daily',
            'allowance_limit' => 150,
            'used_count' => 0,
            'remaining_count' => 150,
            'quota_source' => 'LOCALLY_MEASURED',
            'measured_at' => Carbon::now(),
            'status' => 'ACTIVE',
        ]);

        // 5. Mailjet (Optional Transactional - PROHIBITED for cold job applications)
        $mailjet = EmailProviderAccount::create([
            'user_id' => $user->id,
            'provider' => 'mailjet',
            'account_email' => 'dispatch@mailjet-trial.com',
            'plan_name' => 'Mailjet Free Tier (200/day, 6k/mo)',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'PROHIBITED',
            'enabled' => false,
            'is_paused' => true,
            'pause_reason' => 'PROHIBITED for unsolicited HR outreach. Policy mandates explicit opt-in and unsubscribe link.',
            'last_synced_at' => Carbon::now(),
        ]);
        EmailProviderAllowance::create([
            'provider_account_id' => $mailjet->id,
            'allowance_type' => 'daily',
            'allowance_limit' => 200,
            'used_count' => 0,
            'remaining_count' => 200,
            'quota_source' => 'DOCUMENTED_PLAN',
            'measured_at' => Carbon::now(),
            'status' => 'UNKNOWN',
        ]);
    }
}

