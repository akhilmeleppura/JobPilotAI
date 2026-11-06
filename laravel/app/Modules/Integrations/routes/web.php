<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Integrations\app\Http\Controllers\IntegrationController;

Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [IntegrationController::class, 'index'])->name('index');
    Route::put('/{id}', [IntegrationController::class, 'update'])->name('update');
    Route::post('/{id}/ping', [IntegrationController::class, 'ping'])->name('ping');
    Route::post('/{id}/toggle', [IntegrationController::class, 'toggle'])->name('toggle');
});
