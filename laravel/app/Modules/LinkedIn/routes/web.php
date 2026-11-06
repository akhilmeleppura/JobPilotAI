<?php

use App\Modules\LinkedIn\app\Http\Controllers\LinkedInController;
use App\Modules\LinkedIn\app\Http\Controllers\LinkedInPublishingController;
use Illuminate\Support\Facades\Route;

// LinkedIn Profile Optimizer Workbench
Route::middleware('auth')->prefix('linkedin-optimizer')->name('linkedin.')->group(function () {
    Route::get('/', [LinkedInController::class, 'index'])->name('optimizer');
    Route::post('/audit', [LinkedInController::class, 'audit'])->name('audit');
    Route::post('/generate', [LinkedInController::class, 'generate'])->name('generate');
    Route::post('/save', [LinkedInController::class, 'save'])->name('save');
    Route::post('/transform-bullets', [LinkedInController::class, 'transformBullets'])->name('transform-bullets');
});

// Authorized LinkedIn Publishing & Human-in-the-Loop Review
Route::middleware('auth')->prefix('linkedin')->name('linkedin.')->group(function () {
    Route::get('/publishing', [LinkedInPublishingController::class, 'index'])->name('publishing');
    Route::get('/auth/redirect', [LinkedInPublishingController::class, 'redirectOAuth'])->name('auth.redirect');
    Route::get('/auth/callback', [LinkedInPublishingController::class, 'callbackOAuth'])->name('auth.callback');
    Route::post('/auth/disconnect', [LinkedInPublishingController::class, 'disconnect'])->name('auth.disconnect');
    Route::post('/publishing/{id}/approve', [LinkedInPublishingController::class, 'approve'])->name('publishing.approve');
    Route::post('/publishing/{id}/reject', [LinkedInPublishingController::class, 'reject'])->name('publishing.reject');
});
