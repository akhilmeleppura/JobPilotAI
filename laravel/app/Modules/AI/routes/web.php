<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AI\app\Http\Controllers\AIChatWorkspaceController;
use App\Modules\AI\app\Http\Controllers\AIController;

Route::middleware('auth')->prefix('ai-chat')->name('ai.')->group(function () {
    // Full Career Copilot Chat Workspace
    Route::get('/', [AIChatWorkspaceController::class, 'index'])->name('chat.index');
    Route::post('/session', [AIChatWorkspaceController::class, 'createSession'])->name('chat.create-session');
    Route::post('/session/{id}/message', [AIChatWorkspaceController::class, 'sendMessage'])->name('chat.send-message');
    Route::delete('/session/{id}', [AIChatWorkspaceController::class, 'deleteSession'])->name('chat.delete-session');

    // Backward-compatible Playground & completion endpoints
    Route::get('/playground', [AIController::class, 'index'])->name('playground');
    Route::post('/generate', [AIController::class, 'generate'])->name('generate');
});
