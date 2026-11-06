<?php

namespace App\Modules\AI\app\Services;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Support\Facades\File;

class AIChatContextService
{
    /**
     * Build rich system instructions and context bag for the Career Copilot.
     */
    public function buildSystemContext(User $user, ?RoleProfile $activeProfile = null, array $attachments = []): string
    {
        $profile = $activeProfile ?? RoleProfile::where('user_id', $user->id)->where('is_default', true)->first();
        $resume = Resume::where('user_id', $user->id)->where('is_global_default', true)->first();

        $techSkills = !empty($profile?->technical_skills) ? implode(', ', $profile->technical_skills) : 'PHP 8.4, Laravel 13, TiDB Cloud, Redis';
        $targetTitles = !empty($profile?->target_titles) ? implode(', ', $profile->target_titles) : 'Senior Backend Engineer, Lead Architect';
        $experienceYears = $profile?->experience_years ?? 6.0;

        // Pipeline stats
        $appsCount = Application::where('user_id', $user->id)->count();
        $interviewsCount = Application::where('user_id', $user->id)->where('application_status', 'Interview')->count();
        $offersCount = Application::where('user_id', $user->id)->where('application_status', 'Offer')->count();

        // Recent applications
        $recentApps = Application::with('registry')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($a) => "- {$a->registry?->company_name_normalized}: {$a->registry?->job_title_normalized} (Status: {$a->application_status})")
            ->implode("\n");

        // Document attachment text
        $attachmentText = '';
        foreach ($attachments as $att) {
            $path = $att['path'] ?? null;
            if ($path && File::exists($path)) {
                $content = File::get($path);
                $attachmentText .= "\n\n--- ATTACHED DOCUMENT: {$att['filename']} ---\n" . substr(strip_tags($content), 0, 4000) . "\n--- END ATTACHMENT ---\n";
            }
        }

        return <<<CONTEXT
You are JobPilot AI Career Copilot, an elite executive career strategist, technical recruiter, and senior engineering mentor.
You work exclusively for the candidate with strict fiduciary commitment to maximizing their compensation, remote autonomy, career trajectory, and interview success.

CANDIDATE CAREER PROFILE:
- Candidate Name: {$user->name}
- Active Persona: {$profile?->name}
- Years of Experience: {$experienceYears} Years
- Target Roles: {$targetTitles}
- Core Technical Stack: {$techSkills}
- Default ATS Resume: {$resume?->title}

APPLICATION PIPELINE SUMMARY:
- Total Historical Applications: {$appsCount}
- Active Interview Rounds: {$interviewsCount}
- Active Offers: {$offersCount}
Recent Pipeline Activity:
{$recentApps}
{$attachmentText}

GUIDELINES & BEHAVIOR:
1. Provide actionable, high-conviction advice on technical screening questions, system design architecture, salary negotiations, and CV tailoring.
2. When analyzing job specs or offer letters, benchmark compensation aggressively and pinpoint leverage points.
3. Keep technical advice grounded in modern PHP 8.4, Laravel, cloud architecture, and distributed systems.
4. Format responses cleanly with markdown headings, concise bullet points, and code blocks where applicable.
CONTEXT;
    }
}

