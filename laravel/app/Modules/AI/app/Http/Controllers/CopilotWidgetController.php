<?php

namespace App\Modules\AI\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AI\app\Services\CopilotAssistantService;
use App\Modules\AI\app\Services\SmartNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

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
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

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
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $data = $this->notificationService->getNotificationsForUser($user, 10);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Notifications controller exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => 0,
                    'notifications' => [],
                    'note' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Mark a specific notification as read.
     */
    public function markNotificationRead(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

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
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'marked_count' => $count,
        ]);
    }
}
