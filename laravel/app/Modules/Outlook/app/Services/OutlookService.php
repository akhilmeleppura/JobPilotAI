<?php

namespace App\Modules\Outlook\app\Services;

use App\Models\User;
use App\Modules\Outlook\app\Models\OutlookContact;
use App\Modules\Outlook\app\Models\OutlookEvent;
use App\Modules\Outlook\app\Models\OutlookMessage;
use App\Modules\Outlook\app\Models\OutlookTask;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OutlookService
{
    /**
     * Ensure the user has starter Outlook data populated if new.
     */
    public function ensureStarterData(User $user): void
    {
        if (OutlookMessage::where('user_id', $user->id)->exists()) {
            return;
        }

        $now = Carbon::now();

        // 1. Seed realistic Recruiter & Job search emails
        OutlookMessage::create([
            'user_id' => $user->id,
            'folder' => 'inbox',
            'is_focused' => true,
            'is_read' => false,
            'is_flagged' => true,
            'is_pinned' => true,
            'sender_name' => 'Sarah Jenkins (Google Recruiting)',
            'sender_email' => 'sarah.jenkins@google.com',
            'recipient_name' => $user->name,
            'recipient_email' => $user->email,
            'subject' => 'Invitation to Technical Screen — Senior Full-Stack Engineer @ Google',
            'category' => 'Interview',
            'snippet' => "Hi {$user->name}, thank you for your application. Our engineering team was very impressed by your background. We would like to schedule a 45-minute technical screen...",
            'body_html' => "<div style='font-family: Segoe UI, sans-serif; color: #1e293b; line-height: 1.6;'>
                <p>Hi {$user->name},</p>
                <p>Thank you for submitting your application for the <strong>Senior Full-Stack Engineer</strong> position on the Cloud Platform team at Google.</p>
                <p>Our engineering leads have reviewed your profile and resume assignments, and we are excited to invite you to a <strong>45-minute Technical Screening interview</strong> via Google Meet.</p>
                <div style='background: #f1f5f9; padding: 16px; border-left: 4px solid #0078d4; border-radius: 6px; margin: 16px 0;'>
                    <strong>Interview Details:</strong><br>
                    <strong>Format:</strong> 45-minute Live System Architecture & Coding discussion<br>
                    <strong>Recommended Time:</strong> This Wednesday, 3:00 PM - 3:45 PM UTC<br>
                    <strong>Meeting Link:</strong> <a href='https://meet.google.com/xyz-jobpilot-demo' target='_blank' style='color: #0078d4;'>https://meet.google.com/xyz-jobpilot-demo</a>
                </div>
                <p>Please reply directly to this email confirming your availability or suggesting alternative times.</p>
                <p>Best regards,<br><strong>Sarah Jenkins</strong><br>Technical Talent Acquisition | Google Cloud</p>
            </div>",
            'has_attachments' => true,
            'received_at' => $now->copy()->subHours(2),
        ]);

        OutlookMessage::create([
            'user_id' => $user->id,
            'folder' => 'inbox',
            'is_focused' => true,
            'is_read' => false,
            'is_flagged' => true,
            'is_pinned' => false,
            'sender_name' => 'Marcus Vance (Stripe Talent Lead)',
            'sender_email' => 'marcus.vance@stripe.com',
            'recipient_name' => $user->name,
            'recipient_email' => $user->email,
            'subject' => 'Follow up on your application — Staff Infrastructure Lead',
            'category' => 'Recruiter',
            'snippet' => "Hi {$user->name}, I came across your recent projects in high-availability distributed systems. We have an upcoming Staff Infrastructure opening...",
            'body_html' => "<div style='font-family: Segoe UI, sans-serif; color: #1e293b; line-height: 1.6;'>
                <p>Hi {$user->name},</p>
                <p>I hope your week is off to a great start. I lead talent partnering for Infrastructure Core at Stripe.</p>
                <p>I reviewed your experience with zero-downtime database deployments and scalable pipelines. We are expanding our Payment Reliability group and your background is a strong match.</p>
                <p>Would you have 20 minutes this Thursday for an informal introduction call to discuss the roadmap and team setup?</p>
                <p>Looking forward to connecting,<br><strong>Marcus Vance</strong><br>Staff Technical Recruiter | Stripe</p>
            </div>",
            'has_attachments' => false,
            'received_at' => $now->copy()->subHours(5),
        ]);

        OutlookMessage::create([
            'user_id' => $user->id,
            'folder' => 'inbox',
            'is_focused' => false,
            'is_read' => true,
            'is_flagged' => false,
            'is_pinned' => false,
            'sender_name' => 'GitHub Notifications',
            'sender_email' => 'notifications@github.com',
            'recipient_name' => $user->name,
            'recipient_email' => $user->email,
            'subject' => '[JobPilotAI] Pull Request #14 merged: TiDB Serverless cluster integration',
            'category' => 'General',
            'snippet' => "akhilmeleppura merged commit 7651517 into main. All continuous deployment checks passed successfully...",
            'body_html' => "<div style='font-family: Segoe UI, sans-serif; color: #1e293b; line-height: 1.6;'>
                <p>Hello {$user->name},</p>
                <p>Pull Request <strong>#14: TiDB Serverless cluster integration & dynamic allowance</strong> was merged into <code>main</code>.</p>
                <p>Status: All automated Render production checks passed (HTTP 200 OK across all endpoints).</p>
            </div>",
            'has_attachments' => false,
            'received_at' => $now->copy()->subDays(1),
        ]);

        OutlookMessage::create([
            'user_id' => $user->id,
            'folder' => 'sent',
            'is_focused' => true,
            'is_read' => true,
            'is_flagged' => false,
            'is_pinned' => false,
            'sender_name' => $user->name,
            'sender_email' => $user->email,
            'recipient_name' => 'Elena Rostova (Meta)',
            'recipient_email' => 'elena.rostova@meta.com',
            'subject' => 'Application & Tailored CV: Senior Distributed Systems Engineer — Alex',
            'category' => 'Recruiter',
            'snippet' => "Dear Elena, following our conversation regarding the Distributed Systems Engineering position at Meta, please find my tailored CV attached...",
            'body_html' => "<div style='font-family: Segoe UI, sans-serif; color: #1e293b; line-height: 1.6;'>
                <p>Dear Elena,</p>
                <p>Following our conversation regarding the Distributed Systems Engineering position at Meta, please find my tailored CV and system architecture project summary attached.</p>
                <p>I look forward to discussing how my background in multi-region replication and resilient cloud scaling can contribute to Meta's infrastructure initiatives.</p>
                <p>Warm regards,<br>{$user->name}</p>
            </div>",
            'has_attachments' => true,
            'received_at' => $now->copy()->subDays(2),
        ]);

        // 2. Seed realistic Calendar Events (Interviews & Syncs)
        OutlookEvent::create([
            'user_id' => $user->id,
            'title' => 'Google Cloud — Technical Screen (Coding & System Design)',
            'description' => '45-minute live architecture round with Senior Engineering Lead. Meeting link: https://meet.google.com/xyz-jobpilot-demo',
            'location' => 'Google Meet (Virtual)',
            'meeting_link' => 'https://meet.google.com/xyz-jobpilot-demo',
            'start_time' => $now->copy()->addDays(2)->setTime(15, 0),
            'end_time' => $now->copy()->addDays(2)->setTime(15, 45),
            'is_all_day' => false,
            'category' => 'Interview',
            'color' => '#0078d4', // Outlook Blue
            'company_name' => 'Google',
            'interviewer_name' => 'Sarah Jenkins',
        ]);

        OutlookEvent::create([
            'user_id' => $user->id,
            'title' => 'Stripe — Hiring Manager Chat (Infrastructure)',
            'description' => 'Introductory call with Marcus Vance regarding team setup, scope, and engineering roadmap.',
            'location' => 'Microsoft Teams',
            'meeting_link' => 'https://teams.microsoft.com/l/meetup-join/demo',
            'start_time' => $now->copy()->addDays(3)->setTime(16, 30),
            'end_time' => $now->copy()->addDays(3)->setTime(17, 0),
            'is_all_day' => false,
            'category' => 'Call',
            'color' => '#107c41', // Excel Green
            'company_name' => 'Stripe',
            'interviewer_name' => 'Marcus Vance',
        ]);

        OutlookEvent::create([
            'user_id' => $user->id,
            'title' => 'Follow up on Amazon Staff Architect Application',
            'description' => 'Day 5 reminder: send polite follow-up email if status has not transitioned.',
            'location' => 'JobPilot AI Automated Dispatch',
            'start_time' => $now->copy()->addDays(1)->setTime(10, 0),
            'end_time' => $now->copy()->addDays(1)->setTime(10, 30),
            'is_all_day' => false,
            'category' => 'FollowUp',
            'color' => '#d83b01', // Outlook Amber
            'company_name' => 'Amazon AWS',
            'interviewer_name' => 'Talent Team',
        ]);

        // 3. Seed Recruiter Contacts (Outlook People)
        OutlookContact::create([
            'user_id' => $user->id,
            'name' => 'Sarah Jenkins',
            'email' => 'sarah.jenkins@google.com',
            'company' => 'Google',
            'job_title' => 'Technical Talent Acquisition Lead',
            'phone' => '+1 (650) 253-0000',
            'linkedin_url' => 'https://linkedin.com/in/sarah-jenkins-recruiting-demo',
            'notes' => 'Primary contact for Google Cloud Platform engineering roles. Prefers morning PST slots.',
        ]);

        OutlookContact::create([
            'user_id' => $user->id,
            'name' => 'Marcus Vance',
            'email' => 'marcus.vance@stripe.com',
            'company' => 'Stripe',
            'job_title' => 'Staff Infrastructure Recruiter',
            'phone' => '+1 (415) 555-0199',
            'linkedin_url' => 'https://linkedin.com/in/marcus-vance-stripe-demo',
            'notes' => 'Very responsive on email. Recommends reviewing Stripe API architecture beforehand.',
        ]);

        OutlookContact::create([
            'user_id' => $user->id,
            'name' => 'Elena Rostova',
            'email' => 'elena.rostova@meta.com',
            'company' => 'Meta',
            'job_title' => 'Principal Engineering Recruiter',
            'phone' => '+1 (650) 543-4800',
            'linkedin_url' => 'https://linkedin.com/in/elena-rostova-meta-demo',
            'notes' => 'Coordinating Menlo Park / London distributed systems headcount.',
        ]);

        // 4. Seed Outlook Tasks (To-Do)
        OutlookTask::create([
            'user_id' => $user->id,
            'title' => 'Review Google Cloud high-availability & distributed storage system concepts',
            'description' => 'Brush up on Spanner, Bigtable, and Raft consensus algorithms before Wednesday interview.',
            'is_completed' => false,
            'is_important' => true,
            'is_my_day' => true,
            'due_date' => $now->copy()->addDays(1),
        ]);

        OutlookTask::create([
            'user_id' => $user->id,
            'title' => 'Send customized thank-you note to Stripe recruiter after intro call',
            'description' => 'Highlight alignment with Payment Reliability roadmap.',
            'is_completed' => false,
            'is_important' => false,
            'is_my_day' => false,
            'due_date' => $now->copy()->addDays(3),
        ]);

        OutlookTask::create([
            'user_id' => $user->id,
            'title' => 'Export updated tailored LaTeX CV from JobPilot CV Studio',
            'description' => 'Target profile: Distributed Systems Architect.',
            'is_completed' => true,
            'is_important' => false,
            'is_my_day' => false,
            'due_date' => $now->copy()->subDays(1),
            'completed_at' => $now->copy()->subDays(1),
        ]);
    }

    /**
     * Generate an automated professional AI response draft to a recruiter email.
     */
    public function generateAIReply(OutlookMessage $message, string $tone = 'accept'): array
    {
        $senderName = explode(' ', $message->sender_name ?? 'Recruiter')[0];

        if ($tone === 'accept') {
            $subject = 'Re: ' . preg_replace('/^Re:\s*/i', '', $message->subject);
            $body = "Hi {$senderName},\n\nThank you so much for the update. I would be delighted to proceed with the technical screening interview.\n\nThe proposed time works perfectly for me. I have reviewed the meeting link and added the discussion to my calendar.\n\nLooking forward to speaking with the team!\n\nBest regards,\n" . auth()->user()->name;
        } elseif ($tone === 'reschedule') {
            $subject = 'Re: ' . preg_replace('/^Re:\s*/i', '', $message->subject);
            $body = "Hi {$senderName},\n\nThank you for reaching out and for this exciting opportunity.\n\nDue to a prior project commitment at that specific hour, would it be possible to connect slightly later that day (e.g. 5:00 PM UTC), or anytime the following afternoon? I am flexible across those windows.\n\nThank you for your understanding, and I look forward to connecting.\n\nBest regards,\n" . auth()->user()->name;
        } else {
            $subject = 'Re: ' . preg_replace('/^Re:\s*/i', '', $message->subject);
            $body = "Hi {$senderName},\n\nThank you for reaching out and sharing these details. I have reviewed the information and would love to learn more about the team's key priorities for this role.\n\nPlease let me know what day and time works best on your calendar for a brief discussion.\n\nWarm regards,\n" . auth()->user()->name;
        }

        return [
            'to' => $message->sender_email,
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
