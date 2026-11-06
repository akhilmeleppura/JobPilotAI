<?php

namespace App\Modules\Resume\app\Services;

use App\Models\User;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Models\ResumeVersion;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Support\Str;

class CVAssignmentResolverService
{
    public const MODE_GLOBAL_DEFAULT = 'Mode 1: Global Default';
    public const MODE_ROLE_LINKED = 'Mode 2: Role Profile Linked';
    public const MODE_APP_OVERRIDE = 'Mode 3: Per-Application Override';
    public const MODE_AI_RECOMMENDED = 'Mode 4: AI-Recommended Match';

    /**
     * Resolve the optimal CV for a job application or vacancy opportunity using Modes 1–4.
     */
    public function resolveCV(
        User $user,
        ?Application $application = null,
        ?int $roleProfileId = null,
        ?int $overrideResumeId = null,
        ?array $vacancyContext = null
    ): array {
        // Mode 3: Per-Application Explicit Override
        $explicitResumeId = $overrideResumeId ?? ($application?->resume_id);
        if ($explicitResumeId) {
            $resume = Resume::with('currentVersion')
                ->where('user_id', $user->id)
                ->find($explicitResumeId);

            if ($resume) {
                return [
                    'resume' => $resume,
                    'version' => $resume->currentVersion,
                    'mode' => self::MODE_APP_OVERRIDE,
                    'match_score' => 100,
                    'reasoning' => "Explicitly selected resume '{$resume->title}' for this specific vacancy.",
                ];
            }
        }

        // Mode 2: Per-Role Profile Linked CV
        $effectiveRoleId = $roleProfileId ?? ($application?->role_profile_id);
        if ($effectiveRoleId) {
            $roleResume = Resume::with('currentVersion')
                ->where('user_id', $user->id)
                ->where('role_profile_id', $effectiveRoleId)
                ->first();

            if ($roleResume) {
                $role = RoleProfile::find($effectiveRoleId);
                return [
                    'resume' => $roleResume,
                    'version' => $roleResume->currentVersion,
                    'mode' => self::MODE_ROLE_LINKED,
                    'match_score' => 95,
                    'reasoning' => "Automatically matched to target persona '{$role?->name}' linked to this opportunity.",
                ];
            }
        }

        // Mode 4: AI / Semantic Skill Match Recommendation if vacancy context provided
        if (!empty($vacancyContext)) {
            $recommendation = $this->evaluateBestMatch($user, $vacancyContext);
            if ($recommendation && $recommendation['match_score'] >= 75) {
                return [
                    'resume' => $recommendation['resume'],
                    'version' => $recommendation['resume']->currentVersion,
                    'mode' => self::MODE_AI_RECOMMENDED,
                    'match_score' => $recommendation['match_score'],
                    'reasoning' => $recommendation['reasoning'],
                ];
            }
        }

        // Mode 1: Global Default CV
        $globalDefault = Resume::with('currentVersion')
            ->where('user_id', $user->id)
            ->where('is_global_default', true)
            ->first();

        if (!$globalDefault) {
            $globalDefault = Resume::with('currentVersion')
                ->where('user_id', $user->id)
                ->first();
        }

        return [
            'resume' => $globalDefault,
            'version' => $globalDefault?->currentVersion,
            'mode' => self::MODE_GLOBAL_DEFAULT,
            'match_score' => 85,
            'reasoning' => "Default career master resume utilized across all standard outreach.",
        ];
    }

    /**
     * Compute keyword overlap between vacancy requirements and all available resumes.
     */
    public function evaluateBestMatch(User $user, array $vacancyContext): ?array
    {
        $resumes = Resume::with('currentVersion')->where('user_id', $user->id)->get();
        if ($resumes->isEmpty()) {
            return null;
        }

        $title = strtolower($vacancyContext['job_title'] ?? '');
        $description = strtolower($vacancyContext['description'] ?? '');
        $requiredSkills = array_map('strtolower', $vacancyContext['skills'] ?? []);

        $bestResume = null;
        $highestScore = 0;
        $bestReason = '';

        foreach ($resumes as $res) {
            $score = 50; // Base score
            $primarySkills = array_map('strtolower', $res->primary_skills ?? []);
            $matchedSkills = [];

            // Match required skills
            foreach ($requiredSkills as $req) {
                if (in_array($req, $primarySkills) || str_contains($description, $req)) {
                    $score += 8;
                    $matchedSkills[] = $req;
                }
            }

            // Match title keywords
            foreach ($primarySkills as $skill) {
                if (str_contains($title, $skill) || str_contains($description, $skill)) {
                    $score += 5;
                    $matchedSkills[] = $skill;
                }
            }

            $score = min(98, $score);

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestResume = $res;
                $uniqueMatched = array_unique($matchedSkills);
                $bestReason = "AI Keyword Engine matched " . count($uniqueMatched) . " target skills (" . implode(', ', array_slice($uniqueMatched, 0, 5)) . ") against '{$res->title}'.";
            }
        }

        if (!$bestResume) {
            return null;
        }

        return [
            'resume' => $bestResume,
            'match_score' => $highestScore,
            'reasoning' => $bestReason,
        ];
    }

    /**
     * Set a specific resume as the Global Default CV.
     */
    public function setGlobalDefault(User $user, int $resumeId): bool
    {
        Resume::where('user_id', $user->id)->update(['is_global_default' => false]);
        return (bool) Resume::where('user_id', $user->id)
            ->where('id', $resumeId)
            ->update(['is_global_default' => true]);
    }

    /**
     * Assign a CV to a specific Role Profile.
     */
    public function mapResumeToRole(User $user, int $resumeId, ?int $roleProfileId): bool
    {
        return (bool) Resume::where('user_id', $user->id)
            ->where('id', $resumeId)
            ->update(['role_profile_id' => $roleProfileId]);
    }
}

