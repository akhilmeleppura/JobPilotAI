<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Resume\app\Http\Controllers\CVAssignmentController;
use App\Modules\Resume\app\Http\Controllers\ResumeEditorController;

Route::middleware('auth')->prefix('cv-studio')->name('resume.')->group(function () {
    // CV Assignment Manager
    Route::get('/assignment', [CVAssignmentController::class, 'index'])->name('assignment.index');
    Route::post('/assignment/{id}/set-default', [CVAssignmentController::class, 'setDefault'])->name('assignment.set-default');
    Route::post('/assignment/{id}/map-role', [CVAssignmentController::class, 'mapRole'])->name('assignment.map-role');
    Route::post('/assignment/test-match', [CVAssignmentController::class, 'testMatch'])->name('assignment.test-match');

    // LaTeX CV Studio & Editor
    Route::get('/editor/{id}', [ResumeEditorController::class, 'index'])->name('editor.index');
    Route::post('/editor/{id}/compile', [ResumeEditorController::class, 'compile'])->name('editor.compile');
    Route::get('/editor/{id}/preview-pdf', [ResumeEditorController::class, 'previewPdf'])->name('editor.preview-pdf');
    Route::get('/editor/{id}/download-pdf/{version?}', [ResumeEditorController::class, 'downloadPdf'])->name('editor.download-pdf');
    Route::get('/editor/{id}/download-tex/{version?}', [ResumeEditorController::class, 'downloadTex'])->name('editor.download-tex');
    Route::post('/editor/{id}/save-version', [ResumeEditorController::class, 'saveVersion'])->name('editor.save-version');
    Route::post('/editor/{id}/apply-template', [ResumeEditorController::class, 'applyTemplate'])->name('editor.apply-template');
    Route::post('/editor/{id}/ats-analyze', [ResumeEditorController::class, 'analyzeAts'])->name('editor.ats-analyze');

    // Default redirect to assignment overview
    Route::get('/', function () {
        return redirect()->route('resume.assignment.index');
    })->name('index');
});
