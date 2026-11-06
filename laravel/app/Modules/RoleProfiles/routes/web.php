<?php

use Illuminate\Support\Facades\Route;
use App\Modules\RoleProfiles\app\Http\Controllers\RoleProfileController;

Route::middleware('auth')->prefix('role-profiles')->name('roleprofiles.')->group(function () {
    Route::get('/', [RoleProfileController::class, 'index'])->name('index');
    Route::get('/create', [RoleProfileController::class, 'create'])->name('create');
    Route::post('/', [RoleProfileController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [RoleProfileController::class, 'edit'])->name('edit');
    Route::put('/{id}', [RoleProfileController::class, 'update'])->name('update');
    Route::delete('/{id}', [RoleProfileController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/set-default', [RoleProfileController::class, 'setDefault'])->name('set-default');
    Route::post('/{id}/toggle-active', [RoleProfileController::class, 'toggleActive'])->name('toggle-active');
});
