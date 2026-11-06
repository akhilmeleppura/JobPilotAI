<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AI\app\DTOs\AIResponseDTO;
use App\Modules\AI\app\Models\ChatMessage;
use App\Modules\AI\app\Models\ChatSession;
use App\Modules\AI\app\Services\AIChatContextService;
use App\Modules\AI\app\Services\AIChatService;
use App\Modules\AI\app\Services\AIGatewayService;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AIChatWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser(): User
    {
        $user = User::factory()->create(['name' => 'Alex Developer', 'email' => 'alex@example.com']);
        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        app(ResumeService::class)->getOrSeedResumesForUser($user);
        $vault = app(CredentialVaultService::class);
        $vault->getOrSeedIntegrationsForUser($user);

        $geminiCred = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'google_ai_studio')
            ->firstOrFail();
        $vault->updateIntegration($geminiCred, ['api_key' => 'AIzaSyMockGeminiKey']);

        \Illuminate\Support\Facades\Http::fake([
            'https://generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Based on your profile, I recommend highlighting your PHP 8.4 and TiDB Cloud experience.'],
                            ],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 120,
                    'candidatesTokenCount' => 45,
                    'totalTokenCount' => 165,
                ],
            ], 200),
        ]);

        return $user;
    }

    public function test_guests_are_redirected_from_chat_workspace(): void
    {
        $response = $this->get('/ai-chat');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_chat_workspace_and_auto_seeds_session(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get('/ai-chat');
        $response->assertStatus(200);
        $response->assertSee('Career Copilot Workspace');
        $response->assertSee('Conversations');
        $response->assertSee('Career Context');

        $session = ChatSession::where('user_id', $user->id)->first();
        $this->assertNotNull($session);
        $this->assertGreaterThan(0, $session->messages()->count());
    }

    public function test_user_can_create_new_chat_session(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->post('/ai-chat/session', [
            'title' => 'Stripe Lead Architect Interview Prep',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $session = ChatSession::where('user_id', $user->id)
            ->where('title', 'Stripe Lead Architect Interview Prep')
            ->firstOrFail();

        $this->assertEquals(1, $session->messages()->count());
        $this->assertEquals('assistant', $session->messages()->first()->sender);
    }

    public function test_user_can_send_message_and_receive_context_aware_ai_response(): void
    {
        $user = $this->setupUser();
        $chatService = app(AIChatService::class);
        $session = $chatService->createSession($user, 'Salary Negotiation');

        $response = $this->actingAs($user)->post("/ai-chat/session/{$session->id}/message", [
            'message' => 'What leverage points do I have to negotiate an extra $15k sign-on bonus?',
        ]);

        $response->assertRedirect("/ai-chat?session_id={$session->id}");

        // Verify both user message and assistant message saved
        $messages = ChatMessage::where('chat_session_id', $session->id)->get();
        $this->assertGreaterThanOrEqual(3, $messages->count()); // initial + user + assistant

        $userMsg = $messages->firstWhere('sender', 'user');
        $this->assertNotNull($userMsg);
        $this->assertStringContainsString('sign-on bonus', $userMsg->message);

        $assistantMsg = $messages->where('sender', 'assistant')->last();
        $this->assertNotNull($assistantMsg);
        $this->assertNotEmpty($assistantMsg->message);
        $this->assertNotNull($assistantMsg->provider);
    }

    public function test_user_can_send_message_with_document_attachment(): void
    {
        $user = $this->setupUser();
        $chatService = app(AIChatService::class);
        $session = $chatService->createSession($user, 'Offer Review');

        $file = UploadedFile::fake()->createWithContent('offer_letter.txt', "Company: Stripe\nRole: Senior PHP Architect\nBase: $140,000\nBonus: 10%\nEquity: $50,000 RSUs");

        $response = $this->actingAs($user)->postJson("/ai-chat/session/{$session->id}/message", [
            'message' => 'Please evaluate this offer letter against current market benchmarks.',
            'attachments' => [$file],
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);

        $userMsg = ChatMessage::find($data['user_message']['id']);
        $this->assertNotEmpty($userMsg->attachments);
        $this->assertEquals('offer_letter.txt', $userMsg->attachments[0]['filename']);
    }

    public function test_user_can_delete_chat_session(): void
    {
        $user = $this->setupUser();
        $chatService = app(AIChatService::class);
        $session = $chatService->createSession($user, 'Temporary Session');

        $response = $this->actingAs($user)->delete("/ai-chat/session/{$session->id}");
        $response->assertRedirect('/ai-chat');
        $response->assertSessionHas('success');

        $this->assertNull(ChatSession::find($session->id));
        $this->assertEquals(0, ChatMessage::where('chat_session_id', $session->id)->count());
    }

    public function test_ai_chat_context_service_builds_grounded_system_instructions(): void
    {
        $user = $this->setupUser();
        $contextService = app(AIChatContextService::class);

        $systemPrompt = $contextService->buildSystemContext($user);

        $this->assertStringContainsString('Alex Developer', $systemPrompt);
        $this->assertStringContainsString('Active Persona', $systemPrompt);
        $this->assertStringContainsString('Target Roles', $systemPrompt);
        $this->assertStringContainsString('APPLICATION PIPELINE SUMMARY', $systemPrompt);
        $this->assertStringContainsString('GUIDELINES & BEHAVIOR', $systemPrompt);
    }
}
