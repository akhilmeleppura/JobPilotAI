<?php

namespace App\Modules\Resume\app\Services;

class ATSAnalysisService
{
    /**
     * Analyze LaTeX source code for ATS parser compatibility and keyword optimization.
     *
     * @return array{
     *     score: int,
     *     status: string,
     *     sections_found: array<string>,
     *     sections_missing: array<string>,
     *     strengths: array<string>,
     *     recommendations: array<string>,
     *     red_flags: array<string>
     * }
     */
    public function analyze(string $latex): array
    {
        $score = 80;
        $sectionsFound = [];
        $sectionsMissing = [];
        $strengths = [];
        $recommendations = [];
        $redFlags = [];

        // Check required sections
        $sectionChecks = [
            'Professional Summary' => ['/\\\\section\{.*(?:Summary|Profile|Objective).*?\}/i', 6],
            'Technical Skills' => ['/\\\\section\{.*(?:Skills|Competencies|Technologies).*?\}/i', 8],
            'Work Experience' => ['/\\\\section\{.*(?:Experience|Employment|History).*?\}/i', 8],
            'Education' => ['/\\\\section\{.*(?:Education|Academics).*?\}/i', 5],
            'Projects / Portfolio' => ['/\\\\section\{.*(?:Projects|Work).*?\}/i', 3],
        ];

        foreach ($sectionChecks as $sectionName => [$pattern, $points]) {
            if (preg_match($pattern, $latex)) {
                $sectionsFound[] = $sectionName;
                $score += $points;
            } else {
                $sectionsMissing[] = $sectionName;
                $score -= 6;
                $recommendations[] = "Add a clear '\\section{{$sectionName}}' to ensure standard ATS parsers categorize your background correctly.";
            }
        }

        // Contact information verification
        $hasEmail = preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $latex);
        $hasPhone = preg_match('/(?:\+\d{1,3}[- ]?)?\(?\d{3}\)?[- ]?\d{3}[- ]?\d{4}/', $latex);
        $hasLinks = str_contains(strtolower($latex), 'github') || str_contains(strtolower($latex), 'linkedin');

        if ($hasEmail && $hasPhone) {
            $strengths[] = "Complete direct contact details (valid email and phone number format) detected in the header.";
            $score += 4;
        } else {
            $recommendations[] = "Ensure both email address and standard phone number are clearly visible in the header.";
            $score -= 5;
        }

        if ($hasLinks) {
            $strengths[] = "Professional profile links (GitHub / LinkedIn) present for recruiter verification.";
        }

        // Check for formatting red flags
        if (preg_match('/\\\\begin\{tabular\}/i', $latex) || preg_match('/\\\\begin\{table\}/i', $latex)) {
            $redFlags[] = "Table environments (tabular/table) detected. Many ATS parsers jumble column text order in multi-column tables. Prefer standard bullet lists with '\\setlist[itemize]'.";
            $score -= 10;
        } else {
            $strengths[] = "No complex table columns detected; document uses ATS-friendly single-column layout.";
        }

        if (preg_match('/\\\\includegraphics/i', $latex)) {
            $redFlags[] = "Embedded raster image (\\includegraphics) detected. ATS engines ignore image text and photos may bias parsing.";
            $score -= 8;
        }

        // Check action verbs and bullet point structure
        if (preg_match_all('/\\\\item\s+(?:Architected|Spearheaded|Delivered|Engineered|Built|Optimized|Designed|Implemented|Maintained|Led)/i', $latex, $matches)) {
            $count = count($matches[0]);
            $strengths[] = "Strong active achievement verbs detected across {$count} bullet points.";
            $score += 3;
        } else {
            $recommendations[] = "Begin experience bullet points with strong active achievement verbs (e.g., 'Architected', 'Spearheaded', 'Engineered', 'Optimized').";
        }

        // Quantifiable metrics check (%, $, numbers)
        if (preg_match_all('/(?:\d+[\.,]?\d*\s*\%|\$\s*\d+|\b\d+\s*(?:requests|users|queries|jobs|ms)\b)/i', $latex, $metricMatches)) {
            $metricCount = count($metricMatches[0]);
            $strengths[] = "Measurable business outcomes and quantifiable metrics ({$metricCount} metrics) detected.";
            $score += 4;
        } else {
            $recommendations[] = "Include quantifiable metrics in work bullets (e.g. 'reduced latency by 42%', 'handled 15M+ requests').";
        }

        $clampedScore = max(40, min(99, $score));

        $status = match (true) {
            $clampedScore >= 88 => 'Excellent',
            $clampedScore >= 75 => 'Good',
            default => 'Needs Optimization',
        };

        return [
            'score' => $clampedScore,
            'status' => $status,
            'sections_found' => $sectionsFound,
            'sections_missing' => $sectionsMissing,
            'strengths' => $strengths,
            'recommendations' => $recommendations,
            'red_flags' => $redFlags,
        ];
    }
}

