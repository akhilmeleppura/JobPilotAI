<?php

namespace App\Modules\LinkedIn\app\Http\Controllers;

use App\Modules\ContentStudio\app\Models\ContentPost;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\LinkedIn\app\Services\LinkedInPublisherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class LinkedInPublishingController extends Controller
{
    public function __construct(
        protected LinkedInPublisherService $publisherService,
        protected CredentialVaultService $vaultService
    ) {}

    /**
     * Show LinkedIn Publishing Control Room & Review Queue.
     */
    public function index(): View
    {
        $user = Auth::user();
        $this->vaultService->getOrSeedIntegrationsForUser($user);

        $publishingStatus = $this->publisherService->getPublishingStatus($user);

        $pendingPosts = ContentPost::where('user_id', $user->id)
            ->whereIn('status', ['scheduled', 'draft'])
            ->orderByDesc('created_at')
            ->get();

        $publishedPosts = ContentPost::where('user_id', $user->id)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get();

        return view('linkedin::publishing.index', [
            'status' => $publishingStatus,
            'pendingPosts' => $pendingPosts,
            'publishedPosts' => $publishedPosts,
        ]);
    }

    /**
     * Redirect candidate to LinkedIn OAuth2 consent screen.
     */
    public function redirectOAuth(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $url = $this->publisherService->getOAuthUrl($user);

        return redirect()->away($url);
    }

    /**
     * Handle LinkedIn OAuth2 authorization code callback.
     */
    public function callbackOAuth(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $code = $request->query('code', 'mock_auth_code');

        $result = $this->publisherService->handleOAuthCallback($user, $code);

        if ($result['success']) {
            return redirect()->route('linkedin.publishing')
                ->with('status', 'LinkedIn account successfully connected as ' . ($result['member_name'] ?? 'Member') . '!');
        }

        return redirect()->route('linkedin.publishing')
            ->with('error', 'Failed to authenticate with LinkedIn. Please verify your client credentials.');
    }

    /**
     * Disconnect LinkedIn OAuth account.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $this->publisherService->disconnect($user);

        return redirect()->route('linkedin.publishing')
            ->with('status', 'LinkedIn account has been disconnected.');
    }

    /**
     * Human-in-the-Loop review: Approve & immediately publish post to LinkedIn.
     */
    public function approve(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $post = ContentPost::where('user_id', $user->id)->findOrFail($id);

        try {
            $result = $this->publisherService->publishPost($user, $post);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Post published to LinkedIn successfully!',
                    'post' => $post->fresh(),
                    'result' => $result,
                ]);
            }

            return redirect()->route('linkedin.publishing')
                ->with('status', 'Post successfully published to LinkedIn! Live URL: ' . $result['url']);
        } catch (Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('linkedin.publishing')
                ->with('error', 'Publishing error: ' . $e->getMessage());
        }
    }

    /**
     * Reject or return post to draft.
     */
    public function reject(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $post = ContentPost::where('user_id', $user->id)->findOrFail($id);

        $post->update(['status' => 'draft']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Post reverted to draft.',
                'post' => $post,
            ]);
        }

        return redirect()->route('linkedin.publishing')
            ->with('status', 'Post reverted to draft.');
    }
}

