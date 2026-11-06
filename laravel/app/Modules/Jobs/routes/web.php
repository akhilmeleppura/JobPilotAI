<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Jobs\app\Http\Controllers\AIJobSearchController;
use App\Modules\Jobs\app\Http\Controllers\JobDiscoveryController;

Route::middleware('auth')->prefix('jobs')->name('jobs.')->group(function () {
    Route::get('/', [JobDiscoveryController::class, 'index'])->name('index');
    Route::post('/fetch', [JobDiscoveryController::class, 'fetch'])->name('fetch');

    // Natural Language AI Job Search & Matching
    Route::get('/ai-search', [AIJobSearchController::class, 'index'])->name('ai-search');
    Route::get('/{id}/match-score', [AIJobSearchController::class, 'matchScore'])->name('match-score');

    Route::get('/{id}', [JobDiscoveryController::class, 'show'])->name('show');
    Route::post('/{id}/stage', [JobDiscoveryController::class, 'stage'])->name('stage');
});
