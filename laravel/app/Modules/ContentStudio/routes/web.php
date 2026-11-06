<?php

use App\Modules\ContentStudio\app\Http\Controllers\ContentStudioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('content-studio')->name('content.')->group(function () {
    Route::get('/', [ContentStudioController::class, 'index'])->name('index');
    Route::post('/topics', [ContentStudioController::class, 'topics'])->name('topics');
    Route::post('/draft', [ContentStudioController::class, 'draft'])->name('draft');
    Route::post('/generate-image', [ContentStudioController::class, 'generateImage'])->name('generate-image');
    Route::post('/save', [ContentStudioController::class, 'save'])->name('save');
    Route::post('/{id}/schedule', [ContentStudioController::class, 'schedule'])->name('schedule');
    Route::delete('/{id}', [ContentStudioController::class, 'destroy'])->name('destroy');
});
