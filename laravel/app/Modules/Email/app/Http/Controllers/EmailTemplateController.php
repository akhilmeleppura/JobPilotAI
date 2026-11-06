<?php

namespace App\Modules\Email\app\Http\Controllers;

use App\Modules\Email\app\Models\EmailTemplate;
use App\Modules\Email\app\Services\EmailTemplateService;
use App\Modules\Email\app\Services\TemplateInterpolationService;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class EmailTemplateController extends Controller
{
    public function __construct(
        protected EmailTemplateService $templateService,
        protected TemplateInterpolationService $interpolationService
    ) {}

    /**
     * Display all templates grouped by category.
     */
    public function index(): View
    {
        $user = Auth::user();
        $templates = $this->templateService->getOrSeedTemplatesForUser($user);
        $grouped = $templates->groupBy('category');

        $categories = [
            'InitialOutreach' => 'Initial Cold Application Outreach',
            'FollowUp' => 'Post-Application Follow-Up (5-Day Check-in)',
            'RecruiterInquiry' => 'Recruiter & Talent Pool Inquiry',
            'InterviewConfirmation' => 'Interview Confirmation & Logistics',
            'PostInterviewThankYou' => 'Post-Interview Thank You',
            'OfferNegotiation' => 'Offer & Compensation Negotiation',
            'ContractProposal' => 'Freelance & Contract Proposal',
            'ReferralRequest' => 'Internal Referral & Networking',
        ];

        return view('email::templates.index', compact('grouped', 'categories'));
    }

    /**
     * Show form for creating a new custom template.
     */
    public function create(): View
    {
        return view('email::templates.create');
    }

    /**
     * Store new template in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'subject_template' => ['required', 'string', 'max:255'],
            'body_template' => ['required', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        $uniqueSlug = $slug;
        $counter = 1;
        while (EmailTemplate::where('user_id', $user->id)->where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = "{$slug}-{$counter}";
            $counter++;
        }

        EmailTemplate::create([
            'user_id' => $user->id,
            'category' => $validated['category'],
            'name' => $validated['name'],
            'slug' => $uniqueSlug,
            'subject_template' => $validated['subject_template'],
            'body_template' => $validated['body_template'],
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()->route('email.templates.index')->with('success', 'Email template created successfully!');
    }

    /**
     * Show form for editing an existing template.
     */
    public function edit(int $id): View
    {
        $template = EmailTemplate::where('user_id', Auth::id())->findOrFail($id);
        return view('email::templates.edit', compact('template'));
    }

    /**
     * Update existing template.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $template = EmailTemplate::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'subject_template' => ['required', 'string', 'max:255'],
            'body_template' => ['required', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'category' => $validated['category'],
            'name' => $validated['name'],
            'subject_template' => $validated['subject_template'],
            'body_template' => $validated['body_template'],
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()->route('email.templates.index')->with('success', "Template '{$template->name}' updated!");
    }

    /**
     * Delete template.
     */
    public function destroy(int $id): RedirectResponse
    {
        $template = EmailTemplate::where('user_id', Auth::id())->findOrFail($id);
        $template->delete();

        return redirect()->route('email.templates.index')->with('success', 'Email template deleted.');
    }

    /**
     * Live preview with sample variables.
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $template = EmailTemplate::where('user_id', $user->id)->findOrFail($id);

        $role = RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();
        $context = $this->templateService->buildContext($user, $role, [
            'company_name' => $request->query('company', 'Acme Innovations'),
            'job_title' => $request->query('title', 'Senior Backend Engineer'),
        ]);

        $rendered = $this->interpolationService->renderTemplate($template, $context);
        return response()->json($rendered);
    }

    /**
     * Tailor template with mini AI assistant.
     */
    public function tailorWithAI(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $template = EmailTemplate::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'company_name' => ['required', 'string'],
            'job_title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
        ]);

        try {
            $tailoredBody = $this->templateService->tailorEmailWithAI(
                $user,
                $template,
                $validated,
                $validated['instructions'] ?? null
            );

            return response()->json([
                'success' => true,
                'tailored_body' => $tailoredBody,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}

