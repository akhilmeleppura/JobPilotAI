<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ContentStudio\app\Models\ContentPost;
use App\Modules\ContentStudio\app\Services\CloudflareImageService;
use App\Modules\ContentStudio\app\Services\ContentStudioService;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContentStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser(): User
    {
        $user = User::factory()->create([
            'name' => 'Sarah Architect',
            'email' => 'sarah@example.com',
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
                if (str_contains($body, 'topics') || str_contains($body, 'Category Filter')) {
                    $text = '```json
[
  {
    "title": "Why MySQL Composite Indexes Fail on Range Queries",
    "category": "database",
    "hook": "Most developers index (status, created_at) and wonder why it scans 500k rows."
  },
  {
    "title": "Designing Zero-Downtime Database Migrations in Laravel",
    "category": "architecture",
    "hook": "Running ALTER TABLE on a 20-million row table locks production."
  }
]
```';
                } else {
                    $text = '```json
{
  "title": "Why MySQL Composite Indexes Fail on Range Queries",
  "hook": "Most developers index (status, created_at) and wonder why it scans 500k rows.",
  "content": "Most developers index (status, created_at) and wonder why it scans 500k rows.\\n\\nHere is the architectural reason: In B-Tree indexes, range queries stop the index traversal.\\n\\n• Always place equality columns first.\\n• Verify query execution plans using EXPLAIN.\\n\\nWhat is your preferred index structure?\\n\\n#PHP #Laravel #DatabaseOptimization #SystemDesign",
  "hashtags": ["#PHP", "#Laravel", "#DatabaseOptimization", "#SystemDesign"],
  "image_prompt": "Modern isometric database node diagram with glowing B-tree paths, dark slate theme",
  "category": "database"
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
                        'candidatesTokenCount' => 120,
                    ],
                ]);
            },
        ]);

        return $user;
    }

    public function test_guests_are_redirected_from_content_studio(): void
    {
        $response = $this->get(route('content.index'));
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_content_studio_workbench(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->get(route('content.index'));

        $response->assertStatus(200);
        $response->assertSee('LinkedIn Content & Image Studio', false);
        $response->assertSee('Technical Topic Ideator');
        $response->assertSee('Post Composer');
        $response->assertSee('Live Post Feed Preview');
        $response->assertSee('Content Calendar & Library', false);
    }

    public function test_generate_topics_returns_trending_technical_ideas(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->postJson(route('content.topics'), [
            'category' => 'database',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $topics = $response->json('topics');
        $this->assertIsArray($topics);
        $this->assertNotEmpty($topics);
        $this->assertArrayHasKey('title', $topics[0]);
    }

    public function test_draft_post_generates_structured_technical_content_and_hashtags(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->postJson(route('content.draft'), [
            'topic' => 'Why MySQL Composite Indexes Fail on Range Queries',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $post = $response->json('post');
        $this->assertNotEmpty($post['content']);
        $this->assertStringContainsString('#', implode(' ', $post['hashtags']));
        $this->assertNotEmpty($post['image_prompt']);
    }

    public function test_generate_image_creates_banner_with_cloudflare_or_local_vector_engine(): void
    {
        $user = $this->setupUser();

        $response = $this->actingAs($user)->postJson(route('content.generate-image'), [
            'title' => 'Preventing Redis Cache Stampedes in High-Concurrency APIs',
            'prompt' => 'Isometric glowing cache architecture diagram, dark theme',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $img = $response->json('image');
        $this->assertNotEmpty($img['path']);
        $this->assertNotEmpty($img['url']);
        $this->assertNotEmpty($img['provider']);

        // Verify generated file exists in storage
        $fullPath = storage_path("app/public/{$img['path']}");
        $this->assertTrue(File::exists($fullPath));
    }

    public function test_user_can_save_draft_and_scheduled_post(): void
    {
        $user = $this->setupUser();
        $defaultProfile = RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();

        $payload = [
            'role_profile_id' => $defaultProfile->id,
            'title' => 'Mastering Laravel Queue Workers',
            'topic' => 'Queue Worker Memory Management',
            'category' => 'craftsmanship',
            'content' => 'Why do queue workers leak memory? Clean up static singletons.',
            'image_prompt' => 'Queue nodes processing messages in parallel',
            'status' => 'draft',
        ];

        $response = $this->actingAs($user)->post(route('content.save'), $payload);

        $response->assertRedirect(route('content.index'));

        $this->assertDatabaseHas('content_posts', [
            'user_id' => $user->id,
            'title' => 'Mastering Laravel Queue Workers',
            'status' => 'draft',
        ]);
    }

    public function test_user_can_schedule_and_delete_post(): void
    {
        $user = $this->setupUser();

        $post = ContentPost::create([
            'user_id' => $user->id,
            'title' => 'Designing Zero-Downtime Database Migrations',
            'topic' => 'Database Migrations',
            'category' => 'architecture',
            'content' => 'Zero-downtime migrations require expand and contract patterns.',
            'status' => 'draft',
        ]);

        // Schedule
        $scheduleTime = Carbon::now()->addDays(2)->toDateTimeString();
        $scheduleResponse = $this->actingAs($user)->postJson(route('content.schedule', $post->id), [
            'scheduled_at' => $scheduleTime,
        ]);

        $scheduleResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('post.status', 'scheduled');

        $this->assertEquals('scheduled', $post->fresh()->status);

        // Delete
        $deleteResponse = $this->actingAs($user)->deleteJson(route('content.destroy', $post->id));
        $deleteResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('content_posts', ['id' => $post->id]);
    }

    public function test_calendar_schedule_groups_posts_by_date(): void
    {
        $user = $this->setupUser();

        $scheduledDate = Carbon::now()->addDays(3)->startOfHour();
        ContentPost::create([
            'user_id' => $user->id,
            'title' => 'PHP 8.4 Property Hooks',
            'topic' => 'PHP 8.4',
            'category' => 'php_internals',
            'content' => 'Property hooks eliminate getters/setters.',
            'status' => 'scheduled',
            'scheduled_at' => $scheduledDate,
        ]);

        $studioService = app(ContentStudioService::class);
        $schedule = $studioService->getCalendarSchedule($user);

        $dateKey = $scheduledDate->format('Y-m-d');
        $this->assertArrayHasKey($dateKey, $schedule);
        $this->assertEquals('PHP 8.4 Property Hooks', $schedule[$dateKey][0]['title']);
    }
}
