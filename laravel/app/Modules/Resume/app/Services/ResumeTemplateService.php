<?php

namespace App\Modules\Resume\app\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ResumeTemplateService
{
    /**
     * Get list of all available ATS LaTeX templates.
     */
    public function getAvailableTemplates(): Collection
    {
        return collect([
            [
                'key' => 'backend_developer',
                'name' => 'Modern Clean Backend Engineer',
                'category' => 'Backend & Systems',
                'recommended_role' => 'Senior Laravel & PHP Backend Specialist',
                'ats_score' => 96,
                'description' => 'Single-column high-density layout optimized for backend developers, systems architects, and API engineers.',
                'filename' => 'backend_developer.tex',
            ],
            [
                'key' => 'wordpress_specialist',
                'name' => 'WordPress & E-Commerce Architect',
                'category' => 'CMS & E-Commerce',
                'recommended_role' => 'WordPress & WooCommerce Specialist',
                'ats_score' => 93,
                'description' => 'Highlights bespoke plugin engineering, WooCommerce scaling metrics, and high-converting store outcomes.',
                'filename' => 'wordpress_specialist.tex',
            ],
            [
                'key' => 'full_stack_developer',
                'name' => 'Full Stack Web Engineer',
                'category' => 'Full Stack & Web',
                'recommended_role' => 'Full Stack Web Engineer',
                'ats_score' => 91,
                'description' => 'Bridges backend Laravel architectures with Vue/React frontend competencies and cloud deployment experience.',
                'filename' => 'full_stack_developer.tex',
            ],
            [
                'key' => 'ats_classic',
                'name' => 'Classic Single-Column ATS Standard',
                'category' => 'General / Universal',
                'recommended_role' => 'General Web Developer',
                'ats_score' => 98,
                'description' => 'Monochrome, zero-column-clutter layout guaranteeing 100% parser compatibility across Workday, Taleo, and iCIMS.',
                'filename' => 'ats_classic.tex',
            ],
            [
                'key' => 'executive_lead',
                'name' => 'Executive Lead & Solutions Architect',
                'category' => 'Leadership & Architecture',
                'recommended_role' => 'Lead PHP Architect / Engineering Manager',
                'ats_score' => 94,
                'description' => 'Emphasizes technical team leadership, architecture modernization, KPI delivery, and executive governance.',
                'filename' => 'executive_lead.tex',
            ],
            [
                'key' => 'project_focused',
                'name' => 'Contract & Freelance MVP Consultant',
                'category' => 'Freelance / Consulting',
                'recommended_role' => 'Freelance Technical Consultant',
                'ats_score' => 92,
                'description' => 'Case-study structured layout spotlighting rapid 0-to-1 MVP launches, client testimonials, and business impact.',
                'filename' => 'project_focused.tex',
            ],
        ]);
    }

    /**
     * Load raw or candidate-interpolated LaTeX template.
     */
    public function getTemplateSource(string $templateKey, ?User $user = null): string
    {
        $template = $this->getAvailableTemplates()->firstWhere('key', $templateKey);
        $filename = $template ? $template['filename'] : 'ats_classic.tex';
        $path = __DIR__ . '/../../resources/templates/' . $filename;

        if (!File::exists($path)) {
            $path = __DIR__ . '/../../resources/templates/ats_classic.tex';
        }

        $source = File::get($path);

        if ($user) {
            $source = str_replace(
                ['{{CANDIDATE_NAME}}', '{{EMAIL}}', '{{PHONE}}', '{{LOCATION}}'],
                [
                    $user->name ?? 'Candidate Name',
                    $user->email ?? 'candidate@example.com',
                    '+1 (555) 234-5678',
                    'Remote / Worldwide',
                ],
                $source
            );
        }

        return $source;
    }
}

