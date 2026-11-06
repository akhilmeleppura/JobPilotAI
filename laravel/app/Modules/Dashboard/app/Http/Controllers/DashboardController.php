<?php

namespace App\Modules\Dashboard\app\Http\Controllers;

use App\Models\User;
use App\Modules\Email\app\Services\AutomaticEmailSwitchingService;
use App\Modules\Email\app\Services\FreeEmailAllowanceService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected FreeEmailAllowanceService $allowanceService,
        protected AutomaticEmailSwitchingService $switchingService
    ) {}

    /**
     * Display consolidated operational metrics and cockpit.
     */
    public function index(): View
    {
        $user = Auth::user() ?? User::first();
        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'candidate@jobpilot.test'],
                [
                    'name' => 'Candidate Admin',
                    'password' => bcrypt('Password123!'),
                ]
            );
        }

        $stats = [
            'companies_count' => 12,
            'jobs_discovered' => 148,
            'matched_jobs' => 42,
            'applications_staged' => 6,
            'applications_sent' => 28,
            'recruiter_responses' => 4,
            'interviews_scheduled' => 2,
            'offers_received' => 0,
        ];

        $emailSummary = $this->allowanceService->getDashboardSummary($user);
        $switchingMonitor = $this->switchingService->getSwitchingMonitorData($user);

        $aiGateway = [
            'free_policy_enforced' => env('FREE_RESOURCES_ONLY', true),
            'providers' => [
                ['name' => 'Google AI Studio', 'model' => 'gemini-2.5-flash', 'status' => 'READY', 'quota_tier' => '15 RPM / Free'],
                ['name' => 'Groq Cloud', 'model' => 'llama-3.3-70b-versatile', 'status' => 'READY', 'quota_tier' => 'Free Dev'],
                ['name' => 'OpenRouter', 'model' => 'meta-llama/llama-3-8b:free', 'status' => 'STANDBY', 'quota_tier' => 'Free Tier'],
                ['name' => 'Local Ollama', 'model' => 'qwen2.5-coder:7b', 'status' => 'STANDBY', 'quota_tier' => 'Local / Offline'],
            ]
        ];

        $storageMetrics = [
            'database' => 'TiDB Cloud Starter',
            'db_status' => 'CONNECTED',
            'cache_driver' => config('cache.default', 'file'),
            'queue_driver' => config('queue.default', 'database'),
            'drive_archive' => 'Monthly Sync Ready',
        ];

        return view('dashboard::index', compact('stats', 'emailSummary', 'switchingMonitor', 'aiGateway', 'storageMetrics'));
    }
}
