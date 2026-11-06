<?php

namespace App\Modules\Jobs\app\Http\Controllers;

use App\Modules\Email\app\Models\EmailTemplate;
use App\Modules\Email\app\Services\EmailTemplateService;
use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\Jobs\app\Services\JobDiscoveryService;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Services\CVAssignmentResolverService;
use App\Modules\Resume\app\Services\ResumeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JobDiscoveryController extends Controller
{
    public function __construct(
        protected JobDiscoveryService $discoveryService,
        protected CVAssignmentResolverService $cvResolver,
        protected ResumeService $resumeService,
        protected EmailTemplateService $templateService
    ) {}

    /**
     * Display discovered job listings with multi-filter controls and staging actions.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $this->resumeService->getOrSeedResumesForUser($user);
        $this->templateService->getOrSeedTemplatesForUser($user);

        $query = JobVacancy::with('company')->orderByDesc('created_at');

        // Text Search
        if ($search = $request->input('query')) {
            $query->where(function ($q) use ($search) {
                $q->where('job_title', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Provider Filter
        if ($provider = $request->input('provider')) {
            if ($provider !== 'all') {
                $query->where('source_provider', 'like', "%{$provider}%");
            }
        }

        // Work Mode Filter
        if ($mode = $request->input('work_mode')) {
            if ($mode !== 'all') {
                $query->where('work_mode', $mode);
            }
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $vacancies = $query->paginate(15)->withQueryString();

        $metrics = [
            'total_discovered' => JobVacancy::count(),
            'remote_count' => JobVacancy::where('work_mode', 'Remote')->count(),
            'staged_count' => JobVacancy::where('status', 'Staged')->count(),
            'providers_count' => count($this->discoveryService->getAdapters()),
        ];

        $resumes = Resume::where('user_id', $user->id)->get();
        $templates = EmailTemplate::where('user_id', $user->id)->get();
        $adapters = $this->discoveryService->getAdapters();

        return view('jobs::discovery.index', compact('vacancies', 'metrics', 'resumes', 'templates', 'adapters'));
    }

    /**
     * Trigger real-time job fetch across all integrated free adapters.
     */
    public function fetch(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $criteria = [
            'query' => $request->input('query', 'PHP Laravel'),
            'limit' => 15,
        ];

        $result = $this->discoveryService->fetchAndIngestAll($user, $criteria);

        $msg = "Discovered {$result['fetched']} job postings across active providers. ({$result['new_vacancies']} new openings added, {$result['duplicates']} existing synced).";

        return redirect()->route('jobs.index')->with('success', $msg);
    }

    /**
     * Show vacancy details and AI CV match analysis.
     */
    public function show(int $id): View|JsonResponse
    {
        $user = Auth::user();
        $vacancy = JobVacancy::with('company')->findOrFail($id);

        $cvMatch = $this->cvResolver->resolveCV(
            user: $user,
            vacancyContext: [
                'job_title' => $vacancy->job_title,
                'description' => $vacancy->description ?? '',
                'skills' => $vacancy->skills ?? [],
            ]
        );

        if (request()->wantsJson()) {
            return response()->json([
                'vacancy' => $vacancy,
                'cv_match' => $cvMatch,
            ]);
        }

        return view('jobs::discovery.show', compact('vacancy', 'cvMatch'));
    }

    /**
     * 1-Click Stage for Outreach: creates Application record and links optimal CV.
     */
    public function stage(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $overrideResumeId = $request->input('resume_id') ? (int) $request->input('resume_id') : null;
        $overrideTemplateId = $request->input('template_id') ? (int) $request->input('template_id') : null;

        $result = $this->discoveryService->stageApplication(
            user: $user,
            jobVacancyId: $id,
            overrideResumeId: $overrideResumeId,
            overrideTemplateId: $overrideTemplateId
        );

        $cvTitle = $result['cv_recommendation']['resume']?->title ?? 'Master CV';
        $mode = $result['cv_recommendation']['mode'];

        return redirect()->route('applications.index')->with(
            'success',
            "Opportunity '{$result['application']->registry?->job_title_normalized}' staged successfully! Matched via {$mode} with '{$cvTitle}'."
        );
    }
}

