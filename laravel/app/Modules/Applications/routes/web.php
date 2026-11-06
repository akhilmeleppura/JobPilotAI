<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Applications\app\Http\Controllers\ApplicationController;

Route::middleware('auth')->prefix('applications')->name('applications.')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])->name('index');
    Route::get('/create', [ApplicationController::class, 'create'])->name('create');
    Route::post('/', [ApplicationController::class, 'store'])->name('store');
    Route::get('/{id}', [ApplicationController::class, 'show'])->name('show');
    Route::post('/{id}/status', [ApplicationController::class, 'updateStatus'])->name('update-status');
    Route::post('/api/check-duplicate', [ApplicationController::class, 'apiCheckDuplicate'])->name('check-duplicate');
});
