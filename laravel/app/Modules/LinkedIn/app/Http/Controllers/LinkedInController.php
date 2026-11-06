<?php

namespace App\Modules\LinkedIn\app\Http\Controllers;

use App\Modules\LinkedIn\app\Models\LinkedInProfile;
use App\Modules\LinkedIn\app\Services\LinkedInOptimizerService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LinkedInController extends Controller
{
    public function __construct(
        protected LinkedInOptimizerService $optimizerService,
        protected RoleProfileService $profileService
    ) {}

    /**
     * Show LinkedIn Optimizer workbench.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Ensure role profiles are present
        if ($this->profileService->getProfilesForUser($user->id)->isEmpty()) {
            $this->profileService->seedDefaultProfilesForUser($user);
        }

        $profiles = $this->profileService->getProfilesForUser($user->id);
        $roleProfileId = $request->query('role_profile_id') ? (int) $request->query('role_profile_id') : null;

        $selectedProfile = $roleProfileId
            ? $profiles->firstWhere('id', $roleProfileId)
            : ($profiles->firstWhere('is_default', true) ?? $profiles->first());

        $linkedInProfile = $this->optimizerService->getOrCreateProfile($user, $selectedProfile?->id);

        $currentHeadline = $linkedInProfile->current_headline ?: ($linkedInProfile->optimized_headline ?: '');
        $currentAbout = $linkedInProfile->current_about ?: ($linkedInProfile->optimized_about ?: '');
        $currentExperience = $linkedInProfile->current_experience ?: ($linkedInProfile->optimized_experience ?: '');

        $audit = $this->optimizerService->auditProfile($user, $selectedProfile, [
            'headline' => $currentHeadline,
            'about' => $currentAbout,
            'experience' => $currentExperience,
        ]);

        return view('linkedin::optimizer.index', [
            'profile' => $linkedInProfile,
            'profiles' => $profiles,
            'selectedProfile' => $selectedProfile,
            'audit' => $audit,
        ]);
    }

    /**
     * Run real-time recruiter keyword gap audit.
     */
    public function audit(Request $request): JsonResponse
    {
        $user = Auth::user();

        $roleProfile = null;
        if ($request->filled('role_profile_id')) {
            $roleProfile = RoleProfile::where('user_id', $user->id)->find($request->input('role_profile_id'));
        }

        $audit = $this->optimizerService->auditProfile($user, $roleProfile, [
            'headline' => $request->input('headline', ''),
            'about' => $request->input('about', ''),
            'experience' => $request->input('experience', ''),
        ]);

        return response()->json([
            'success' => true,
            'audit' => $audit,
        ]);
    }

    /**
     * AI Generation for headlines, about, or experience bullets.
     */
    public function generate(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        $roleProfileId = $request->input('role_profile_id');
        $roleProfile = $roleProfileId
            ? RoleProfile::where('user_id', $user->id)->find($roleProfileId)
            : RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();

        if (! $roleProfile) {
            $roleProfile = $this->profileService->getProfilesForUser($user->id)->first();
        }

        $type = $request->input('type', 'all');
        $headlines = null;
        $about = null;
        $experience = null;

        if (in_array($type, ['all', 'headlines'])) {
            $headlines = $this->optimizerService->generateHeadlines(
                $user,
                $roleProfile,
                $request->input('current_headline')
            );
        }

        if (in_array($type, ['all', 'about'])) {
            $about = $this->optimizerService->generateAboutSection(
                $user,
                $roleProfile,
                $request->input('current_about')
            );
        }

        if (in_array($type, ['all', 'experience'])) {
            $experience = $this->optimizerService->optimizeExperienceBullets(
                $user,
                $request->input('current_experience', '')
            );
        }

        // Persist to user's profile model
        $profile = $this->optimizerService->getOrCreateProfile($user, $roleProfile?->id);

        $updates = [];
        if ($headlines) {
            $updates['headline_variations'] = $headlines;
            $updates['optimized_headline'] = $headlines['authority'] ?? ($headlines['keyword'] ?? null);
        }
        if ($about) {
            $updates['optimized_about'] = $about;
        }
        if ($experience) {
            $updates['optimized_experience'] = $experience;
        }

        if (! empty($updates)) {
            $profile->update($updates);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'headlines' => $headlines,
                'about' => $about,
                'experience' => $experience,
                'profile' => $profile->fresh(),
            ]);
        }

        return redirect()->route('linkedin.optimizer', ['role_profile_id' => $roleProfile?->id])
            ->with('status', 'LinkedIn profile copy generated successfully!');
    }

    /**
     * Transform raw experience bullet points to Google XYZ framework.
     */
    public function transformBullets(Request $request): JsonResponse
    {
        $user = Auth::user();
        $raw = $request->input('raw_experience', '');

        $optimized = $this->optimizerService->optimizeExperienceBullets($user, $raw);

        return response()->json([
            'success' => true,
            'optimized' => $optimized,
        ]);
    }

    /**
     * Save candidate's edited LinkedIn profile.
     */
    public function save(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $roleProfileId = $request->input('role_profile_id') ? (int) $request->input('role_profile_id') : null;

        $roleProfile = $roleProfileId
            ? RoleProfile::where('user_id', $user->id)->find($roleProfileId)
            : null;

        // Run audit to save accurate recruiter score and keywords
        $audit = $this->optimizerService->auditProfile($user, $roleProfile, [
            'headline' => $request->input('optimized_headline') ?: $request->input('current_headline'),
            'about' => $request->input('optimized_about') ?: $request->input('current_about'),
            'experience' => $request->input('optimized_experience') ?: $request->input('current_experience'),
        ]);

        $data = [
            'current_headline' => $request->input('current_headline'),
            'current_about' => $request->input('current_about'),
            'current_experience' => $request->input('current_experience'),
            'optimized_headline' => $request->input('optimized_headline'),
            'optimized_about' => $request->input('optimized_about'),
            'optimized_experience' => $request->input('optimized_experience'),
            'optimization_notes' => $request->input('optimization_notes'),
            'recruiter_score' => $audit['recruiter_score'],
            'missing_keywords' => $audit['missing_keywords'],
        ];

        if ($request->filled('headline_variations') && is_array($request->input('headline_variations'))) {
            $data['headline_variations'] = $request->input('headline_variations');
        }

        $profile = $this->optimizerService->saveProfile($user, $data, $roleProfileId);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'LinkedIn profile saved successfully.',
                'profile' => $profile,
                'audit' => $audit,
            ]);
        }

        return redirect()->route('linkedin.optimizer', ['role_profile_id' => $roleProfileId])
            ->with('status', 'LinkedIn profile optimized and saved successfully.');
    }
}

