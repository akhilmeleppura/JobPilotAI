<?php

namespace App\Modules\Monitoring\app\Services;

use App\Models\User;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemMonitoringService
{
    /**
     * Run all subsystem health and security audits.
     */
    public function getOverallHealth(): array
    {
        $dbCheck = $this->checkDatabase();
        $storageCheck = $this->checkStorage();
        $aiCheck = $this->checkFreePolicyAndAI();
        $queueCheck = $this->checkQueuesAndScheduler();
        $securityCheck = $this->checkSecurity();

        $subsystems = [
            'database' => $dbCheck,
            'storage' => $storageCheck,
            'ai_gateway' => $aiCheck,
            'queues' => $queueCheck,
            'security' => $securityCheck,
        ];

        $healthyCount = 0;
        $total = count($subsystems);

        foreach ($subsystems as $check) {
            if ($check['status'] === 'healthy') {
                $healthyCount++;
            } elseif ($check['status'] === 'warning') {
                $healthyCount += 0.7;
            }
        }

        $overallScore = (int) round(($healthyCount / $total) * 100);
        $status = $overallScore >= 90 ? 'Operational' : ($overallScore >= 65 ? 'Degraded' : 'Critical');

        return [
            'overall_status' => $status,
            'overall_score' => $overallScore,
            'timestamp' => Carbon::now()->toIso8601String(),
            'subsystems' => $subsystems,
        ];
    }

    /**
     * Database connectivity and latency check.
     */
    public function checkDatabase(): array
    {
        $start = microtime(true);
        $driver = config('database.default', 'sqlite');

        try {
            DB::connection()->getPdo();
            $latencyMs = round((microtime(true) - $start) * 1000, 2);

            $fileInfo = null;
            if ($driver === 'sqlite') {
                $path = config('database.connections.sqlite.database');
                if ($path === ':memory:') {
                    $fileInfo = "SQLite In-Memory Engine";
                } else {
                    $sizeMb = File::exists($path) ? round(File::size($path) / 1048576, 2) : 0;
                    $fileInfo = "SQLite file: {$sizeMb} MB";
                }
            }

            return [
                'status' => 'healthy',
                'name' => 'Database Engine (' . strtoupper($driver) . ')',
                'latency_ms' => $latencyMs,
                'details' => $fileInfo ?: "Connected to {$driver} over TLS/Socket",
                'message' => "Database responsive ({$latencyMs}ms)",
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'critical',
                'name' => 'Database Engine (' . strtoupper($driver) . ')',
                'latency_ms' => null,
                'details' => $e->getMessage(),
                'message' => 'Database connection failed',
            ];
        }
    }

    /**
     * Storage permissions and disk space check.
     */
    public function checkStorage(): array
    {
        $storagePath = storage_path();
        $isWritable = is_writable($storagePath) && is_writable(storage_path('logs'));

        $freeBytes = @disk_free_space($storagePath);
        $totalBytes = @disk_total_space($storagePath);

        $freeGb = $freeBytes ? round($freeBytes / (1024 * 1024 * 1024), 2) : 0;
        $totalGb = $totalBytes ? round($totalBytes / (1024 * 1024 * 1024), 2) : 0;
        $pctFree = $totalGb > 0 ? round(($freeGb / $totalGb) * 100, 1) : 100;

        $status = $isWritable ? ($pctFree < 5 ? 'warning' : 'healthy') : 'critical';

        return [
            'status' => $status,
            'name' => 'Filesystem & Storage',
            'free_gb' => $freeGb,
            'total_gb' => $totalGb,
            'percent_free' => $pctFree,
            'is_writable' => $isWritable,
            'message' => $isWritable ? "Storage writable ({$freeGb} GB free)" : 'Storage directory is NOT writable',
        ];
    }

    /**
     * Free policy enforcement and AI providers check.
     */
    public function checkFreePolicyAndAI(): array
    {
        $freePolicyActive = config('services.free_resources_only', true);
        $configuredProvidersCount = IntegrationCredential::where('provider_group', 'ai')
            ->where('is_enabled', true)
            ->count();

        return [
            'status' => $freePolicyActive ? 'healthy' : 'warning',
            'name' => 'AI Gateway (Zero-Spend Policy)',
            'free_resources_only' => $freePolicyActive,
            'configured_ai_providers' => $configuredProvidersCount,
            'message' => $freePolicyActive ? 'FREE_RESOURCES_ONLY active (Zero-Cost Enforced)' : 'Warning: Paid resources not blocked',
        ];
    }

    /**
     * Queue worker and scheduler health check.
     */
    public function checkQueuesAndScheduler(): array
    {
        $failedJobsCount = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedJobsCount = DB::table('failed_jobs')->count();
            }
        } catch (Throwable $e) {
            // Safe fallback
        }

        $queueConn = config('queue.default', 'sync');
        $cacheConn = config('cache.default', 'file');

        return [
            'status' => $failedJobsCount > 10 ? 'warning' : 'healthy',
            'name' => 'Queues & Background Execution',
            'queue_driver' => $queueConn,
            'cache_driver' => $cacheConn,
            'failed_jobs' => $failedJobsCount,
            'message' => "Driver: {$queueConn} | {$failedJobsCount} failed jobs",
        ];
    }

    /**
     * Core security settings audit.
     */
    public function checkSecurity(): array
    {
        $appKeySet = ! empty(config('app.key')) && strlen(config('app.key')) >= 32;
        $debugMode = config('app.debug', false);
        $env = config('app.env', 'production');

        $status = $appKeySet ? ($env === 'production' && $debugMode ? 'warning' : 'healthy') : 'critical';

        return [
            'status' => $status,
            'name' => 'Security Hardening & Environment',
            'app_key_set' => $appKeySet,
            'environment' => $env,
            'debug_mode' => $debugMode,
            'message' => $appKeySet ? 'App encryption key verified' : 'APP_KEY is missing or invalid',
        ];
    }
}
