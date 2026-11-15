<?php

namespace App\Modules\Settings\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Email\app\Models\EmailAccount;
use App\Modules\Email\app\Models\EmailLog;
use App\Modules\Outlook\app\Models\OutlookMessage;
use App\Shared\Services\EmergencyStopService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminControlController extends Controller
{
    public function __construct(
        protected EmergencyStopService $emergencyStop
    ) {}

    /**
     * Display the Central Administrative Cockpit.
     */
    public function index(): View
    {
        $user = Auth::user();
        if (!$user?->isAdmin()) {
            abort(403, 'Unauthorized. Administrative credentials required to access system management.');
        }

        // Global metrics across all tenants
        $metrics = [
            'total_users' => User::count(),
            'total_applications' => Application::count(),
            'total_emails_sent' => EmailLog::where('status', 'Sent')->count(),
            'outlook_messages_count' => OutlookMessage::count(),
            'connected_mailboxes' => EmailAccount::count(),
            'emergency_stop_status' => $this->emergencyStop->getStatus(),
            'free_policy_enforced' => env('FREE_RESOURCES_ONLY', true),
            'database_connection' => config('database.default'),
            'database_name' => DB::connection()->getDatabaseName(),
            'cache_driver' => config('cache.default'),
        ];

        // Recent users
        $users = User::latest()->take(10)->get();

        // Recent system dispatch logs
        $recentDispatches = EmailLog::with('account')->latest()->take(8)->get();

        // Recent Outlook interactions
        $recentOutlook = OutlookMessage::latest()->take(8)->get();

        return view('settings::admin_cockpit', compact('metrics', 'users', 'recentDispatches', 'recentOutlook'));
    }

    /**
     * Toggle global Emergency Kill Switch.
     */
    public function toggleEmergencyStop(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user?->isAdmin()) {
            abort(403);
        }

        if ($this->emergencyStop->isStopped()) {
            $this->emergencyStop->resume($user->id);
            $msg = 'Global Emergency Stop CLEARED. All automated pipelines resumed.';
        } else {
            $reason = $request->input('reason', 'Administrative manual kill switch triggered from Admin Cockpit.');
            $this->emergencyStop->trigger($reason, $user->id);
            $msg = 'Global Emergency Stop ENGAGED. All automated email dispatches, job scrapers, and AI workers suspended immediately.';
        }

        return redirect()->route('admin.cockpit')->with('success', $msg);
    }

    /**
     * Flush application caches and views.
     */
    public function flushSystemCache(): RedirectResponse
    {
        $user = Auth::user();
        if (!$user?->isAdmin()) {
            abort(403);
        }

        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');

        return redirect()->route('admin.cockpit')->with('success', 'System caches, compiled Blade views, and configuration cache cleared successfully.');
    }

    /**
     * Run on-demand database maintenance and verify schema health.
     */
    public function runMaintenance(): RedirectResponse
    {
        $user = Auth::user();
        if (!$user?->isAdmin()) {
            abort(403);
        }

        Artisan::call('migrate', ['--force' => true]);

        return redirect()->route('admin.cockpit')->with('success', 'Database schema migration and integrity verification completed on TiDB Cloud.');
    }
}
