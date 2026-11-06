<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Services\ApplicationRegistryService;
use App\Modules\Email\app\DTOs\EmailMessageDTO;
use App\Modules\Email\app\Exceptions\QuotaExceededException;
use App\Modules\Email\app\Models\EmailAccount;
use App\Modules\Email\app\Models\EmailLog;
use App\Modules\Email\app\Services\MailAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_email_studio(): void
    {
        $response = $this->get('/email-studio');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_email_studio_and_auto_seeds_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/email-studio');
        $response->assertStatus(200);
        $response->assertSee('Email Automation');
        $response->assertSee($user->email);

        $this->assertDatabaseHas('email_accounts', [
            'user_id' => $user->id,
            'email_address' => $user->email,
            'daily_cap' => null,
            'is_active' => true,
        ]);
    }

    public function test_user_can_add_custom_sender_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/email-studio/accounts', [
            'email_address' => 'developer.outreach@gmail.com',
            'sender_name' => 'Lead Engineer',
            'provider_type' => 'Gmail',
            'daily_cap' => 150,
            'access_token' => 'ya29.mock_oauth_token',
        ]);

        $response->assertRedirect(route('email.index'));

        $this->assertDatabaseHas('email_accounts', [
            'user_id' => $user->id,
            'email_address' => 'developer.outreach@gmail.com',
            'daily_cap' => 150,
        ]);
    }

    public function test_email_dispatch_increments_quota_by_total_recipients(): void
    {
        $user = User::factory()->create();
        $mailService = app(MailAutomationService::class);
        $account = $mailService->getOrCreateDefaultAccount($user);

        $this->assertEquals(0, $account->sent_today);

        // Send to 1 To + 1 CC + 1 BCC = 3 recipients
        $response = $this->actingAs($user)->post('/email-studio/send', [
            'email_account_id' => $account->id,
            'to' => 'recruiter@acme.com',
            'cc' => 'lead.dev@acme.com',
            'bcc' => 'tracker@jobpilot.test',
            'subject' => 'Application for Backend Role',
            'body' => '<p>Hello, please find my application attached.</p>',
            'require_review' => 0,
        ]);

        $response->assertRedirect(route('email.index'));

        $account->refresh();
        $this->assertEquals(3, $account->sent_today);

        $this->assertDatabaseHas('email_logs', [
            'user_id' => $user->id,
            'email_account_id' => $account->id,
            'subject' => 'Application for Backend Role',
            'status' => 'Sent',
        ]);
    }

    public function test_anti_abuse_guard_rejects_invalid_or_guessed_emails(): void
    {
        $user = User::factory()->create();
        $mailService = app(MailAutomationService::class);
        $account = $mailService->getOrCreateDefaultAccount($user);

        $this->expectException(\InvalidArgumentException::class);

        $msg = new EmailMessageDTO(
            to: ['not-a-valid-email-address'],
            subject: 'Spam test',
            body: 'Hello'
        );

        $mailService->dispatchEmail($user, $msg, $account);
    }

    public function test_daily_quota_exhaustion_halts_dispatch_and_pauses_account(): void
    {
        $user = User::factory()->create();
        $mailService = app(MailAutomationService::class);
        $account = $mailService->getOrCreateDefaultAccount($user);

        // Set account near cap: 199 / 200
        $account->update(['sent_today' => 199, 'daily_cap' => 200]);

        // Attempting to send to 2 recipients will exceed cap (199 + 2 = 201 > 200)
        $this->expectException(QuotaExceededException::class);

        $msg = new EmailMessageDTO(
            to: ['recruiter1@acme.com', 'recruiter2@acme.com'],
            subject: 'Over quota application',
            body: 'Hello'
        );

        $mailService->dispatchEmail($user, $msg, $account);

        $account->refresh();
        $this->assertFalse($account->is_active);
        $this->assertNotNull($account->pause_reason);
    }

    public function test_human_in_the_loop_review_queue_and_approval(): void
    {
        $user = User::factory()->create();

        // Stage email for human review
        $response = $this->actingAs($user)->post('/email-studio/send', [
            'to' => 'talent@uber.com',
            'subject' => 'Application for Senior Software Engineer',
            'body' => '<p>Review preview</p>',
            'require_review' => 1,
        ]);

        $response->assertRedirect(route('email.index'));

        $log = EmailLog::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals('PendingReview', $log->status);
        $this->assertNull($log->sent_at);

        // Approve and dispatch
        $resApprove = $this->actingAs($user)->post("/email-studio/review/{$log->id}/approve");
        $resApprove->assertRedirect(route('email.index'));

        $log->refresh();
        $this->assertEquals('Sent', $log->status);
        $this->assertNotNull($log->sent_at);
    }

    public function test_application_lifecycle_updates_when_email_sent(): void
    {
        $user = User::factory()->create();
        $appService = app(ApplicationRegistryService::class);
        $mailService = app(MailAutomationService::class);

        $application = $appService->stageApplication($user, [
            'company_name' => 'Figma Inc.',
            'job_title' => 'Senior Frontend / Full Stack',
            'location' => 'Remote',
            'apply_method' => 'Email',
            'hr_email' => 'careers@figma.com',
        ]);

        $this->assertEquals('Staged', $application->application_status);

        // Dispatch email linked to this application
        $msg = new EmailMessageDTO(
            to: ['careers@figma.com'],
            subject: 'Application for Senior Frontend',
            body: '<p>Attached is my resume.</p>',
            applicationId: $application->id
        );

        $mailService->dispatchEmail($user, $msg);

        $application->refresh();
        $this->assertEquals('Sent', $application->application_status);
        $this->assertEquals('Sent', $application->email_delivery_status);

        // Verify lifecycle event
        $this->assertDatabaseHas('application_events', [
            'application_id' => $application->id,
            'event_type' => 'EMAIL_SENT',
        ]);
    }
}
