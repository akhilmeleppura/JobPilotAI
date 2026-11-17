<?php

namespace App\Modules\AI\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AI\app\Services\CopilotAssistantService;
use App\Modules\AI\app\Services\SmartNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CopilotWidgetController extends Controller
{
    public function __construct(
        protected CopilotAssistantService $copilotService,
        protected SmartNotificationService $notificationService
    ) {}

    /**
     * Handle incoming interactive chat message from the floating copilot widget.
     */
    public function message(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $response = $this->copilotService->handleUserMessage($user, $validated['message']);

        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
    }

    /**
     * Get initial state and recommendations for a new or returning user.
     */
    public function initial(Request $request): JsonResponse
    {
        $user = Auth::user();
        $response = $this->copilotService->handleUserMessage($user, 'where to start');

        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
    }

    /**
     * Get proactive real-time notifications for the current user.
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = Auth::user();
        $data = $this->notificationService->getNotificationsForUser($user, 10);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markNotificationRead(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $success = $this->notificationService->markAsRead($user, $id);

        return response()->json([
            'success' => $success,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'marked_count' => $count,
        ]);
    }
}
