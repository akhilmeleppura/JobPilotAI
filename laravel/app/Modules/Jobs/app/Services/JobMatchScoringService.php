<?php

namespace App\Modules\Jobs\app\Services;

use App\Modules\Jobs\app\Models\JobVacancy;
use App\Modules\RoleProfiles\app\Models\RoleProfile;

class JobMatchScoringService
{
    /**
     * Compute multi-factor match score between a vacancy and a candidate's profile/query.
     *
     * @return array{
     *     score: int,
     *     grade: string,
     *     matched_skills: array<string>,
     *     missing_skills: array<string>,
     *     pros: array<string>,
     *     cons: array<string>,
     *     recommendation: string
     * }
     */
    public function evaluateMatch(
        JobVacancy $vacancy,
        ?RoleProfile $profile = null,
        array $queryContext = []
    ): array {
        $score = 40; // Base score
        $pros = [];
        $cons = [];
        $matchedSkills = [];
        $missingSkills = [];

        $profileSkills = $profile ? array_map('strtolower', $profile->technical_skills ?? []) : ['php', 'laravel', 'mysql', 'redis'];
        $secondarySkills = $profile ? array_map('strtolower', $profile->secondary_skills ?? []) : ['vue.js', 'docker', 'git'];
        $candidateTitles = $profile ? array_map('strtolower', $profile->target_titles ?? []) : ['senior laravel developer', 'backend engineer'];

        $vacancyTitle = strtolower($vacancy->job_title);
        $vacancyDesc = strtolower($vacancy->description ?? '');
        $vacancySkills = array_map('strtolower', $vacancy->skills ?? []);

        // 1. Technical Skills Overlap (Max 35 points)
        $skillScore = 0;
        foreach ($profileSkills as $skill) {
            if (in_array($skill, $vacancySkills) || str_contains($vacancyDesc, $skill) || str_contains($vacancyTitle, $skill)) {
                $skillScore += 5;
                $matchedSkills[] = ucfirst($skill);
            }
        }
        foreach ($secondarySkills as $sec) {
            if (in_array($sec, $vacancySkills) || str_contains($vacancyDesc, $sec)) {
                $skillScore += 2;
                $matchedSkills[] = ucfirst($sec);
            }
        }
        $skillScore = min(35, $skillScore);
        $score += $skillScore;

        if (count($matchedSkills) >= 3) {
            $pros[] = "Overlapping core technical competencies: " . implode(', ', array_slice(array_unique($matchedSkills), 0, 5)) . ".";
        } else {
            $cons[] = "Limited skill tag overlap with target profile.";
        }

        // 2. Title & Seniority Match (Max 20 points)
        $titleMatched = false;
        foreach ($candidateTitles as $t) {
            if (str_contains($vacancyTitle, $t) || str_contains($t, $vacancyTitle)) {
                $titleMatched = true;
                break;
            }
        }

        if ($titleMatched) {
            $score += 18;
            $pros[] = "Job title strongly aligns with target career persona '{$vacancy->job_title}'.";
        } elseif (str_contains($vacancyTitle, 'senior') || str_contains($vacancyTitle, 'lead') || str_contains($vacancyTitle, 'backend')) {
            $score += 10;
            $pros[] = "Seniority and engineering domain match candidate scope.";
        } else {
            $score += 4;
            $cons[] = "Role title differs slightly from default target designations.";
        }

        // 3. Work Mode & Location Preference (Max 15 points)
        if ($vacancy->work_mode === 'Remote') {
            $score += 15;
            $pros[] = "100% Remote flexibility matching worldwide workflow.";
        } elseif ($vacancy->work_mode === 'Hybrid') {
            $score += 8;
        } else {
            $score += 2;
            $cons[] = "Onsite requirement may restrict remote location preference.";
        }

        // 4. Compensation Match (Max 10 points)
        $minRequired = $queryContext['salary_min'] ?? null;
        if ($minRequired && $vacancy->salary_min) {
            if ($vacancy->salary_min >= $minRequired || ($vacancy->salary_max && $vacancy->salary_max >= $minRequired)) {
                $score += 10;
                $pros[] = "Meets or exceeds requested compensation threshold ({$vacancy->currency} " . number_format($vacancy->salary_min) . "+).";
            } else {
                $cons[] = "Listed compensation is below the requested threshold of {$vacancy->currency} " . number_format($minRequired) . ".";
            }
        } elseif ($vacancy->salary_min) {
            $score += 8;
            $pros[] = "Transparent salary range disclosed: {$vacancy->currency} " . number_format($vacancy->salary_min) . " - " . number_format($vacancy->salary_max ?? $vacancy->salary_min) . ".";
        }

        // 5. Natural Language Prompt Specifics (Max 10 points)
        $queryKeywords = $queryContext['keywords'] ?? [];
        $matchedQueryKeywords = 0;
        foreach ($queryKeywords as $kw) {
            $lkw = strtolower($kw);
            if (str_contains($vacancyTitle, $lkw) || str_contains($vacancyDesc, $lkw) || in_array($lkw, $vacancySkills)) {
                $matchedQueryKeywords++;
            }
        }
        if ($matchedQueryKeywords > 0) {
            $score += min(10, $matchedQueryKeywords * 4);
            $pros[] = "Specifically matches {$matchedQueryKeywords} prompt keyword criteria.";
        }

        // Detect missing key technologies
        foreach ($vacancySkills as $req) {
            if (!in_array($req, $profileSkills) && !in_array($req, $secondarySkills)) {
                $missingSkills[] = ucfirst($req);
            }
        }

        $clampedScore = max(40, min(99, $score));

        $grade = match (true) {
            $clampedScore >= 80 => 'Strong Match',
            $clampedScore >= 65 => 'Good Match',
            $clampedScore >= 50 => 'Moderate Match',
            default => 'Low Match',
        };

        $recommendation = match ($grade) {
            'Strong Match' => 'High-priority opportunity. Stage immediately and dispatch personalized outreach.',
            'Good Match' => 'Solid career alignment. Tailor CV keywords toward stated stack and apply.',
            default => 'Review vacancy specifications before staging.',
        };

        return [
            'score' => $clampedScore,
            'grade' => $grade,
            'matched_skills' => array_values(array_unique($matchedSkills)),
            'missing_skills' => array_values(array_slice(array_unique($missingSkills), 0, 5)),
            'pros' => $pros,
            'cons' => $cons,
            'recommendation' => $recommendation,
        ];
    }
}

