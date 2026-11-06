<?php

use App\Modules\Monitoring\app\Http\Controllers\SystemMonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('system-health')->name('monitoring.')->group(function () {
    Route::get('/', [SystemMonitoringController::class, 'index'])->name('index');
    Route::post('/backup', [SystemMonitoringController::class, 'createBackup'])->name('backup');
    Route::get('/backup/{id}/download', [SystemMonitoringController::class, 'downloadBackup'])->name('backup.download');
    Route::get('/api', [SystemMonitoringController::class, 'healthJson'])->name('api');
});
