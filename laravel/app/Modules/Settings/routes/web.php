<?php

use App\Modules\Settings\app\Http\Controllers\AdminControlController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminControlController::class, 'index'])->name('cockpit');
    Route::post('/emergency-stop/toggle', [AdminControlController::class, 'toggleEmergencyStop'])->name('emergency-stop.toggle');
    Route::post('/cache/flush', [AdminControlController::class, 'flushSystemCache'])->name('cache.flush');
    Route::post('/maintenance/run', [AdminControlController::class, 'runMaintenance'])->name('maintenance.run');
});
