<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ImportExport\app\Http\Controllers\ImportExportController;

Route::middleware('auth')->prefix('import')->name('import.')->group(function () {
    Route::get('/', [ImportExportController::class, 'index'])->name('index');
    Route::post('/upload', [ImportExportController::class, 'upload'])->name('upload');
    Route::post('/confirm', [ImportExportController::class, 'confirm'])->name('confirm');
    Route::get('/template', [ImportExportController::class, 'template'])->name('template');
});
