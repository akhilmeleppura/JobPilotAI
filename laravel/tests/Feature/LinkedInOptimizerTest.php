<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AI\app\Services\AIGatewayService;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\LinkedIn\app\Models\LinkedInProfile;
use App\Modules\LinkedIn\app\Services\LinkedInOptimizerService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkedInOptimizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser(): User
    {
        $user = User::factory()->create([
            'name' => 'Jordan LeadDev',
            'email' => 'jordan@example.com',
        ]);

        app(RoleProfileService::class)->seedDefaultProfilesForUser($user);
        $vault = app(CredentialVaultService::class);
        $vault->getOrSeedIntegrationsForUser($user);

        $geminiCred = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'google_ai_studio')
            ->first();

        if ($geminiCred) {
            $vault->updateIntegration($geminiCred, ['api_key' => 'AIzaSyMockGeminiKey']);
        }

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function (\Illuminate\Http\Client\Request $request) {
                $body = $request->body();
                if (str_contains($body, 'XYZ') || str_contains($body, 'Accomplished') || str_contains($body, 'experience')) {
                    $text = "• Architected backend database query optimization, reducing latency by 35%.\n• Engineered streamlined customer checkout flow, boosting completion by 22%.\n• Spearheaded API authentication layer, securing 500k monthly requests.";
                } elseif (str_contains($body, 'About') || str_contains($body, 'narrative')) {
                    $text = "I engineer resilient, scalable backend architectures and high-throughput APIs that power modern business operations.\n\nWith 8+ years of software development experience specializing in PHP and Laravel, I design microservices and optimize complex database systems.\n\nCore Competencies:\n• Laravel & PHP 8.4\n• MySQL / TiDB Cloud & Redis\n• Docker & CI/CD Pipelines\n\nRecent Achievements:\n• Scaled API throughput to 10M requests daily with 99.99% availability.\n• Reduced database read latency by 45% using intelligent Redis caching.\n\nLet's connect: jordan@example.com";
                } else {
                    $text = '```json
{
  "authority": "Senior Laravel Architect | Scaling Distributed High-Throughput APIs & Cloud Systems",
  "keyword": "Lead PHP Developer | Laravel 12 | MySQL | Redis | Docker | RESTful APIs | CI/CD",
  "metrics": "Laravel Backend Engineer | Scaled System to 2M+ Users & Slashed Query Latency by 45%",
  "consulting": "Principal PHP Consultant | High-Performance Web Architectures & Modernization"
}
```';
                }

                return Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => $text],
                                ],
                            ],
                        ],
                    ],
                    'usageMetadata' => [
                        'promptTokenCount' => 100,
                        'candidatesTokenCount' => 80,
                    ],
                ]);
            },
        ]);

        return $user;
    }

    public function test_linkedin_optimizer_page_renders_successfully(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get(route('linkedin.optimizer'));

        $response->assertStatus(200);
        $response->assertSee('LinkedIn Profile Optimizer');
        $response->assertSee('Recruiter Search Score');
        $response->assertSee('Headline Studio');
        $response->assertSee('Above-the-Fold Hook Preview');
        $response->assertSee('Experience Bullets (Google XYZ Framework)');
    }

    public function test_audit_calculates_recruiter_score_and_identifies_missing_keywords(): void
    {
        $user = $this->setupUser();
        $defaultProfile = RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();

        // 1. Weak profile lacking core target keywords
        $weakResponse = $this->actingAs($user)->postJson(route('linkedin.audit'), [
            'role_profile_id' => $defaultProfile->id,
            'headline' => 'Junior Web Builder',
            'about' => 'I make websites for fun.',
            'experience' => 'Helped with front-end tasks.',
        ]);

        $weakResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $weakAudit = $weakResponse->json('audit');
        $this->assertLessThan(60, $weakAudit['recruiter_score']);
        $this->assertNotEmpty($weakAudit['missing_keywords']);
        $this->assertContains('PHP', $weakAudit['missing_keywords']);

        // 2. Strong profile containing key stack skills and metrics
        $strongResponse = $this->actingAs($user)->postJson(route('linkedin.audit'), [
            'role_profile_id' => $defaultProfile->id,
            'headline' => 'Senior Laravel Developer | PHP 8.4 | MySQL | Redis | REST APIs | Docker',
            'about' => 'Senior software engineer with 8+ years building enterprise applications. Core stack includes PHP, Laravel, MySQL, Docker, and Redis. Led team of 6 engineers and scaled traffic to 1.5M users with 99.9% uptime. Reach out at jordan@example.com.',
            'experience' => 'Architected high-throughput microservices using Laravel and MySQL, cutting API latency by 45% for 500k active users.',
        ]);

        $strongResponse->assertStatus(200);
        $strongAudit = $strongResponse->json('audit');
        $this->assertGreaterThan(75, $strongAudit['recruiter_score']);
        $this->assertTrue($strongAudit['has_metrics']);
        $this->assertContains('PHP', $strongAudit['matched_keywords']);
        $this->assertContains('Laravel', $strongAudit['matched_keywords']);
    }

    public function test_generate_headlines_produces_four_distinct_variations_under_220_characters(): void
    {
        $user = $this->setupUser();
        $defaultProfile = RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();

        $response = $this->actingAs($user)->postJson(route('linkedin.generate'), [
            'type' => 'headlines',
            'role_profile_id' => $defaultProfile->id,
            'current_headline' => 'Software Engineer',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'headlines' => [
                    'authority',
                    'keyword',
                    'metrics',
                    'consulting',
                ],
            ]);

        $headlines = $response->json('headlines');
        foreach ($headlines as $key => $headline) {
            $this->assertNotEmpty($headline, "Headline '{$key}' should not be empty.");
            $this->assertLessThanOrEqual(220, mb_strlen($headline), "Headline '{$key}' must not exceed 220 characters.");
        }
    }

    public function test_generate_about_section_produces_compelling_narrative(): void
    {
        $user = $this->setupUser();
        $defaultProfile = RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();

        $response = $this->actingAs($user)->postJson(route('linkedin.generate'), [
            'type' => 'about',
            'role_profile_id' => $defaultProfile->id,
            'current_about' => 'Basic draft summary.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $about = $response->json('about');
        $this->assertNotEmpty($about);
        $this->assertLessThanOrEqual(2600, mb_strlen($about));
        $this->assertStringContainsString('resilient, scalable backend', $about);
    }

    public function test_transform_bullets_converts_to_google_xyz_framework(): void
    {
        $user = $this->setupUser();

        $rawInput = "responsible for backend database queries\nhelped with customer checkout flow\nworked on api authentication";

        $response = $this->actingAs($user)->postJson(route('linkedin.transform-bullets'), [
            'raw_experience' => $rawInput,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $optimized = $response->json('optimized');
        $this->assertNotEmpty($optimized);
        $this->assertStringContainsString('• ', $optimized);
        $this->assertStringNotContainsString('responsible for', strtolower($optimized));
    }

    public function test_save_persists_candidate_linkedin_profile_and_score(): void
    {
        $user = $this->setupUser();
        $defaultProfile = RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();

        $payload = [
            'role_profile_id' => $defaultProfile->id,
            'current_headline' => 'Junior Dev',
            'optimized_headline' => 'Senior Laravel Engineer | Microservices | MySQL | Redis',
            'current_about' => 'Short draft',
            'optimized_about' => 'Accomplished software engineer specializing in high-throughput Laravel microservices. Scaled databases by 30%.',
            'current_experience' => 'worked on code',
            'optimized_experience' => '• Architected resilient payment APIs, handling $2M daily volume.',
            'optimization_notes' => 'Targeting top tier fintech roles in 2026.',
        ];

        $response = $this->actingAs($user)->post(route('linkedin.save'), $payload);

        $response->assertRedirect(route('linkedin.optimizer', ['role_profile_id' => $defaultProfile->id]));

        $this->assertDatabaseHas('linkedin_profiles', [
            'user_id' => $user->id,
            'role_profile_id' => $defaultProfile->id,
            'optimized_headline' => 'Senior Laravel Engineer | Microservices | MySQL | Redis',
            'optimization_notes' => 'Targeting top tier fintech roles in 2026.',
        ]);

        $saved = LinkedInProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($saved);
        $this->assertGreaterThan(50, $saved->recruiter_score);
    }

    public function test_role_persona_switcher_updates_target_profile(): void
    {
        $user = $this->setupUser();
        $profiles = RoleProfile::where('user_id', $user->id)->get();
        $secondaryProfile = $profiles->last();

        $response = $this->actingAs($user)->get(route('linkedin.optimizer', [
            'role_profile_id' => $secondaryProfile->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee($secondaryProfile->name);
    }
}
