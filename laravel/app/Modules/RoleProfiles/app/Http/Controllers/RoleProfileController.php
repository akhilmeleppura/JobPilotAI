<?php

namespace App\Modules\RoleProfiles\app\Http\Controllers;

use App\Modules\RoleProfiles\app\Models\RoleProfile;
use App\Modules\RoleProfiles\app\Services\RoleProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleProfileController extends Controller
{
    public function __construct(
        protected RoleProfileService $profileService
    ) {}

    /**
     * Display all role profiles for the authenticated user.
     */
    public function index(): View
    {
        $user = Auth::user();

        // Automatically seed default 5 roles if candidate has none yet
        if (RoleProfile::where('user_id', $user->id)->count() === 0) {
            $this->profileService->seedDefaultProfilesForUser($user);
        }

        $profiles = $this->profileService->getProfilesForUser($user->id);

        return view('roleprofiles::index', compact('profiles'));
    }

    /**
     * Show form for creating a new profile.
     */
    public function create(): View
    {
        return view('roleprofiles::create');
    }

    /**
     * Store newly created role profile.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_titles' => ['nullable', 'string'],
            'technical_skills' => ['nullable', 'string'],
            'secondary_skills' => ['nullable', 'string'],
            'experience_years' => ['required', 'numeric', 'min:0', 'max:50'],
            'summary' => ['nullable', 'string'],
            'work_modes' => ['nullable', 'array'],
            'location_preferences' => ['nullable', 'string'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'search_keywords' => ['nullable', 'string'],
            'email_signature' => ['nullable', 'string'],
        ]);

        $slug = Str::slug($validated['name']);
        $uniqueSlug = $slug;
        $counter = 1;
        while (RoleProfile::where('user_id', $user->id)->where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = "{$slug}-{$counter}";
            $counter++;
        }

        RoleProfile::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'slug' => $uniqueSlug,
            'target_titles' => $this->parseCommaList($validated['target_titles'] ?? ''),
            'technical_skills' => $this->parseCommaList($validated['technical_skills'] ?? ''),
            'secondary_skills' => $this->parseCommaList($validated['secondary_skills'] ?? ''),
            'experience_years' => $validated['experience_years'],
            'summary' => $validated['summary'] ?? null,
            'work_modes' => $validated['work_modes'] ?? ['Remote'],
            'location_preferences' => $this->parseCommaList($validated['location_preferences'] ?? ''),
            'salary_min' => $validated['salary_min'] ? (int) $validated['salary_min'] : null,
            'salary_max' => $validated['salary_max'] ? (int) $validated['salary_max'] : null,
            'currency' => strtoupper($validated['currency']),
            'search_keywords' => $this->parseCommaList($validated['search_keywords'] ?? ''),
            'email_signature' => $validated['email_signature'] ?? null,
            'is_active' => true,
            'is_default' => false,
        ]);

        return redirect()->route('roleprofiles.index')->with('success', 'Role profile created successfully!');
    }

    /**
     * Show form for editing an existing profile.
     */
    public function edit(int $id): View
    {
        $profile = RoleProfile::where('user_id', Auth::id())->findOrFail($id);
        return view('roleprofiles::edit', compact('profile'));
    }

    /**
     * Update existing role profile.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $profile = RoleProfile::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_titles' => ['nullable', 'string'],
            'technical_skills' => ['nullable', 'string'],
            'secondary_skills' => ['nullable', 'string'],
            'experience_years' => ['required', 'numeric', 'min:0', 'max:50'],
            'summary' => ['nullable', 'string'],
            'work_modes' => ['nullable', 'array'],
            'location_preferences' => ['nullable', 'string'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'search_keywords' => ['nullable', 'string'],
            'email_signature' => ['nullable', 'string'],
        ]);

        $profile->update([
            'name' => $validated['name'],
            'target_titles' => $this->parseCommaList($validated['target_titles'] ?? ''),
            'technical_skills' => $this->parseCommaList($validated['technical_skills'] ?? ''),
            'secondary_skills' => $this->parseCommaList($validated['secondary_skills'] ?? ''),
            'experience_years' => $validated['experience_years'],
            'summary' => $validated['summary'] ?? null,
            'work_modes' => $validated['work_modes'] ?? ['Remote'],
            'location_preferences' => $this->parseCommaList($validated['location_preferences'] ?? ''),
            'salary_min' => $validated['salary_min'] ? (int) $validated['salary_min'] : null,
            'salary_max' => $validated['salary_max'] ? (int) $validated['salary_max'] : null,
            'currency' => strtoupper($validated['currency']),
            'search_keywords' => $this->parseCommaList($validated['search_keywords'] ?? ''),
            'email_signature' => $validated['email_signature'] ?? null,
        ]);

        return redirect()->route('roleprofiles.index')->with('success', "Profile '{$profile->name}' updated successfully!");
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(int $id): RedirectResponse
    {
        $profile = RoleProfile::where('user_id', Auth::id())->findOrFail($id);
        $profile->update(['is_active' => !$profile->is_active]);

        $status = $profile->is_active ? 'activated' : 'deactivated';
        return redirect()->route('roleprofiles.index')->with('success', "Profile '{$profile->name}' has been {$status}.");
    }

    /**
     * Set profile as primary default.
     */
    public function setDefault(int $id): RedirectResponse
    {
        $user = Auth::user();
        $this->profileService->setDefaultProfile($user, $id);

        return redirect()->route('roleprofiles.index')->with('success', 'Default career profile updated!');
    }

    /**
     * Delete profile.
     */
    public function destroy(int $id): RedirectResponse
    {
        $profile = RoleProfile::where('user_id', Auth::id())->findOrFail($id);
        if ($profile->is_default) {
            return back()->withErrors(['error' => 'Cannot delete the primary default profile. Select another default first.']);
        }

        $profile->delete();
        return redirect()->route('roleprofiles.index')->with('success', 'Role profile deleted.');
    }

    /**
     * Helper to parse comma/newline delimited text into array.
     */
    protected function parseCommaList(?string $input): array
    {
        if (empty($input)) {
            return [];
        }

        $items = preg_split('/[\r\n,]+/', $input);
        return array_values(array_filter(array_map('trim', $items)));
    }
}

