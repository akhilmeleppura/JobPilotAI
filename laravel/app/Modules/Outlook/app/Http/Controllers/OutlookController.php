<?php

namespace App\Modules\Outlook\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Outlook\app\Models\OutlookContact;
use App\Modules\Outlook\app\Models\OutlookEvent;
use App\Modules\Outlook\app\Models\OutlookMessage;
use App\Modules\Outlook\app\Models\OutlookTask;
use App\Modules\Outlook\app\Services\OutlookService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OutlookController extends Controller
{
    public function __construct(
        protected OutlookService $outlookService
    ) {}

    /**
     * Display the main Outlook Hub UI.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if (!$user) {
            $user = User::first() ?? User::create([
                'name' => 'Alex Candidate',
                'email' => 'candidate@jobpilot.test',
                'password' => bcrypt('Password123!'),
            ]);
        }

        // Auto-seed starter recruiter mail and calendar entries if empty
        $this->outlookService->ensureStarterData($user);

        $currentTab = $request->query('tab', 'mail');
        $currentFolder = $request->query('folder', 'inbox');
        $filter = $request->query('filter', 'all'); // all, focused, other, unread, flagged
        $search = $request->query('search', '');

        // Mail Query
        $mailQuery = OutlookMessage::where('user_id', $user->id)
            ->where('folder', $currentFolder);

        if ($filter === 'focused') {
            $mailQuery->where('is_focused', true);
        } elseif ($filter === 'other') {
            $mailQuery->where('is_focused', false);
        } elseif ($filter === 'unread') {
            $mailQuery->where('is_read', false);
        } elseif ($filter === 'flagged') {
            $mailQuery->where('is_flagged', true);
        }

        if (!empty($search)) {
            $mailQuery->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('sender_email', 'like', "%{$search}%")
                  ->orWhere('snippet', 'like', "%{$search}%");
            });
        }

        $messages = $mailQuery->orderByDesc('is_pinned')
            ->orderByDesc('received_at')
            ->get();

        // Selected message for reading pane
        $selectedId = $request->query('message_id');
        $selectedMessage = null;
        if ($selectedId) {
            $selectedMessage = OutlookMessage::where('user_id', $user->id)->find($selectedId);
            if ($selectedMessage && !$selectedMessage->is_read) {
                $selectedMessage->update(['is_read' => true]);
            }
        }
        if (!$selectedMessage && $messages->isNotEmpty()) {
            $selectedMessage = $messages->first();
            if ($selectedMessage && !$selectedMessage->is_read) {
                $selectedMessage->update(['is_read' => true]);
            }
        }

        // Folder Counts
        $counts = [
            'inbox_unread' => OutlookMessage::where('user_id', $user->id)->where('folder', 'inbox')->where('is_read', false)->count(),
            'inbox_total' => OutlookMessage::where('user_id', $user->id)->where('folder', 'inbox')->count(),
            'sent_total' => OutlookMessage::where('user_id', $user->id)->where('folder', 'sent')->count(),
            'drafts_total' => OutlookMessage::where('user_id', $user->id)->where('folder', 'drafts')->count(),
            'archive_total' => OutlookMessage::where('user_id', $user->id)->where('folder', 'archive')->count(),
            'deleted_total' => OutlookMessage::where('user_id', $user->id)->where('folder', 'deleted')->count(),
        ];

        // Calendar Events
        $events = OutlookEvent::where('user_id', $user->id)
            ->orderBy('start_time')
            ->get();

        // Contacts (People)
        $contacts = OutlookContact::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        // Tasks (To-Do)
        $tasks = OutlookTask::where('user_id', $user->id)
            ->orderByDesc('is_important')
            ->orderBy('is_completed')
            ->orderBy('due_date')
            ->get();

        return view('outlook::index', compact(
            'user',
            'currentTab',
            'currentFolder',
            'filter',
            'search',
            'messages',
            'selectedMessage',
            'counts',
            'events',
            'contacts',
            'tasks'
        ));
    }

    /**
     * Send or draft a new email message.
     */
    public function sendMessage(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        OutlookMessage::create([
            'user_id' => $user->id,
            'folder' => 'sent',
            'is_focused' => true,
            'is_read' => true,
            'is_flagged' => false,
            'sender_name' => $user->name,
            'sender_email' => $user->email,
            'recipient_name' => explode('@', $validated['to'])[0],
            'recipient_email' => $validated['to'],
            'subject' => $validated['subject'],
            'snippet' => substr(strip_tags($validated['body']), 0, 160) . '...',
            'body_html' => nl2br(e($validated['body'])),
            'category' => 'Recruiter',
            'received_at' => Carbon::now(),
        ]);

        return redirect()->route('outlook.index', ['folder' => 'sent'])
            ->with('success', 'Email sent successfully via Outlook Hub.');
    }

    /**
     * Toggle flag on message.
     */
    public function toggleFlag(int $id): JsonResponse
    {
        $message = OutlookMessage::where('user_id', Auth::id())->findOrFail($id);
        $message->update(['is_flagged' => !$message->is_flagged]);

        return response()->json([
            'success' => true,
            'is_flagged' => $message->is_flagged,
        ]);
    }

    /**
     * Toggle read status on message.
     */
    public function toggleRead(int $id): JsonResponse
    {
        $message = OutlookMessage::where('user_id', Auth::id())->findOrFail($id);
        $message->update(['is_read' => !$message->is_read]);

        return response()->json([
            'success' => true,
            'is_read' => $message->is_read,
        ]);
    }

    /**
     * Delete or move message to deleted folder.
     */
    public function deleteMessage(int $id): RedirectResponse
    {
        $message = OutlookMessage::where('user_id', Auth::id())->findOrFail($id);
        if ($message->folder === 'deleted') {
            $message->delete();
        } else {
            $message->update(['folder' => 'deleted']);
        }

        return redirect()->route('outlook.index')
            ->with('success', 'Message moved to Deleted Items.');
    }

    /**
     * Generate an instant AI response draft.
     */
    public function generateAIReply(Request $request, int $id): JsonResponse
    {
        $message = OutlookMessage::where('user_id', Auth::id())->findOrFail($id);
        $tone = $request->query('tone', 'accept');

        $draft = $this->outlookService->generateAIReply($message, $tone);

        return response()->json([
            'success' => true,
            'draft' => $draft,
        ]);
    }

    /**
     * Store new calendar event.
     */
    public function storeEvent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'category' => ['required', 'string'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'interviewer_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $colorMap = [
            'Interview' => '#0078d4',
            'Call' => '#107c41',
            'Deadline' => '#d83b01',
            'FollowUp' => '#5c2d91',
            'Personal' => '#008272',
        ];

        OutlookEvent::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'meeting_link' => $validated['meeting_link'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'category' => $validated['category'],
            'color' => $colorMap[$validated['category']] ?? '#0078d4',
            'company_name' => $validated['company_name'] ?? null,
            'interviewer_name' => $validated['interviewer_name'] ?? null,
        ]);

        return redirect()->route('outlook.index', ['tab' => 'calendar'])
            ->with('success', 'Event scheduled on your Outlook Calendar.');
    }

    /**
     * Delete calendar event.
     */
    public function deleteEvent(int $id): RedirectResponse
    {
        OutlookEvent::where('user_id', Auth::id())->findOrFail($id)->delete();

        return redirect()->route('outlook.index', ['tab' => 'calendar'])
            ->with('success', 'Calendar event removed.');
    }

    /**
     * Store new contact.
     */
    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string'],
        ]);

        OutlookContact::create(array_merge($validated, ['user_id' => Auth::id()]));

        return redirect()->route('outlook.index', ['tab' => 'people'])
            ->with('success', 'Contact saved to Outlook People.');
    }

    /**
     * Delete contact.
     */
    public function deleteContact(int $id): RedirectResponse
    {
        OutlookContact::where('user_id', Auth::id())->findOrFail($id)->delete();

        return redirect()->route('outlook.index', ['tab' => 'people'])
            ->with('success', 'Contact removed from Outlook People.');
    }

    /**
     * Store new task.
     */
    public function storeTask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'is_important' => ['nullable', 'boolean'],
        ]);

        OutlookTask::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'due_date' => $validated['due_date'] ?? null,
            'is_important' => (bool) ($validated['is_important'] ?? false),
            'is_my_day' => true,
        ]);

        return redirect()->route('outlook.index', ['tab' => 'todo'])
            ->with('success', 'Task added to To Do.');
    }

    /**
     * Toggle task completion.
     */
    public function toggleTask(int $id): JsonResponse
    {
        $task = OutlookTask::where('user_id', Auth::id())->findOrFail($id);
        $task->update([
            'is_completed' => !$task->is_completed,
            'completed_at' => !$task->is_completed ? Carbon::now() : null,
        ]);

        return response()->json([
            'success' => true,
            'is_completed' => $task->is_completed,
        ]);
    }

    /**
     * Toggle task importance.
     */
    public function toggleTaskImportant(int $id): JsonResponse
    {
        $task = OutlookTask::where('user_id', Auth::id())->findOrFail($id);
        $task->update(['is_important' => !$task->is_important]);

        return response()->json([
            'success' => true,
            'is_important' => $task->is_important,
        ]);
    }

    /**
     * Delete task.
     */
    public function deleteTask(int $id): RedirectResponse
    {
        OutlookTask::where('user_id', Auth::id())->findOrFail($id)->delete();

        return redirect()->route('outlook.index', ['tab' => 'todo'])
            ->with('success', 'Task deleted.');
    }
}
