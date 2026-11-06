<?php

namespace App\Modules\Resume\app\Http\Controllers;

use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Services\CVAssignmentResolverService;
use App\Modules\Resume\app\Services\ResumeService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CVAssignmentController extends Controller
{
    public function __construct(
        protected ResumeService $resumeService,
        protected CVAssignmentResolverService $resolverService
    ) {}

    /**
     * Display CV Assignment Manager overview.
     */
    public function index(): View
    {
        $user = Auth::user();
        $resumes = $this->resumeService->getOrSeedResumesForUser($user);
        $roleProfiles = RoleProfile::where('user_id', $user->id)->get();

        $metrics = [
            'total_resumes' => $resumes->count(),
            'global_default' => $resumes->firstWhere('is_global_default', true)?->title ?? 'None Set',
            'role_mapped_count' => $resumes->whereNotNull('role_profile_id')->count(),
            'avg_ats_score' => round($resumes->avg(fn($r) => $r->currentVersion?->ats_score ?? 85)),
        ];

        return view('resume::assignment.index', compact('resumes', 'roleProfiles', 'metrics'));
    }

    /**
     * Set selected resume as the primary Global Default CV (Mode 1).
     */
    public function setDefault(int $id): RedirectResponse
    {
        $user = Auth::user();
        $this->resolverService->setGlobalDefault($user, $id);

        $resume = Resume::find($id);
        return redirect()->route('resume.assignment.index')->with(
            'success',
            "Resume '{$resume?->title}' is now your Global Default CV across all outreach."
        );
    }

    /**
     * Map a resume to a specific Career Role Profile (Mode 2).
     */
    public function mapRole(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $roleId = $request->input('role_profile_id') ? (int) $request->input('role_profile_id') : null;

        $this->resolverService->mapResumeToRole($user, $id, $roleId);

        $role = $roleId ? RoleProfile::find($roleId) : null;
        $roleMsg = $role ? "mapped to target persona '{$role->name}'" : "unlinked from role profile";

        return redirect()->route('resume.assignment.index')->with(
            'success',
            "CV assignment updated: {$roleMsg}."
        );
    }

    /**
     * Test semantic ATS keyword matching against an opportunity (Mode 4).
     */
    public function testMatch(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'job_title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'skills' => ['nullable', 'string'],
        ]);

        $skillsArray = array_values(array_filter(array_map('trim', explode(',', $validated['skills'] ?? ''))));

        $recommendation = $this->resolverService->resolveCV(
            user: $user,
            vacancyContext: [
                'job_title' => $validated['job_title'],
                'description' => $validated['description'] ?? '',
                'skills' => $skillsArray,
            ]
        );

        return response()->json([
            'recommended_resume' => $recommendation['resume']?->title,
            'template' => $recommendation['version']?->template_name,
            'mode' => $recommendation['mode'],
            'match_score' => $recommendation['match_score'],
            'reasoning' => $recommendation['reasoning'],
        ]);
    }
}

