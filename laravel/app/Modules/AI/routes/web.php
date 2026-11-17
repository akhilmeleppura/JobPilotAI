<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AI\app\Http\Controllers\AIChatWorkspaceController;
use App\Modules\AI\app\Http\Controllers\AIController;
use App\Modules\AI\app\Http\Controllers\CopilotWidgetController;

Route::middleware('auth')->prefix('ai-chat')->name('ai.')->group(function () {
    // Full Career Copilot Chat Workspace
    Route::get('/', [AIChatWorkspaceController::class, 'index'])->name('chat.index');
    Route::post('/session', [AIChatWorkspaceController::class, 'createSession'])->name('chat.create-session');
    Route::post('/session/{id}/message', [AIChatWorkspaceController::class, 'sendMessage'])->name('chat.send-message');
    Route::delete('/session/{id}', [AIChatWorkspaceController::class, 'deleteSession'])->name('chat.delete-session');

    // Floating Copilot Assistant Widget endpoints (available globally across app)
    Route::post('/widget/message', [CopilotWidgetController::class, 'message'])->name('widget.message');
    Route::get('/widget/initial', [CopilotWidgetController::class, 'initial'])->name('widget.initial');
    
    // Proactive Smart Notification Endpoints
    Route::get('/widget/notifications', [CopilotWidgetController::class, 'notifications'])->name('widget.notifications');
    Route::post('/widget/notifications/{id}/read', [CopilotWidgetController::class, 'markNotificationRead'])->name('widget.notifications.read');
    Route::post('/widget/notifications/read-all', [CopilotWidgetController::class, 'markAllNotificationsRead'])->name('widget.notifications.read-all');

    // Backward-compatible Playground & completion endpoints
    Route::get('/playground', [AIController::class, 'index'])->name('playground');
    Route::post('/generate', [AIController::class, 'generate'])->name('generate');
});
