<?php

namespace App\Modules\AI\app\Services;

use App\Models\User;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\Models\ChatMessage;
use App\Modules\AI\app\Models\ChatSession;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AIChatService
{
    public function __construct(
        protected AIGatewayService $aiGateway,
        protected AIChatContextService $contextService
    ) {}

    /**
     * Get active session or create initial default session for user.
     */
    public function getOrCreateSession(User $user, ?int $sessionId = null): ChatSession
    {
        if ($sessionId) {
            $session = ChatSession::with(['messages', 'roleProfile'])
                ->where('user_id', $user->id)
                ->find($sessionId);

            if ($session) {
                return $session;
            }
        }

        // Return latest active session or create one
        $latest = ChatSession::with(['messages', 'roleProfile'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if ($latest) {
            return $latest;
        }

        return $this->createSession($user, 'Career Strategy & Interview Prep');
    }

    /**
     * Create a new chat session.
     */
    public function createSession(User $user, ?string $title = null, ?int $roleProfileId = null): ChatSession
    {
        $defaultProfile = $roleProfileId 
            ? RoleProfile::where('user_id', $user->id)->find($roleProfileId)
            : RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();

        $session = ChatSession::create([
            'user_id' => $user->id,
            'title' => $title ?: 'New Career Copilot Chat',
            'active_role_profile_id' => $defaultProfile?->id,
        ]);

        // Seed initial friendly welcome message from assistant
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'assistant',
            'message' => "Hello {$user->name}! I am your JobPilot AI Career Copilot. I have full context of your role personas, application pipeline, and ATS master CVs.\n\nHow can I accelerate your career today? Ask me to prepare for an upcoming technical interview, benchmark a compensation offer, analyze a job description, or refine your outreach pitch.",
            'provider' => 'system',
            'model' => 'copilot-assistant',
        ]);

        return $session;
    }

    /**
     * Delete a chat session.
     */
    public function deleteSession(User $user, int $sessionId): bool
    {
        $session = ChatSession::where('user_id', $user->id)->findOrFail($sessionId);
        return (bool) $session->delete();
    }

    /**
     * Process user turn, attachments, context assembly, and AI generation.
     *
     * @param array<UploadedFile> $uploadedFiles
     * @return array{user_message: ChatMessage, assistant_message: ChatMessage}
     */
    public function sendMessage(
        User $user,
        int $sessionId,
        string $messageText,
        array $uploadedFiles = []
    ): array {
        $session = ChatSession::where('user_id', $user->id)->findOrFail($sessionId);

        // 1. Process document attachments
        $attachments = [];
        if (!empty($uploadedFiles)) {
            $storageDir = storage_path("app/chat_attachments/{$session->id}");
            File::ensureDirectoryExists($storageDir);

            foreach ($uploadedFiles as $file) {
                if ($file instanceof UploadedFile) {
                    $origName = $file->getClientOriginalName();
                    $storedName = Str::random(12) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $origName);
                    $fullPath = $storageDir . '/' . $storedName;
                    File::put($fullPath, file_get_contents($file->getRealPath()));

                    $attachments[] = [
                        'filename' => $origName,
                        'path' => $fullPath,
                        'file_type' => $file->getClientOriginalExtension(),
                        'size' => File::size($fullPath),
                    ];
                }
            }
        }

        // 2. Persist User Message
        $userMsg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'user',
            'message' => $messageText,
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);

        // 3. Assemble Grounded System Context
        $systemInstruction = $this->contextService->buildSystemContext(
            user: $user,
            activeProfile: $session->roleProfile,
            attachments: $attachments
        );

        // 4. Build Conversation History Prompt
        $recentMessages = ChatMessage::where('chat_session_id', $session->id)
            ->orderBy('created_at')
            ->take(12)
            ->get();

        $historyPrompt = "CONVERSATION HISTORY:\n";
        foreach ($recentMessages as $msg) {
            $roleLabel = $msg->sender === 'user' ? 'Candidate' : 'Career Copilot';
            $historyPrompt .= "{$roleLabel}: {$msg->message}\n\n";
        }

        $historyPrompt .= "Candidate: {$messageText}\n\nCareer Copilot:";

        // 5. Execute AI Generation via Free-First Gateway
        $requestDTO = new AIRequestDTO(
            prompt: $historyPrompt,
            systemPrompt: $systemInstruction,
            temperature: 0.7,
            maxTokens: 1200
        );

        $aiResponse = $this->aiGateway->complete($user, $requestDTO);

        // 6. Persist Assistant Message
        $assistantMsg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'assistant',
            'message' => $aiResponse->content,
            'provider' => $aiResponse->provider,
            'model' => $aiResponse->model,
            'prompt_tokens' => $aiResponse->promptTokens,
            'completion_tokens' => $aiResponse->completionTokens,
        ]);

        // 7. Auto-title session if still named default
        if (str_starts_with($session->title, 'New Career Copilot') || str_starts_with($session->title, 'Career Copilot Session')) {
            $shortTitle = Str::limit(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $messageText)), 35, '...');
            if (!empty($shortTitle)) {
                $session->update(['title' => $shortTitle]);
            }
        }

        return [
            'user_message' => $userMsg,
            'assistant_message' => $assistantMsg,
        ];
    }
}
