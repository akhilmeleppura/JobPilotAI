<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Archives\app\Http\Controllers\ArchiveController;

Route::middleware('auth')->prefix('archives')->name('archives.')->group(function () {
    Route::get('/', [ArchiveController::class, 'index'])->name('index');
    Route::post('/generate', [ArchiveController::class, 'generate'])->name('generate');
    Route::get('/{id}/download-jsonl', [ArchiveController::class, 'downloadJsonl'])->name('download-jsonl');
    Route::get('/{id}/download-csv', [ArchiveController::class, 'downloadCsv'])->name('download-csv');
    Route::post('/{id}/sync-drive', [ArchiveController::class, 'syncDrive'])->name('sync-drive');
    Route::post('/{id}/verify-checksum', [ArchiveController::class, 'verifyChecksum'])->name('verify-checksum');
});
