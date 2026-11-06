<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Exceptions\QuotaExceededException;
use App\Modules\Email\app\Models\EmailAccount;
use App\Modules\Email\app\Models\EmailLog;
use App\Modules\Email\app\Models\EmailProviderAccount;
use App\Modules\Email\app\Models\EmailProviderAllowance;
use App\Modules\Email\app\Models\EmailProviderError;
use App\Modules\Email\app\Models\EmailProviderUsage;
use App\Modules\Email\app\Services\FreeEmailAllowanceService;
use App\Modules\Email\app\Services\MailAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicFreeEmailAllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FreeEmailAllowanceService $allowanceService;
    protected MailAutomationService $mailService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'candidate.qa@gmail.com',
            'name' => 'Candidate QA',
        ]);

        $this->allowanceService = app(FreeEmailAllowanceService::class);
        $this->mailService = app(MailAutomationService::class);
    }

    /**
     * 1. Test: No fixed 200 quota remains.
     */
    public function test_no_fixed_200_quota_remains(): void
    {
        $account = $this->mailService->getOrCreateDefaultAccount($this->user);
        $this->assertNull($account->daily_cap, 'Default sender account must not have a hardcoded 200 daily cap.');

        $summary = $this->allowanceService->getDashboardSummary($this->user);
        $this->assertArrayNotHasKey('daily_cap', $summary);

        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertDontSee('200/day');
        $response->assertDontSee('200 daily cap');
    }

    /**
     * 2. Test: Gmail measured usage.
     */
    public function test_gmail_measured_usage(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
        ]);

        $this->allowanceService->recordUsage($gmail, 14, 'msg_01', 'SENT');

        $card = $this->allowanceService->getProviderCardData($gmail);
        $this->assertEquals(14, $card['sent_today']);
        $this->assertEquals('LOCALLY_MEASURED', $card['quota_source']);
    }

    /**
     * 3. Test: Gmail unknown remaining quota.
     */
    public function test_gmail_unknown_remaining_quota(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
        ]);

        $card = $this->allowanceService->getProviderCardData($gmail);
        $this->assertTrue($card['is_remaining_unknown'], 'Gmail API must not fabricate an exact remaining quota.');
        $this->assertNull($card['remaining_count']);
        $this->assertEquals('Exact remaining quota unavailable', $card['remaining_display']);
    }

    /**
     * 4. Test: Apps Script actual remaining quota.
     */
    public function test_apps_script_actual_remaining_quota(): void
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

        // Simulate MailApp.getRemainingDailyQuota() returning 92
        $this->allowanceService->syncAppsScriptQuota($gas, 92);

        $card = $this->allowanceService->getProviderCardData($gas);
        $this->assertEquals('PROVIDER_REPORTED', $card['quota_source']);
        $this->assertEquals(92, $card['remaining_count']);
        $this->assertFalse($card['is_remaining_unknown']);
    }

    /**
     * 5. Test: Apps Script quota reaches zero.
     */
    public function test_apps_script_quota_reaches_zero(): void
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

        // Report 0 remaining from provider
        $this->allowanceService->syncAppsScriptQuota($gas, 0);

        $gas->refresh();
        $this->assertTrue($gas->is_paused);
        $this->assertStringContainsString('reported 0 recipients remaining', $gas->pause_reason);

        $check = $this->allowanceService->canSend($gas, 1);
        $this->assertFalse($check['can_send']);
    }

    /**
     * 6. Test: Gmail and Apps Script use same account.
     */
    public function test_gmail_and_apps_script_use_same_account(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => 'shared@gmail.com',
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
        ]);

        $gas = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'google_apps_script',
            'account_email' => 'shared@gmail.com',
            'plan_name' => 'Apps Script Consumer Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
        ]);

        $cardGmail = $this->allowanceService->getProviderCardData($gmail);
        $cardGas = $this->allowanceService->getProviderCardData($gas);

        $this->assertNotNull($cardGmail['shared_pool_warning']);
        $this->assertStringContainsString('Shares underlying Gmail recipient allowance', $cardGmail['shared_pool_warning']);
        $this->assertNotNull($cardGas['shared_pool_warning']);
    }

    /**
     * 7. Test: Outlook quota unavailable.
     */
    public function test_outlook_quota_unavailable(): void
    {
        $outlook = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'outlook_graph',
            'account_email' => 'candidate@outlook.com',
            'plan_name' => 'Outlook.com Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
        ]);

        $card = $this->allowanceService->getProviderCardData($outlook);
        $this->assertTrue($card['is_remaining_unknown']);
        $this->assertEquals('LOCALLY_MEASURED', $card['quota_source']);
        $this->assertEquals('Exact remaining quota unavailable', $card['remaining_display']);
    }

    /**
     * 8. Test: Monthly limit reached.
     */
    public function test_monthly_limit_reached(): void
    {
        $smtp = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'smtp2go',
            'account_email' => 'smtp@domain.test',
            'plan_name' => 'SMTP2GO Free Tier (1,000/mo)',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'PROHIBITED',
            'enabled' => false,
        ]);

        EmailProviderUsage::create([
            'provider_account_id' => $smtp->id,
            'message_id' => 'm_01',
            'recipient_count' => 1000,
            'sent_at' => Carbon::now()->subDays(2),
            'delivery_status' => 'SENT',
        ]);

        $card = $this->allowanceService->getProviderCardData($smtp);
        $this->assertEquals(1000, $card['sent_this_month']);
    }

    /**
     * 9. Test: Hourly limit reached.
     */
    public function test_hourly_limit_reached(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
        ]);

        EmailProviderUsage::create([
            'provider_account_id' => $gmail->id,
            'message_id' => 'msg_hr_1',
            'recipient_count' => 25,
            'sent_at' => Carbon::now()->subMinutes(15),
            'delivery_status' => 'SENT',
        ]);

        $card = $this->allowanceService->getProviderCardData($gmail);
        $this->assertEquals(25, $card['sent_this_hour']);
    }

    /**
     * 10. Test: Provider API failure.
     */
    public function test_provider_api_failure(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'is_paused' => false,
        ]);

        $this->allowanceService->recordError($gmail, '429 Rate limit exceeded: User daily recipient ceiling reached', 'RATE_LIMIT');

        $gmail->refresh();
        $this->assertTrue($gmail->is_paused, 'Account must auto-pause on 429 / Rate Limit error.');
        $this->assertStringContainsString('Rate limit', $gmail->pause_reason);
    }

    /**
     * 11. Test: Unknown provider pricing.
     */
    public function test_unknown_provider_pricing(): void
    {
        $custom = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'unverified_custom_relay',
            'account_email' => 'custom@relay.test',
            'plan_name' => 'Unknown Relay Plan',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'UNKNOWN',
            'enabled' => true,
        ]);

        $check = $this->allowanceService->canSend($custom, 1);
        $this->assertFalse($check['can_send'], 'Unknown provider terms must fail-closed.');
        $this->assertStringContainsString('UNKNOWN', $check['reason']);
    }

    /**
     * 12. Test: Prohibited sender policy.
     */
    public function test_prohibited_sender_policy(): void
    {
        $mailjet = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'mailjet',
            'account_email' => 'dispatch@mailjet.test',
            'plan_name' => 'Mailjet Free Tier',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'PROHIBITED',
            'enabled' => true,
        ]);

        $check = $this->allowanceService->canSend($mailjet, 1);
        $this->assertFalse($check['can_send'], 'PROHIBITED platform policy must block cold job outreach.');
        $this->assertStringContainsString('prohibited', strtolower($check['reason']));
    }

    /**
     * 13. Test: Eligible free provider selection.
     */
    public function test_eligible_free_provider_selection(): void
    {
        // 1. Prohibited account
        EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'mailjet',
            'account_email' => 'prohibited@mailjet.test',
            'plan_name' => 'Mailjet Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'PROHIBITED',
            'enabled' => true,
        ]);

        // 2. Allowed account
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => 'eligible@gmail.com',
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'CONNECTED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
            'is_paused' => false,
        ]);

        $selected = $this->allowanceService->selectEligibleProvider($this->user, 1);
        $this->assertNotNull($selected);
        $this->assertEquals($gmail->id, $selected->id);
    }

    /**
     * 14. Test: Multiple recipient accounting.
     */
    public function test_multiple_recipient_accounting(): void
    {
        $dto = new EmailMessageDTO(
            to: ['rec1@company.com', 'rec2@company.com'],
            subject: 'Application',
            body: 'Hello',
            cc: ['lead@company.com'],
            bcc: ['archive@tracker.com']
        );

        $this->assertEquals(4, $dto->getTotalRecipientsCount(), 'To (2) + CC (1) + BCC (1) must equal 4 recipients.');
    }

    /**
     * 15. Test: Provider connection expired.
     */
    public function test_provider_connection_expired(): void
    {
        $gmail = EmailProviderAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'gmail_api',
            'account_email' => $this->user->email,
            'plan_name' => 'Gmail Personal Free',
            'connection_status' => 'EXPIRED',
            'policy_status' => 'VERIFIED_ALLOWED',
            'enabled' => true,
        ]);

        $check = $this->allowanceService->canSend($gmail, 1);
        $this->assertFalse($check['can_send']);
        $this->assertStringContainsString('EXPIRED', $check['reason']);
    }

    /**
     * 16. Test: Queue pause and recovery.
     */
    public function test_queue_pause_and_recovery(): void
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

        // Paused due to quota = 0
        $this->allowanceService->syncAppsScriptQuota($gas, 0);
        $gas->refresh();
        $this->assertTrue($gas->is_paused);

        // Daily reset occurs, provider returns 100
        $this->allowanceService->syncAppsScriptQuota($gas, 100);
        $gas->refresh();
        $this->assertFalse($gas->is_paused, 'Account must auto-unpause when positive quota is synced.');
        $this->assertNull($gas->pause_reason);
    }

    /**
     * 17. Test: No hidden paid API fallback.
     */
    public function test_no_hidden_paid_api_fallback(): void
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
            'pause_reason' => 'Rate limit exhausted',
        ]);

        $sender = EmailAccount::create([
            'user_id' => $this->user->id,
            'provider_type' => 'Gmail',
            'email_address' => $this->user->email,
            'daily_cap' => null,
            'is_active' => true,
            'last_reset_date' => Carbon::today(),
        ]);

        $message = new EmailMessageDTO(
            to: ['recruiter@enterprise.com'],
            subject: 'Job Application',
            body: 'Dear Recruiter'
        );

        $this->expectException(QuotaExceededException::class);
        $this->mailService->dispatchEmail($this->user, $message, $sender);

        // Assert pending message was preserved with QuotaPaused status
        $this->assertDatabaseHas('email_logs', [
            'user_id' => $this->user->id,
            'status' => 'QuotaPaused',
        ]);
    }
}

