<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Dashboard\app\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);

