<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Exceptions\QuotaExceededException;
use App\Modules\Email\app\Models\EmailAccount;
use App\Modules\Email\app\Models\EmailLog;
use App\Modules\Email\app\Models\EmailProviderAccount;
use App\Modules\Email\app\Models\EmailProviderAllowance;
use App\Modules\Email\app\Models\EmailProviderSwitch;
use App\Modules\Email\app\Models\EmailSwitchingSetting;
use App\Modules\Email\app\Services\AutomaticEmailSwitchingService;
use App\Modules\Email\app\Services\FreeEmailAllowanceService;
use App\Modules\Email\app\Services\MailAutomationService;
use App\Shared\Services\EmergencyStopService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class AutomaticEmailSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected EmailAccount $emailAccount;
    protected AutomaticEmailSwitchingService $switchingService;
    protected FreeEmailAllowanceService $allowanceService;
    protected MailAutomationService $mailService;
    protected EmergencyStopService $emergencyStop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'candidate.switch@gmail.com',
            'name' => 'Switching QA Candidate',
        ]);

        $this->emailAccount = EmailAccount::create([
            'user_id' => $this->user->id,
            'sender_name' => $this->user->name,
            'email_address' => $this->user->email,
            'provider_type' => 'Gmail API',
            'is_active' => true,
            'last_reset_date' => now()->toDateString(),
        ]);

        $this->allowanceService = app(FreeEmailAllowanceService::class);
        $this->switchingService = app(AutomaticEmailSwitchingService::class);
        $this->mailService = app(MailAutomationService::class);
        $this->emergencyStop = app(EmergencyStopService::class);
    }

    /**
     * 1. Test: Gmail success.
     */
    public function test_gmail_success(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
            'is_primary' => true,
        ]);

        $message = new EmailMessageDTO(
            to: ['recruiter@techcorp.com'],
            subject: 'Senior Developer Role',
            body: 'Dear Recruiter, application enclosed.'
        );

        $log = $this->switchingService->dispatchWithFailover($this->user, $message);

        $this->assertEquals('Sent', $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertEquals(0, $log->switch_count);
    }

    /**
     * 2. Test: Gmail temporary outage.
     */
    public function test_gmail_temporary_outage(): void
    {
        // Primary Gmail is temporarily unavailable
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'DISCONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
            'is_primary' => true,
        ]);

        // Alternative Outlook Graph is ready
        $outlook = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'outlook_graph',
            'account_email' => 'candidate@outlook.com',
            'plan_name' => 'Outlook Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 2,
            'is_primary' => false,
        ]);

        $message = new EmailMessageDTO(
            to: ['hiring@acme.com'],
            subject: 'Full Stack Engineer Application',
            body: 'Application body'
        );

        $log = $this->switchingService->dispatchWithFailover($this->user, $message);

        $this->assertEquals('Sent', $log->status);
        $this->assertEquals(1, $log->switch_count);
        $this->assertEquals('gmail_api', $log->previous_provider);

        // Assert switch was logged
        $this->assertDatabaseHas('email_provider_switches', [
            'user_id' => $this->user->id,
            'previous_provider' => 'gmail_api',
            'new_provider' => 'outlook_graph',
            'result' => 'SWITCHED',
        ]);
    }

    /**
     * 3. Test: Gmail 429 rate limit.
     */
    public function test_gmail_429_rate_limit(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        // Record a 429 error
        $this->allowanceService->recordError($gmail, '429 Rate limit exceeded by Gmail API', '429');

        $gmail->refresh();
        $this->assertTrue($gmail->is_paused);

        $eval = $this->switchingService->evaluateProviderEligibility($gmail);
        $this->assertFalse($eval['is_eligible']);
        $this->assertEquals('QUOTA_EXHAUSTED', $eval['status']);
    }

    /**
     * 4. Test: Gmail daily quota reached.
     */
    public function test_gmail_daily_quota_reached(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'is_paused' => true,
            'pause_reason' => 'Daily quota limit reached',
            'priority' => 1,
        ]);

        $outlook = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'outlook_graph',
            'account_email' => 'alternative@outlook.com',
            'plan_name' => 'Outlook Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 2,
        ]);

        $settings = $this->switchingService->getSettings($this->user);

        // When allow_quota_switch is FALSE (default)
        $settings->update(['allow_quota_switch' => false]);
        $selection = $this->switchingService->selectProviderForOutreach($this->user);
        $this->assertNull($selection['account'], 'Quota switch must be disabled by default without explicit user authorization.');

        // When allow_quota_switch is TRUE
        $settings->update(['allow_quota_switch' => true]);
        $selection = $this->switchingService->selectProviderForOutreach($this->user);
        $this->assertNotNull($selection['account']);
        $this->assertEquals('outlook_graph', $selection['account']->provider);
    }

    /**
     * 5. Test: Apps Script quota reached.
     */
    public function test_apps_script_quota_reached(): void
    {
        $gas = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'google_apps_script',
            'account_email' => $this->user->email,
            'plan_name' => 'Apps Script Consumer Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
        ]);

        $this->allowanceService->syncAppsScriptQuota($gas, 0);

        $eval = $this->switchingService->evaluateProviderEligibility($gas);
        $this->assertFalse($eval['is_eligible']);
        $this->assertEquals('QUOTA_EXHAUSTED', $eval['status']);
    }

    /**
     * 6. Test: Same Gmail connected through two methods.
     */
    public function test_same_gmail_connected_through_two_methods(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => 'samemail@gmail.com',
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'is_paused' => true,
            'pause_reason' => 'Daily quota reached',
            'priority' => 1,
        ]);

        $gas = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'google_apps_script',
            'account_email' => 'samemail@gmail.com', // identical email address!
            'plan_name' => 'Apps Script Consumer Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 2,
        ]);

        $this->switchingService->getSettings($this->user)->update(['allow_quota_switch' => true]);

        // The system must recognize both methods share the underlying Gmail mailbox
        $selection = $this->switchingService->selectProviderForOutreach($this->user);
        $this->assertNull($selection['account'], 'Apps Script on the same Gmail account must NOT be used to evade daily limits.');
    }

    /**
     * 7. Test: Outlook available as independent alternative.
     */
    public function test_outlook_available_as_independent_alternative(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'EXPIRED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        $outlook = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'outlook_graph',
            'account_email' => 'separate.career@outlook.com',
            'plan_name' => 'Outlook Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 2,
        ]);

        $selection = $this->switchingService->selectProviderForOutreach($this->user);
        $this->assertNotNull($selection['account']);
        $this->assertEquals('outlook_graph', $selection['account']->provider);
        $this->assertEquals('separate.career@outlook.com', $selection['account']->account_email);
    }

    /**
     * 8. Test: Alternative provider policy prohibited.
     */
    public function test_alternative_provider_policy_prohibited(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'DISCONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        $mailjet = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'mailjet',
            'account_email' => 'dispatch@mailjet.test',
            'plan_name' => 'Mailjet Free Tier',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'PROHIBITED',
            'enabled' => true,
            'priority' => 2,
        ]);

        $selection = $this->switchingService->selectProviderForOutreach($this->user);
        $this->assertNull($selection['account'], 'Prohibited platforms must be rejected as failover targets.');
    }

    /**
     * 9. Test: Alternative account quota unknown.
     */
    public function test_alternative_account_quota_unknown(): void
    {
        $outlook = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'outlook_graph',
            'account_email' => 'candidate@outlook.com',
            'plan_name' => 'Outlook Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        $eval = $this->switchingService->evaluateProviderEligibility($outlook);
        $this->assertTrue($eval['is_eligible'], 'Outlook Graph personal with unknown remaining quota is eligible under local tracking.');
    }

    /**
     * 10. Test: All providers unavailable.
     */
    public function test_all_providers_unavailable(): void
    {
        EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'connection_status' => 'DISCONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'outlook_graph',
            'account_email' => 'out@outlook.com',
            'connection_status' => 'DISCONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 2,
        ]);

        $message = new EmailMessageDTO(
            to: ['recruiter@allfail.com'],
            subject: 'Application',
            body: 'Hello'
        );

        $this->expectException(QuotaExceededException::class);
        $this->switchingService->dispatchWithFailover($this->user, $message);

        // Verify pending log was safely preserved
        $this->assertDatabaseHas('email_logs', [
            'user_id' => $this->user->id,
            'status' => 'PausedProvider',
        ]);
    }

    /**
     * 11. Test: Paid provider selected.
     */
    public function test_paid_provider_selected(): void
    {
        $paid = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'sendgrid_paid',
            'account_email' => 'paid@sendgrid.com',
            'plan_name' => 'SendGrid Pro Plan',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        $eval = $this->switchingService->evaluateProviderEligibility($paid);
        $this->assertFalse($eval['is_eligible']);
        $this->assertEquals('PAID_PROVIDER_BLOCKED', $eval['status']);
    }

    /**
     * 12. Test: Previous email accepted but response timed out.
     */
    public function test_previous_email_accepted_but_response_timed_out(): void
    {
        $message = new EmailMessageDTO(
            to: ['recruiter@timeout.com'],
            subject: 'Timeout Application',
            body: 'Hello'
        );

        $idempotencyKey = hash('sha256', "{$this->user->id}::" . implode(',', $message->to) . ":{$message->subject}");

        // Previous attempt timed out mid-flight and was set to UNKNOWN
        EmailLog::create([
            'user_id' => $this->user->id,
            'attempt_uuid' => (string) Str::uuid(),
            'idempotency_key' => $idempotencyKey,
            'email_account_id' => $this->emailAccount->id,
            'to_recipients' => $message->to,
            'subject' => $message->subject,
            'body_html' => $message->body,
            'status' => 'UNKNOWN',
            'error_message' => 'Network timeout while awaiting provider acknowledgment.',
        ]);

        // Attempting to send again must halt to prevent sending duplicate email
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/state.*reconciled/i');

        $this->switchingService->dispatchWithFailover($this->user, $message);
    }

    /**
     * 13. Test: Two workers attempting the same email.
     */
    public function test_two_workers_attempting_same_email(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        $message = new EmailMessageDTO(
            to: ['recruiter@concurrency.com'],
            subject: 'Concurrent Dispatch Test',
            body: 'Hello'
        );

        // Worker 1 dispatches email successfully
        $log1 = $this->switchingService->dispatchWithFailover($this->user, $message);
        $this->assertEquals('Sent', $log1->status);

        // Worker 2 attempts dispatch with exact same message
        $log2 = $this->switchingService->dispatchWithFailover($this->user, $message);

        // Worker 2 receives existing log without duplicate transmission
        $this->assertEquals($log1->id, $log2->id);
        $this->assertEquals(1, EmailLog::where('to_recipients', 'like', '%recruiter@concurrency.com%')->count());
    }

    /**
     * 14. Test: Provider switching during queue processing.
     */
    public function test_provider_switching_during_queue_processing(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        $outlook = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'outlook_graph',
            'account_email' => 'fallback@outlook.com',
            'plan_name' => 'Outlook Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 2,
        ]);

        // Message 1 succeeds on Gmail
        $msg1 = new EmailMessageDTO(to: ['queue1@firm.com'], subject: 'App 1', body: 'Body 1');
        $log1 = $this->switchingService->dispatchWithFailover($this->user, $msg1);
        $this->assertEquals('Sent', $log1->status);

        // Gmail experiences technical disconnection mid-queue
        $gmail->update(['connection_status' => 'DISCONNECTED']);

        // Message 2 switches to Outlook automatically
        $msg2 = new EmailMessageDTO(to: ['queue2@firm.com'], subject: 'App 2', body: 'Body 2');
        $log2 = $this->switchingService->dispatchWithFailover($this->user, $msg2);
        $this->assertEquals('Sent', $log2->status);
        $this->assertEquals('gmail_api', $log2->previous_provider);
    }

    /**
     * 15. Test: Global emergency stop activated.
     */
    public function test_global_emergency_stop_activated(): void
    {
        EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'priority' => 1,
        ]);

        // Engage Emergency Stop
        $this->emergencyStop->engage('Candidate initiated emergency freeze');

        $message = new EmailMessageDTO(
            to: ['recruiter@emergency.com'],
            subject: 'Freeze Test',
            body: 'Hello'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Emergency Stop is active/i');

        $this->switchingService->dispatchWithFailover($this->user, $message);
    }
}
