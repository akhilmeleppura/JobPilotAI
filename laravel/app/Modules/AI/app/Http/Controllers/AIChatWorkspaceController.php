<?php

namespace App\Modules\AI\app\Http\Controllers;

use App\Modules\AI\app\Models\AIRequestLog;
use App\Modules\AI\app\Models\ChatSession;
use App\Modules\AI\app\Services\AIChatService;
use App\Modules\AI\app\Services\AIGatewayService;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AIChatWorkspaceController extends Controller
{
    public function __construct(
        protected AIChatService $chatService,
        protected AIGatewayService $gatewayService,
        protected RoleProfileService $profileService,
        protected ResumeService $resumeService
    ) {}

    /**
     * Display multi-pane Career Copilot Chat Workspace.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if ($this->profileService->getProfilesForUser($user->id)->isEmpty()) {
            $this->profileService->seedDefaultProfilesForUser($user);
        }
        $this->resumeService->getOrSeedResumesForUser($user);

        $sessionId = $request->query('session_id') ? (int) $request->query('session_id') : null;
        $activeSession = $this->chatService->getOrCreateSession($user, $sessionId);

        $sessions = ChatSession::where('user_id', $user->id)
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get();

        $messages = $activeSession->messages;
        $roleProfiles = RoleProfile::where('user_id', $user->id)->get();
        $defaultResume = Resume::where('user_id', $user->id)->where('is_global_default', true)->first();

        // Career Context Metrics
        $contextMetrics = [
            'total_applications' => Application::where('user_id', $user->id)->count(),
            'active_interviews' => Application::where('user_id', $user->id)->where('application_status', 'Interview')->count(),
            'active_offers' => Application::where('user_id', $user->id)->where('application_status', 'Offer')->count(),
            'staged_count' => Application::where('user_id', $user->id)->where('application_status', 'Draft')->count(),
        ];

        $providers = $this->gatewayService->getProviders();

        return view('ai::chat.index', [
            'activeSession' => $activeSession,
            'sessions' => $sessions,
            'messages' => $messages,
            'roleProfiles' => $roleProfiles,
            'defaultResume' => $defaultResume,
            'contextMetrics' => $contextMetrics,
            'providers' => $providers,
        ]);
    }

    /**
     * Create a new conversation session.
     */
    public function createSession(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $title = $request->input('title', 'New Career Strategy Session');
        $roleProfileId = $request->input('role_profile_id') ? (int) $request->input('role_profile_id') : null;

        $session = $this->chatService->createSession($user, $title, $roleProfileId);

        return redirect()->route('ai.chat.index', ['session_id' => $session->id])
            ->with('success', "New chat session '{$session->title}' created.");
    }

    /**
     * Send user message + attachments and receive copilot response.
     */
    public function sendMessage(Request $request, int $sessionId): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'message' => ['required', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:10240'], // 10MB per attachment
        ]);

        $messageText = $request->input('message');
        $uploadedFiles = $request->file('attachments') ?? [];

        $result = $this->chatService->sendMessage(
            user: $user,
            sessionId: $sessionId,
            messageText: $messageText,
            uploadedFiles: is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'user_message' => $result['user_message'],
                'assistant_message' => $result['assistant_message'],
            ]);
        }

        return redirect()->route('ai.chat.index', ['session_id' => $sessionId]);
    }

    /**
     * Delete conversation session.
     */
    public function deleteSession(int $sessionId): RedirectResponse
    {
        $user = Auth::user();
        $this->chatService->deleteSession($user, $sessionId);

        return redirect()->route('ai.chat.index')->with('success', 'Conversation session deleted.');
    }
}
