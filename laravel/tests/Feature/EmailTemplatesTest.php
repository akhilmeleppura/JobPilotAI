<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AI\app\Services\AIGatewayService;
use App\Modules\Email\app\Models\EmailTemplate;
use App\Modules\Email\app\Services\EmailTemplateService;
use App\Modules\Email\app\Services\TemplateInterpolationService;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_templates(): void
    {
        $response = $this->get('/email-studio/templates');
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_templates_and_auto_seeds_standard_templates(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/email-studio/templates');
        $response->assertStatus(200);
        $response->assertSee('Communication Templates Studio');
        $response->assertSee('Direct Cold Application (Laravel &amp; PHP Specialist)', false);
        $response->assertSee('Post-Application Follow-Up (5-Day Check-in)');
        $response->assertSee('Post-Technical Interview Thank You');
        $response->assertSee('Freelance &amp; Contract Pitch', false);

        $this->assertEquals(6, EmailTemplate::where('user_id', $user->id)->count());
    }

    public function test_interpolation_service_replaces_all_variables(): void
    {
        $service = app(TemplateInterpolationService::class);

        $template = new EmailTemplate([
            'subject_template' => 'Application for {{job_title}} at {{company}}',
            'body_template' => '<p>Dear {{company}}, my name is {{candidate_name}}. I have {{experience_years}} years experience in {{tech_stack}}.</p>',
        ]);

        $rendered = $service->renderTemplate($template, [
            'job_title' => 'Senior Backend Engineer',
            'company' => 'Acme Labs',
            'candidate_name' => 'John Doe',
            'experience_years' => 7,
            'tech_stack' => ['PHP 8.4', 'Laravel 13', 'TiDB'],
        ]);

        $this->assertEquals('Application for Senior Backend Engineer at Acme Labs', $rendered['subject']);
        $this->assertEquals('<p>Dear Acme Labs, my name is John Doe. I have 7 years experience in PHP 8.4, Laravel 13, TiDB.</p>', $rendered['body']);
    }

    public function test_user_can_create_custom_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/email-studio/templates', [
            'category' => 'RecruiterInquiry',
            'name' => 'Quick Talent Pool Inquiry',
            'subject_template' => 'Senior PHP Engineer inquiry — {{company}}',
            'body_template' => '<p>Hello, are you currently hiring remote engineers?</p>',
            'is_default' => 1,
        ]);

        $response->assertRedirect(route('email.templates.index'));

        $this->assertDatabaseHas('email_templates', [
            'user_id' => $user->id,
            'name' => 'Quick Talent Pool Inquiry',
            'category' => 'RecruiterInquiry',
            'is_default' => 1,
        ]);
    }

    public function test_user_can_update_and_delete_template(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/email-studio/templates');

        $template = EmailTemplate::where('user_id', $user->id)->firstOrFail();

        // Update
        $resUpdate = $this->actingAs($user)->put("/email-studio/templates/{$template->id}", [
            'category' => 'InitialOutreach',
            'name' => 'Updated Headline Pitch',
            'subject_template' => 'New Subject Line',
            'body_template' => '<p>Updated Body</p>',
        ]);
        $resUpdate->assertRedirect(route('email.templates.index'));
        $this->assertEquals('Updated Headline Pitch', $template->fresh()->name);

        // Delete
        $resDelete = $this->actingAs($user)->delete("/email-studio/templates/{$template->id}");
        $resDelete->assertRedirect(route('email.templates.index'));
        $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
    }

    public function test_preview_endpoint_returns_json_rendered_template(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/email-studio/templates');

        $template = EmailTemplate::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->get("/email-studio/templates/{$template->id}/preview?company=Vercel&title=Staff+Engineer");
        $response->assertStatus(200);
        $response->assertJsonStructure(['subject', 'body']);

        $json = $response->json();
        $this->assertStringContainsString('Vercel', $json['subject'] . $json['body']);
    }

    public function test_mini_ai_assistant_tailors_email_body(): void
    {
        $user = User::factory()->create();
        $vault = app(CredentialVaultService::class);
        $vault->getOrSeedIntegrationsForUser($user);

        $groqCred = \App\Modules\Integrations\app\Models\IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'groq')
            ->firstOrFail();
        $vault->updateIntegration($groqCred, ['api_key' => 'gsk_mock_test_key_12345']);

        // Mock Groq API
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '<p>Dear Stripe Team, I have extensive experience in high-concurrency payment APIs.</p>']],
                ],
                'usage' => [
                    'prompt_tokens' => 50,
                    'completion_tokens' => 20,
                    'total_tokens' => 70,
                ],
            ], 200),
        ]);

        $this->actingAs($user)->get('/email-studio/templates');
        $template = EmailTemplate::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post("/email-studio/templates/{$template->id}/tailor-ai", [
            'company_name' => 'Stripe',
            'job_title' => 'Senior Payments Architect',
            'description' => 'Looking for engineers with high-volume database sharding experience.',
            'instructions' => 'Emphasize MySQL optimization',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tailored_body' => '<p>Dear Stripe Team, I have extensive experience in high-concurrency payment APIs.</p>',
        ]);
    }
}

