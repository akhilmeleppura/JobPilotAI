<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Diagnostic route outside module middleware
Route::get('/debug-check', function () {
    try {
        $dbStatus = 'NOT_CONNECTED';
        $userEmail = null;
        $tables = [];
        $migrationsInDb = [];
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $dbStatus = 'CONNECTED (' . config('database.default') . ' -> ' . $dbName . ')';
            $user = \App\Models\User::first();
            $userEmail = $user?->email;

            // List all tables
            $rawTables = DB::select('SHOW TABLES');
            foreach ($rawTables as $row) {
                $tables[] = array_values((array)$row)[0];
            }

            // List migrations
            if (in_array('migrations', $tables)) {
                $migrationsInDb = DB::table('migrations')->pluck('migration')->toArray();
            }
        } catch (\Throwable $dbe) {
            $dbStatus = 'DB_ERROR: ' . $dbe->getMessage();
        }

        return response()->json([
            'status' => 'OK',
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'database_status' => $dbStatus,
            'first_user' => $userEmail,
            'tables_count' => count($tables),
            'tables' => $tables,
            'migrations_count' => count($migrationsInDb),
            'migrations' => $migrationsInDb,
            'php_version' => PHP_VERSION,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ], 500);
    }
});

Route::get('/run-migrations', function () {
    try {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        // If orphan table exists without being recorded in migrations, clean it up so migration succeeds
        $migrated = DB::table('migrations')->pluck('migration')->toArray();
        if (!in_array('2026_11_01_000001_create_content_posts_table', $migrated)) {
            DB::statement('DROP TABLE IF EXISTS content_posts');
        }

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

        // Also run every module migration path explicitly
        $moduleOutputs = [];
        foreach (glob(app_path('Modules/*/database/migrations')) as $modPath) {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--path' => $modPath, '--force' => true]);
            $moduleOutputs[basename(dirname($modPath))] = trim(\Illuminate\Support\Facades\Artisan::output());
        }

        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // Get final list of tables
        $tables = [];
        $rawTables = DB::select('SHOW TABLES');
        foreach ($rawTables as $row) {
            $tables[] = array_values((array)$row)[0];
        }

        return response()->json([
            'success' => true,
            'migrate_output' => explode("\n", trim($migrateOutput)),
            'module_outputs' => $moduleOutputs,
            'seed_output' => explode("\n", trim($seedOutput)),
            'final_tables_count' => count($tables),
            'final_tables' => $tables,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Root and core domain routes are registered by their respective Modules (Dashboard, Identity, Jobs, etc.)
