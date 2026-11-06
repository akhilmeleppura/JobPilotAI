<?php

namespace App\Modules\Applications\app\Http\Controllers;

use App\Modules\Applications\app\Models\Application;
use App\Modules\Applications\app\Services\ApplicationRegistryService;
use App\Modules\Applications\app\Services\DuplicateCheckerService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        protected ApplicationRegistryService $registryService,
        protected DuplicateCheckerService $duplicateChecker
    ) {}

    /**
     * Display all applications with stage tabs and metrics.
     */
    public function index(Request $request): View
    {
        $userId = Auth::id();
        $status = $request->query('status');
        $search = $request->query('q');

        $query = Application::with(['vacancy', 'roleProfile', 'registry'])
            ->where('user_id', $userId)
            ->latest();

        if ($status && $status !== 'all') {
            $query->where('application_status', $status);
        }

        if ($search) {
            $query->whereHas('vacancy', function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate(15)->withQueryString();

        // Stage metrics
        $metrics = [
            'total' => Application::where('user_id', $userId)->count(),
            'staged' => Application::where('user_id', $userId)->where('application_status', 'Staged')->count(),
            'sent' => Application::where('user_id', $userId)->where('application_status', 'Sent')->count(),
            'interviews' => Application::where('user_id', $userId)->where('application_status', 'Interview')->count(),
            'offers' => Application::where('user_id', $userId)->where('application_status', 'Offer')->count(),
            'rejected' => Application::where('user_id', $userId)->where('application_status', 'Rejected')->count(),
        ];

        return view('applications::index', compact('applications', 'metrics', 'status', 'search'));
    }

    /**
     * Show form to manually stage an application.
     */
    public function create(): View
    {
        $roleProfiles = RoleProfile::where('user_id', Auth::id())->where('is_active', true)->get();
        return view('applications::create', compact('roleProfiles'));
    }

    /**
     * Stage an application with strict duplicate prevention.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'work_mode' => ['required', 'string', 'in:Remote,Hybrid,Onsite'],
            'apply_method' => ['required', 'string', 'in:Email,Portal,LinkedIn,Direct'],
            'apply_url' => ['nullable', 'url', 'max:1000'],
            'external_job_id' => ['nullable', 'string', 'max:150'],
            'hr_email' => ['nullable', 'email', 'max:255'],
            'role_profile_id' => ['nullable', 'exists:role_profiles,id'],
            'discovered_from' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'override_duplicate' => ['nullable', 'boolean'],
        ]);

        // Run duplicate check
        $check = $this->duplicateChecker->check(
            $user->id,
            $validated['company_name'],
            $validated['job_title'],
            $validated['location'] ?? null,
            $validated['external_job_id'] ?? null,
            $validated['apply_url'] ?? null
        );

        if (!$check['can_apply'] && empty($validated['override_duplicate'])) {
            return back()->withInput()->withErrors([
                'duplicate' => $check['message'],
            ]);
        }

        $application = $this->registryService->stageApplication(
            $user,
            $validated,
            $validated['role_profile_id'] ?? null,
            $validated['apply_method'],
            $validated['discovered_from'] ?? 'Manual',
            $validated['notes'] ?? null
        );

        return redirect()->route('applications.show', $application->id)
            ->with('success', 'Opportunity successfully registered in your permanent application archive.');
    }

    /**
     * Show detailed application view with events and fingerprint inspection.
     */
    public function show(int $id): View
    {
        $application = Application::with(['vacancy', 'roleProfile', 'registry.identityKeys', 'events'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('applications::show', compact('application'));
    }

    /**
     * Update application status.
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $application = Application::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Draft,Staged,Sent,Replied,Interview,Offer,Rejected'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->registryService->updateStatus($application, $validated['status'], $validated['note'] ?? null);

        return redirect()->route('applications.show', $application->id)
            ->with('success', "Status updated to {$validated['status']}.");
    }

    /**
     * AJAX endpoint to check duplicates in real-time.
     */
    public function apiCheckDuplicate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string'],
            'job_title' => ['required', 'string'],
            'location' => ['nullable', 'string'],
            'external_job_id' => ['nullable', 'string'],
            'apply_url' => ['nullable', 'string'],
        ]);

        $result = $this->duplicateChecker->check(
            Auth::id(),
            $validated['company_name'],
            $validated['job_title'],
            $validated['location'] ?? null,
            $validated['external_job_id'] ?? null,
            $validated['apply_url'] ?? null
        );

        return response()->json($result);
    }
}

