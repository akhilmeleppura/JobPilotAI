<?php

namespace App\Modules\Jobs\app\Http\Controllers;

use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\Jobs\app\Services\JobMatchScoringService;
use App\Modules\Jobs\app\Services\NaturalLanguageJobSearchService;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AIJobSearchController extends Controller
{
    public function __construct(
        protected NaturalLanguageJobSearchService $searchService,
        protected JobMatchScoringService $scoringService,
        protected RoleProfileService $profileService
    ) {}

    /**
     * Display conversational AI job search interface and ranked matches.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $roleProfiles = $this->profileService->getProfilesForUser($user->id);
        if ($roleProfiles->isEmpty()) {
            $roleProfiles = $this->profileService->seedDefaultProfilesForUser($user);
        }

        $selectedProfileId = $request->query('role_profile_id');
        $activeProfile = $selectedProfileId
            ? $roleProfiles->firstWhere('id', (int) $selectedProfileId)
            : $roleProfiles->firstWhere('is_default', true) ?? $roleProfiles->first();

        $prompt = $request->input('prompt');
        $parsedParams = null;
        $rankedResults = collect();

        if (!empty($prompt)) {
            $parsedParams = $this->searchService->parseQuery($prompt, $user);

            $query = JobVacancy::with('company');

            // Apply parsed work mode if specified
            if (!empty($parsedParams['work_mode']) && $parsedParams['work_mode'] !== 'Any') {
                $query->where('work_mode', $parsedParams['work_mode']);
            }

            // Apply parsed salary minimum if specified
            if (!empty($parsedParams['salary_min'])) {
                $query->where(function ($q) use ($parsedParams) {
                    $q->where('salary_min', '>=', $parsedParams['salary_min'])
                      ->orWhere('salary_max', '>=', $parsedParams['salary_min'])
                      ->orWhereNull('salary_min'); // Don't exclude competitive unlisted roles
                });
            }

            // Keyword filtering across title, skills, and description
            if (!empty($parsedParams['keywords'])) {
                $query->where(function ($q) use ($parsedParams) {
                    foreach ($parsedParams['keywords'] as $kw) {
                        $q->orWhere('job_title', 'like', "%{$kw}%")
                          ->orWhere('description', 'like', "%{$kw}%");
                    }
                });
            }

            $vacancies = $query->take(30)->get();

            // Evaluate multi-factor match score for each vacancy
            foreach ($vacancies as $vac) {
                $match = $this->scoringService->evaluateMatch($vac, $activeProfile, $parsedParams);
                $vac->match_evaluation = $match;
                $rankedResults->push($vac);
            }

            $rankedResults = $rankedResults->sortByDesc(fn($v) => $v->match_evaluation['score'])->values();
        }

        $resumes = Resume::where('user_id', $user->id)->get();

        return view('jobs::search.ai', [
            'prompt' => $prompt,
            'parsedParams' => $parsedParams,
            'rankedResults' => $rankedResults,
            'roleProfiles' => $roleProfiles,
            'activeProfile' => $activeProfile,
            'resumes' => $resumes,
        ]);
    }

    /**
     * Get JSON match evaluation for a single vacancy against a role profile.
     */
    public function matchScore(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $vacancy = JobVacancy::with('company')->findOrFail($id);

        $roleProfileId = $request->query('role_profile_id');
        $profile = $roleProfileId
            ? RoleProfile::where('user_id', $user->id)->find($roleProfileId)
            : RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();

        $match = $this->scoringService->evaluateMatch($vacancy, $profile);

        return response()->json([
            'vacancy_id' => $vacancy->id,
            'job_title' => $vacancy->job_title,
            'company_name' => $vacancy->company_name,
            'role_profile' => $profile?->name ?? 'Default Persona',
            'evaluation' => $match,
        ]);
    }
}

