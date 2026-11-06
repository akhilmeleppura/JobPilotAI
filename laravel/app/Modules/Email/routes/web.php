<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Email\app\Http\Controllers\EmailController;
use App\Modules\Email\app\Http\Controllers\EmailTemplateController;

Route::middleware('auth')->prefix('email-studio')->name('email.')->group(function () {
    Route::get('/', [EmailController::class, 'index'])->name('index');
    Route::post('/accounts', [EmailController::class, 'storeAccount'])->name('store-account');
    Route::post('/accounts/{id}/toggle', [EmailController::class, 'toggleAccount'])->name('toggle-account');
    Route::post('/send', [EmailController::class, 'send'])->name('send');
    Route::post('/review/{id}/approve', [EmailController::class, 'approveReview'])->name('approve-review');
    Route::post('/review/{id}/reject', [EmailController::class, 'rejectReview'])->name('reject-review');

    // Automatic Switching & Failover Controls
    Route::post('/switching-settings', [EmailController::class, 'updateSwitchingSettings'])->name('update-switching-settings');
    Route::post('/provider-priority', [EmailController::class, 'updatePriority'])->name('update-priority');

    // Email Templates Sub-routes
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
        Route::get('/create', [EmailTemplateController::class, 'create'])->name('create');
        Route::post('/', [EmailTemplateController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [EmailTemplateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EmailTemplateController::class, 'update'])->name('update');
        Route::delete('/{id}', [EmailTemplateController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
        Route::post('/{id}/tailor-ai', [EmailTemplateController::class, 'tailorWithAI'])->name('tailor-ai');
    });
});
