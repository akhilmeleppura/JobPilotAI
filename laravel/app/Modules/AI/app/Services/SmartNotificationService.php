<?php

namespace App\Modules\AI\app\Services;

use App\Models\User;
use App\Modules\AI\app\Models\SmartNotification;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Outlook\app\Models\OutlookEvent;
use App\Modules\Outlook\app\Models\OutlookMessage;
use App\Modules\Outlook\app\Models\OutlookTask;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SmartNotificationService
{
    /**
     * Check and generate proactive notifications dynamically for the given user.
     * Uses Outlook emails, events, application statuses, and API key vault health.
     */
    public function syncProactiveNotifications(User $user): void
    {
        try {
            if (!Schema::hasTable('smart_notifications')) {
                return;
            }

            // 1. Check Unread Urgent Emails in Outlook Hub
            if (Schema::hasTable('outlook_messages')) {
                $unreadMessages = OutlookMessage::where('user_id', $user->id)
                    ->where('is_read', false)
                    ->orderBy('received_at', 'desc')
                    ->take(3)
                    ->get();

                foreach ($unreadMessages as $msg) {
                    $sender = $msg->sender_name ?: $msg->sender_email;
                    $title = "New Recruiter/Lead Email: {$sender}";
                    
                    $exists = SmartNotification::where('user_id', $user->id)
                        ->where('title', $title)
                        ->where('created_at', '>=', Carbon::now()->subHours(24))
                        ->exists();

                    if (!$exists) {
                        SmartNotification::create([
                            'user_id' => $user->id,
                            'type' => 'email_unread',
                            'title' => $title,
                            'message' => "Subject: \"{$msg->subject}\" — Pilo recommends reviewing and sending a fast follow-up.",
                            'action_url' => "/outlook?tab=mail&folder=inbox",
                            'action_label' => 'View in Outlook',
                            'icon' => 'bi-envelope-exclamation-fill',
                            'badge_color' => 'warning',
                            'is_read' => false,
                        ]);
                    }
                }
            }

            // 2. Check Upcoming Interviews or Events (next 48 hours)
            if (Schema::hasTable('outlook_events')) {
                $upcomingEvents = OutlookEvent::where('user_id', $user->id)
                    ->where('start_time', '>=', Carbon::now())
                    ->where('start_time', '<=', Carbon::now()->addHours(48))
                    ->get();

                foreach ($upcomingEvents as $evt) {
                    $title = "Upcoming Event: {$evt->title}";
                    $exists = SmartNotification::where('user_id', $user->id)
                        ->where('title', $title)
                        ->where('created_at', '>=', Carbon::now()->subHours(24))
                        ->exists();

                    if (!$exists) {
                        $timeFormatted = Carbon::parse($evt->start_time)->format('M d, g:i A');
                        SmartNotification::create([
                            'user_id' => $user->id,
                            'type' => 'event_reminder',
                            'title' => $title,
                            'message' => "Scheduled for {$timeFormatted} ({$evt->location}). Pilo has your interview prep and CV ready.",
                            'action_url' => "/outlook?tab=calendar",
                            'action_label' => 'View Calendar',
                            'icon' => 'bi-calendar-event-fill',
                            'badge_color' => 'info',
                            'is_read' => false,
                        ]);
                    }
                }
            }

            // 3. Check Pending Application Follow-ups (> 5 days with no response)
            if (Schema::hasTable('applications')) {
                $staleApplications = Application::where('user_id', $user->id)
                    ->whereIn('application_status', ['applied', 'Applied', 'pending', 'Submitted'])
                    ->where('created_at', '<=', Carbon::now()->subDays(5))
                    ->take(2)
                    ->get();

                foreach ($staleApplications as $app) {
                    $jobTitle = $app->registry->job_title ?? 'Software Engineer';
                    $company = $app->registry->company_name ?? 'Target Company';
                    $title = "Follow-up Recommended: {$jobTitle} at {$company}";
                    $exists = SmartNotification::where('user_id', $user->id)
                        ->where('title', $title)
                        ->where('created_at', '>=', Carbon::now()->subDays(3))
                        ->exists();

                    if (!$exists) {
                        SmartNotification::create([
                            'user_id' => $user->id,
                            'type' => 'application_followup',
                            'title' => $title,
                            'message' => "Application submitted {$app->created_at?->diffForHumans()}. Ask Pilo: \"send email follow up for {$company}\".",
                            'action_url' => "/applications",
                            'action_label' => 'Application Registry',
                            'icon' => 'bi-send-check-fill',
                            'badge_color' => 'primary',
                            'is_read' => false,
                        ]);
                    }
                }
            }

            // 4. Check API Vault Setup (Gemini Key missing)
            if (Schema::hasTable('integration_credentials')) {
                $hasGemini = IntegrationCredential::where('user_id', $user->id)
                    ->whereIn('service_key', ['gemini', 'google_ai_studio'])
                    ->where('is_active', true)
                    ->exists();

                if (!$hasGemini) {
                    $title = "Free Gemini AI Key Recommended";
                    $exists = SmartNotification::where('user_id', $user->id)
                        ->where('title', $title)
                        ->exists();

                    if (!$exists) {
                        SmartNotification::create([
                            'user_id' => $user->id,
                            'type' => 'api_setup',
                            'title' => $title,
                            'message' => "Connect your free Gemini 2.5 Flash key in Settings Vault to unlock deep hydraulic reasoning.",
                            'action_url' => "/settings",
                            'action_label' => 'Setup in Vault',
                            'icon' => 'bi-key-fill',
                            'badge_color' => 'success',
                            'is_read' => false,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Smart notification sync suppressed: ' . $e->getMessage());
        }
    }

    /**
     * Get active unread and recent notifications for user.
     */
    public function getNotificationsForUser(User $user, int $limit = 10): array
    {
        $this->syncProactiveNotifications($user);

        if (!Schema::hasTable('smart_notifications')) {
            return [
                'unread_count' => 0,
                'notifications' => [],
            ];
        }

        try {
            $items = SmartNotification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();

            $unreadCount = SmartNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return [
                'unread_count' => $unreadCount,
                'notifications' => $items->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'type' => $n->type,
                        'title' => $n->title,
                        'message' => $n->message,
                        'action_url' => $n->action_url,
                        'action_label' => $n->action_label,
                        'icon' => $n->icon,
                        'badge_color' => $n->badge_color,
                        'is_read' => (bool)$n->is_read,
                        'time_ago' => $n->created_at ? $n->created_at->diffForHumans() : 'just now',
                    ];
                })->all(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Smart notification fetch failed: ' . $e->getMessage());
            return [
                'unread_count' => 0,
                'notifications' => [],
            ];
        }
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(User $user, int $notificationId): bool
    {
        if (!Schema::hasTable('smart_notifications')) {
            return false;
        }

        return (bool) SmartNotification::where('user_id', $user->id)
            ->where('id', $notificationId)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(User $user): int
    {
        if (!Schema::hasTable('smart_notifications')) {
            return 0;
        }

        return SmartNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);
    }
}
