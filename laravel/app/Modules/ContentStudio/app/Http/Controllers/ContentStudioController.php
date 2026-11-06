<?php

namespace App\Modules\ContentStudio\app\Http\Controllers;

use App\Modules\ContentStudio\app\Models\ContentPost;
use App\Modules\ContentStudio\app\Services\ContentStudioService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContentStudioController extends Controller
{
    public function __construct(
        protected ContentStudioService $studioService,
        protected RoleProfileService $profileService
    ) {}

    /**
     * Show Content & Image Studio workbench.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Ensure role profiles are seeded
        if ($this->profileService->getProfilesForUser($user->id)->isEmpty()) {
            $this->profileService->seedDefaultProfilesForUser($user);
        }

        $profiles = $this->profileService->getProfilesForUser($user->id);
        $status = $request->query('status');

        $posts = $this->studioService->getPosts($user, $status);
        $calendarSchedule = $this->studioService->getCalendarSchedule($user);

        $stats = [
            'total' => ContentPost::where('user_id', $user->id)->count(),
            'scheduled' => ContentPost::where('user_id', $user->id)->where('status', 'scheduled')->count(),
            'published' => ContentPost::where('user_id', $user->id)->where('status', 'published')->count(),
            'draft' => ContentPost::where('user_id', $user->id)->where('status', 'draft')->count(),
        ];

        return view('contentstudio::studio.index', [
            'posts' => $posts,
            'profiles' => $profiles,
            'calendarSchedule' => $calendarSchedule,
            'stats' => $stats,
            'activeStatus' => $status,
        ]);
    }

    /**
     * Ideate trending technical topics via AI.
     */
    public function topics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $category = $request->input('category', 'all');

        $roleProfile = null;
        if ($request->filled('role_profile_id')) {
            $roleProfile = RoleProfile::where('user_id', $user->id)->find($request->input('role_profile_id'));
        }

        $topics = $this->studioService->generateTopicIdeas($user, $roleProfile, $category);

        return response()->json([
            'success' => true,
            'topics' => $topics,
        ]);
    }

    /**
     * Draft full technical LinkedIn post with hooks, body, code insights, and hashtags.
     */
    public function draft(Request $request): JsonResponse
    {
        $user = Auth::user();
        $topic = $request->input('topic', 'Scalable System Architecture');

        $roleProfile = null;
        if ($request->filled('role_profile_id')) {
            $roleProfile = RoleProfile::where('user_id', $user->id)->find($request->input('role_profile_id'));
        }

        $draft = $this->studioService->draftPost($user, $topic, $roleProfile);

        return response()->json([
            'success' => true,
            'post' => $draft,
        ]);
    }

    /**
     * Generate visual banner using Cloudflare Workers AI or local SVG engine.
     */
    public function generateImage(Request $request): JsonResponse
    {
        $user = Auth::user();
        $prompt = $request->input('prompt', 'System Architecture Diagram');
        $title = $request->input('title', 'Architecture Deep Dive');

        $imageResult = $this->studioService->generateImage($user, $prompt, $title);

        return response()->json([
            'success' => true,
            'image' => $imageResult,
        ]);
    }

    /**
     * Save post (create or update).
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $postId = $request->input('post_id') ? (int) $request->input('post_id') : null;

        $post = $this->studioService->savePost($user, $request->all(), $postId);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Post saved successfully.',
                'post' => $post,
            ]);
        }

        return redirect()->route('content.index')
            ->with('status', 'Post saved successfully.');
    }

    /**
     * Schedule a post for a specific date and time.
     */
    public function schedule(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $scheduledAt = $request->input('scheduled_at', Carbon::now()->addDays(1)->toDateTimeString());

        $post = $this->studioService->schedulePost($user, $id, $scheduledAt);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Post scheduled successfully.',
                'post' => $post,
            ]);
        }

        return redirect()->route('content.index')
            ->with('status', 'Post scheduled successfully for ' . $post->scheduled_at->format('M d, Y H:i'));
    }

    /**
     * Delete post.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $deleted = $this->studioService->deletePost($user, $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? 'Post deleted successfully.' : 'Post not found.',
            ]);
        }

        return redirect()->route('content.index')
            ->with('status', 'Post deleted successfully.');
    }
}

