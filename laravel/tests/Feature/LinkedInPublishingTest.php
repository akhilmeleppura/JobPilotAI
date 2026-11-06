<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ContentStudio\app\Models\ContentPost;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\LinkedIn\app\Services\LinkedInPublisherService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkedInPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUser(bool $connectLinkedIn = true): User
    {
        $user = User::factory()->create([
            'name' => 'Michael LeadArchitect',
            'email' => 'michael@example.com',
        ]);

        $vault = app(CredentialVaultService::class);
        $vault->getOrSeedIntegrationsForUser($user);

        $cred = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'linkedin_oauth')
            ->first();

        if ($cred && $connectLinkedIn) {
            $vault->updateIntegration(
                $cred,
                ['access_token' => 'AQ_Live_Test_Token_12345'],
                [
                    'client_id' => 'mock_client_id',
                    'client_secret' => 'mock_client_secret',
                    'member_urn' => 'urn:li:person:user_' . $user->id,
                    'member_name' => $user->name,
                    'expires_at' => Carbon::now()->addDays(60)->toDateTimeString(),
                    'requires_human_review' => true,
                ],
                true
            );
        }

        Http::fake([
            'https://www.linkedin.com/oauth/v2/accessToken' => Http::response([
                'access_token' => 'AQ_Live_Test_Token_12345',
                'expires_in' => 5184000,
            ]),
            'https://api.linkedin.com/v2/userinfo' => Http::response([
                'sub' => '12345678',
                'name' => 'Michael LeadArchitect',
                'email' => 'michael@example.com',
            ]),
            'https://api.linkedin.com/v2/assets?action=registerUpload' => Http::response([
                'value' => [
                    'asset' => 'urn:li:digitalmediaAsset:D5600X123',
                    'uploadMechanism' => [
                        'com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest' => [
                            'uploadUrl' => 'https://api.linkedin.com/mediaUpload/12345',
                        ],
                    ],
                ],
            ]),
            'https://api.linkedin.com/mediaUpload/*' => Http::response('', 201),
            'https://api.linkedin.com/v2/ugcPosts' => Http::response([
                'id' => 'urn:li:share:719823487123987',
            ], 201),
        ]);

        return $user;
    }

    public function test_guests_are_redirected_from_publishing_workbench(): void
    {
        $response = $this->get(route('linkedin.publishing'));
        $response->assertRedirect('/login');
    }

    public function test_user_can_access_publishing_workbench_and_views_status(): void
    {
        $user = $this->setupUser(connectLinkedIn: true);

        $response = $this->actingAs($user)->get(route('linkedin.publishing'));

        $response->assertStatus(200);
        $response->assertSee('Authorized LinkedIn Publishing');
        $response->assertSee('LinkedIn Member Account Connected');
        $response->assertSee('Human-in-the-Loop Review');
        $response->assertSee('Zero-Bot Guarantee');
    }

    public function test_oauth_redirect_builds_valid_linkedin_authorization_url(): void
    {
        $user = $this->setupUser(connectLinkedIn: false);

        $response = $this->actingAs($user)->get(route('linkedin.auth.redirect'));

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringContainsString('linkedin.com/oauth/v2/authorization', $target);
        $this->assertStringContainsString('response_type=code', $target);
        $this->assertStringContainsString('scope=', $target);
    }

    public function test_oauth_callback_exchanges_token_and_activates_credential(): void
    {
        $user = $this->setupUser(connectLinkedIn: false);

        $response = $this->actingAs($user)->get(route('linkedin.auth.callback', [
            'code' => 'test_authorization_code_abc123',
            'state' => 'test_state',
        ]));

        $response->assertRedirect(route('linkedin.publishing'));

        $cred = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'linkedin_oauth')
            ->first();

        $this->assertNotNull($cred);
        $this->assertTrue($cred->is_enabled);
        $secrets = $cred->getSecrets();
        $this->assertNotEmpty($secrets['access_token'] ?? '');
        $this->assertEquals('urn:li:person:12345678', $cred->settings['member_urn']);
    }

    public function test_user_can_disconnect_linkedin_account(): void
    {
        $user = $this->setupUser(connectLinkedIn: true);

        $response = $this->actingAs($user)->post(route('linkedin.auth.disconnect'));

        $response->assertRedirect(route('linkedin.publishing'));

        $cred = IntegrationCredential::where('user_id', $user->id)
            ->where('provider_key', 'linkedin_oauth')
            ->first();

        $this->assertFalse($cred->is_enabled);
        $secrets = $cred->getSecrets();
        $this->assertEmpty($secrets['access_token'] ?? '');
    }

    public function test_human_review_approval_publishes_post_to_linkedin(): void
    {
        $user = $this->setupUser(connectLinkedIn: true);

        $post = ContentPost::create([
            'user_id' => $user->id,
            'title' => 'Scaling MySQL Composite Indexes',
            'topic' => 'Database Optimization',
            'category' => 'database',
            'content' => 'Why range queries stop index traversal in MySQL and TiDB.',
            'status' => 'scheduled',
            'scheduled_at' => Carbon::now()->addHour(),
        ]);

        $response = $this->actingAs($user)->post(route('linkedin.publishing.approve', $post->id));

        $response->assertRedirect(route('linkedin.publishing'));

        $post->refresh();
        $this->assertEquals('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertNotEmpty($post->metrics['linkedin_post_urn']);
        $this->assertStringContainsString('https://www.linkedin.com/feed/update/', $post->metrics['linkedin_url']);
    }

    public function test_publishing_with_attached_image_uploads_binary_asset(): void
    {
        $user = $this->setupUser(connectLinkedIn: true);

        // Create temporary test image banner
        $dir = storage_path("app/public/content_images/{$user->id}");
        File::ensureDirectoryExists($dir);
        $testImagePath = "content_images/{$user->id}/test_banner.svg";
        File::put(storage_path("app/public/{$testImagePath}"), '<svg><rect width="100" height="100"/></svg>');

        $post = ContentPost::create([
            'user_id' => $user->id,
            'title' => 'Preventing Redis Cache Stampedes',
            'topic' => 'Architecture',
            'category' => 'architecture',
            'content' => 'When hot keys expire, database connections saturate.',
            'image_path' => $testImagePath,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)->postJson(route('linkedin.publishing.approve', $post->id));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $post->refresh();
        $this->assertEquals('published', $post->status);
    }

    public function test_reject_reverts_post_to_draft_status(): void
    {
        $user = $this->setupUser(connectLinkedIn: true);

        $post = ContentPost::create([
            'user_id' => $user->id,
            'title' => 'Unreviewed Technical Post',
            'topic' => 'Draft Review',
            'category' => 'craftsmanship',
            'content' => 'Needs revisions before publishing.',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)->post(route('linkedin.publishing.reject', $post->id));

        $response->assertRedirect(route('linkedin.publishing'));

        $this->assertEquals('draft', $post->fresh()->status);
    }

    public function test_unconnected_account_cannot_publish_and_handles_error(): void
    {
        $user = $this->setupUser(connectLinkedIn: false);

        $post = ContentPost::create([
            'user_id' => $user->id,
            'title' => 'Post Without LinkedIn Auth',
            'topic' => 'Auth Guard',
            'category' => 'architecture',
            'content' => 'Should fail because user has not authorized OAuth.',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->postJson(route('linkedin.publishing.approve', $post->id));

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertEquals('draft', $post->fresh()->status);
    }
}
