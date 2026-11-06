<?php

namespace App\Modules\Monitoring\app\Http\Controllers;

use App\Modules\Monitoring\app\Models\SystemBackup;
use App\Modules\Monitoring\app\Services\BackupService;
use App\Modules\Monitoring\app\Services\SystemMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemMonitoringController extends Controller
{
    public function __construct(
        protected SystemMonitoringService $monitoringService,
        protected BackupService $backupService
    ) {}

    /**
     * Display system health dashboard and backup management hub.
     */
    public function index(): View
    {
        $health = $this->monitoringService->getOverallHealth();
        $backups = SystemBackup::orderByDesc('created_at')->get();

        return view('monitoring::index', [
            'health' => $health,
            'backups' => $backups,
        ]);
    }

    /**
     * Create an immediate database backup.
     */
    public function createBackup(Request $request): JsonResponse|RedirectResponse
    {
        $backup = $this->backupService->createBackup();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully!',
                'backup' => $backup,
            ]);
        }

        return redirect()->route('monitoring.index')
            ->with('status', "Database backup created successfully: {$backup->filename} ({$backup->formatted_size})");
    }

    /**
     * Download backup file with integrity verification.
     */
    public function downloadBackup(int $id): StreamedResponse|RedirectResponse
    {
        $backup = SystemBackup::findOrFail($id);

        if (! $this->backupService->verifyBackup($backup)) {
            return redirect()->route('monitoring.index')
                ->with('error', 'Backup checksum mismatch or file missing. Download aborted.');
        }

        $filePath = $this->backupService->getBackupFilePath($backup);
        if (! $filePath) {
            return redirect()->route('monitoring.index')
                ->with('error', 'Backup file not found on disk.');
        }

        return response()->streamDownload(function () use ($filePath) {
            readfile($filePath);
        }, $backup->filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * JSON health endpoint.
     */
    public function healthJson(): JsonResponse
    {
        $health = $this->monitoringService->getOverallHealth();

        return response()->json($health);
    }
}

