<?php

namespace App\Modules\Email\app\Http\Controllers;

use App\Modules\Applications\app\Models\Application;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Models\EmailAccount;
use App\Modules\Email\app\Models\EmailLog;
use App\Modules\Email\app\Models\EmailProviderAccount;
use App\Modules\Email\app\Services\AutomaticEmailSwitchingService;
use App\Modules\Email\app\Services\FreeEmailAllowanceService;
use App\Modules\Email\app\Services\MailAutomationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class EmailController extends Controller
{
    public function __construct(
        protected MailAutomationService $mailService,
        protected FreeEmailAllowanceService $allowanceService,
        protected AutomaticEmailSwitchingService $switchingService
    ) {}

    /**
     * Display connected sender accounts, dynamic free provider allowances, and outreach history.
     */
    public function index(): View
    {
        $user = Auth::user();
        $this->mailService->getOrCreateDefaultAccount($user);

        $accounts = EmailAccount::where('user_id', $user->id)
            ->withCount('logs')
            ->get();

        $accounts->each(fn($acc) => $acc->resetQuotaIfNewDay());

        $logs = EmailLog::with(['account', 'application.vacancy'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $pendingReviews = EmailLog::with(['account', 'application.vacancy'])
            ->where('user_id', $user->id)
            ->where('status', 'PendingReview')
            ->latest()
            ->get();

        $emailSummary = $this->allowanceService->getDashboardSummary($user);
        $switchingMonitor = $this->switchingService->getSwitchingMonitorData($user);

        $metrics = [
            'total_sent_today' => $emailSummary['recipients_sent_today'],
            'total_sent_this_month' => $emailSummary['recipients_sent_this_month'],
            'active_accounts' => $accounts->where('is_active', true)->count(),
            'pending_review_count' => $pendingReviews->count(),
            'connected_providers' => $emailSummary['connected_providers'],
            'free_providers_eligible' => $emailSummary['free_providers_eligible'],
        ];

        return view('email::index', compact('accounts', 'logs', 'pendingReviews', 'metrics', 'emailSummary', 'switchingMonitor'));
    }

    /**
     * Store new sender email account without fixed universal limits.
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email_address' => ['required', 'email', 'max:255'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'provider_type' => ['required', 'string', 'in:Gmail,MicrosoftGraph,Zoho,SMTP,LogRelay'],
            'custom_safety_cap' => ['nullable', 'integer', 'min:1'],
            'daily_cap' => ['nullable', 'integer', 'min:1'],
            'access_token' => ['nullable', 'string'],
        ]);

        $customCap = $validated['custom_safety_cap'] ?? $validated['daily_cap'] ?? null;

        $account = EmailAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'email_address' => $validated['email_address'],
            ],
            [
                'sender_name' => $validated['sender_name'] ?? $user->name,
                'provider_type' => $validated['provider_type'],
                'daily_cap' => $customCap,
                'last_reset_date' => Carbon::today(),
                'is_active' => true,
            ]
        );

        if (!empty($validated['access_token'])) {
            $account->setSecrets(['access_token' => $validated['access_token']]);
            $account->save();
        }

        // Also register with dynamic provider accounts table
        $provMap = [
            'Gmail' => 'gmail_api',
            'MicrosoftGraph' => 'outlook_graph',
            'Zoho' => 'zoho_mail',
            'SMTP' => 'smtp2go',
            'LogRelay' => 'gmail_api',
        ];
        $providerKey = $provMap[$validated['provider_type']] ?? 'gmail_api';

        EmailProviderAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $providerKey,
                'account_email' => $validated['email_address'],
            ],
            [
                'plan_name' => $validated['provider_type'] . ' Free Tier',
                'connection_status' => 'CONNECTED',
                'policy_status' => in_array($providerKey, ['mailjet', 'smtp2go']) ? 'PROHIBITED' : ($providerKey === 'zoho_mail' ? 'REQUIRES_REVIEW' : 'VERIFIED_ALLOWED'),
                'enabled' => true,
                'custom_safety_cap' => $customCap,
            ]
        );

        return redirect()->route('email.index')->with('success', "Sender account [{$account->email_address}] connected!");
    }

    /**
     * Toggle active status of a mailbox.
     */
    public function toggleAccount(int $id): RedirectResponse
    {
        $user = Auth::user();
        $account = EmailAccount::where('user_id', $user->id)->findOrFail($id);
        $account->update(['is_active' => !$account->is_active]);

        EmailProviderAccount::where('user_id', $user->id)
            ->where('account_email', $account->email_address)
            ->update([
                'enabled' => $account->is_active,
                'is_paused' => !$account->is_active,
                'pause_reason' => $account->is_active ? null : 'Manually paused by candidate in Email Studio.',
            ]);

        return redirect()->route('email.index')->with('success', "Mailbox [{$account->email_address}] " . ($account->is_active ? 'activated' : 'paused') . '.');
    }

    /**
     * Update automatic email switching settings.
     */
    public function updateSwitchingSettings(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $this->switchingService->updateSettings($user, [
            'automatic_switching' => $request->has('automatic_switching'),
            'provider_mode' => $request->input('provider_mode', 'AUTOMATIC'),
            'allow_technical_failover' => $request->has('allow_technical_failover'),
            'allow_quota_switch' => $request->has('allow_quota_switch'),
        ]);

        return redirect()->back()->with('success', 'Automatic email switching settings updated successfully.');
    }

    /**
     * Update priority order of provider accounts.
     */
    public function updatePriority(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $order = $request->input('order_ids', []);
        if (!empty($order) && is_array($order)) {
            $this->switchingService->updateProviderPriority($user, $order);
        }

        return redirect()->back()->with('success', 'Provider priority hierarchy saved.');
    }

    /**
     * Dispatch an ad-hoc or staged email outreach.
     */
    public function send(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email_account_id' => ['nullable', 'exists:email_accounts,id'],
            'application_id' => ['nullable', 'exists:applications,id'],
            'to' => ['required', 'string'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'require_review' => ['nullable', 'boolean'],
        ]);

        $toList = array_map('trim', explode(',', $validated['to']));
        $ccList = !empty($validated['cc']) ? array_map('trim', explode(',', $validated['cc'])) : [];
        $bccList = !empty($validated['bcc']) ? array_map('trim', explode(',', $validated['bcc'])) : [];

        $account = !empty($validated['email_account_id'])
            ? EmailAccount::where('user_id', $user->id)->findOrFail($validated['email_account_id'])
            : null;

        $message = new EmailMessageDTO(
            to: $toList,
            subject: $validated['subject'],
            body: $validated['body'],
            cc: $ccList,
            bcc: $bccList,
            applicationId: !empty($validated['application_id']) ? (int) $validated['application_id'] : null,
            requireReview: (bool) ($validated['require_review'] ?? false)
        );

        try {
            $log = $this->mailService->dispatchEmail($user, $message, $account);

            if ($log->status === 'PendingReview') {
                return redirect()->route('email.index')->with('success', 'Email held in Review Queue for final candidate approval.');
            }

            return redirect()->route('email.index')->with('success', 'Email dispatched successfully via failover mailer.');
        } catch (Throwable $e) {
            return redirect()->route('email.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Approve and dispatch a human-reviewed email.
     */
    public function approveReview(int $id): RedirectResponse
    {
        $user = Auth::user();
        $log = EmailLog::where('user_id', $user->id)->findOrFail($id);

        try {
            $this->mailService->approveAndSend($log);
            return redirect()->route('email.index')->with('success', "Outreach approved and dispatched to [{$log->subject}]!");
        } catch (Throwable $e) {
            return redirect()->route('email.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel or discard an outreach review queue entry.
     */
    public function rejectReview(int $id): RedirectResponse
    {
        $user = Auth::user();
        $log = EmailLog::where('user_id', $user->id)->findOrFail($id);

        $log->update([
            'status' => 'Rejected',
            'error_message' => 'Manually dismissed by candidate during review stage.',
        ]);

        return redirect()->route('email.index')->with('success', 'Outreach discarded from review queue.');
    }
}
