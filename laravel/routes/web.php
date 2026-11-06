<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Diagnostic route outside module middleware
Route::get('/debug-check', function () {
    try {
        $dbStatus = 'NOT_CONNECTED';
        $userEmail = null;
        try {
            DB::connection()->getPdo();
            $dbStatus = 'CONNECTED (' . config('database.default') . ')';
            $user = \App\Models\User::first();
            $userEmail = $user?->email;
        } catch (\Throwable $dbe) {
            $dbStatus = 'DB_ERROR: ' . $dbe->getMessage();
        }

        return response()->json([
            'status' => 'OK',
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_key_set' => !empty(config('app.key')),
            'app_key_length' => strlen(config('app.key') ?? ''),
            'session_driver' => config('session.driver'),
            'database_status' => $dbStatus,
            'first_user' => $userEmail,
            'php_version' => PHP_VERSION,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ], 500);
    }
});

// Root and core domain routes are registered by their respective Modules (Dashboard, Identity, Jobs, etc.)
