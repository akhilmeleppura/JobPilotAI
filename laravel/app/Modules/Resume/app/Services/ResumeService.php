<?php

namespace App\Modules\Resume\app\Services;

use App\Models\User;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\Resume\app\Models\ResumeVersion;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResumeService
{
    public function __construct(
        protected ?ResumeTemplateService $templateService = null
    ) {
        $this->templateService = $templateService ?? app(ResumeTemplateService::class);
    }
    /**
     * Get all resumes for user, auto-seeding if none exist.
     */
    public function getOrSeedResumesForUser(User $user): Collection
    {
        if (Resume::where('user_id', $user->id)->count() === 0) {
            $this->seedStandardResumes($user);
        }

        return Resume::with(['roleProfile', 'currentVersion', 'versions'])
            ->where('user_id', $user->id)
            ->orderByDesc('is_global_default')
            ->orderBy('title')
            ->get();
    }

    /**
     * Populate standard resumes linked to role profiles.
     */
    public function seedStandardResumes(User $user): Collection
    {
        $roleProfiles = RoleProfile::where('user_id', $user->id)->get();

        $laravelRole = $roleProfiles->firstWhere('slug', 'laravel-php-backend');
        $wpRole = $roleProfiles->firstWhere('slug', 'wordpress-woocommerce-specialist');
        $fullStackRole = $roleProfiles->firstWhere('slug', 'full-stack-web-engineer');
        $generalRole = $roleProfiles->firstWhere('slug', 'general-web-developer');
        $contractRole = $roleProfiles->firstWhere('slug', 'freelance-contract-consultant');

        $standards = [
            [
                'title' => 'Senior Laravel & PHP Backend Specialist CV',
                'slug' => 'senior-laravel-php-backend-cv',
                'role_profile_id' => $laravelRole?->id,
                'is_global_default' => true,
                'target_role_name' => 'Senior Laravel & PHP Backend Specialist',
                'primary_skills' => ['PHP 8.4', 'Laravel 13', 'MySQL / TiDB Cloud', 'Redis', 'REST APIs', 'GraphQL', 'Docker', 'PHPUnit'],
                'template_name' => 'backend_developer',
                'ats_score' => 96,
            ],
            [
                'title' => 'WordPress & WooCommerce Architect CV',
                'slug' => 'wordpress-woocommerce-architect-cv',
                'role_profile_id' => $wpRole?->id,
                'is_global_default' => false,
                'target_role_name' => 'WordPress & WooCommerce Architect',
                'primary_skills' => ['WordPress Core', 'WooCommerce', 'Custom Plugins', 'Hooks & Filters', 'PHP 8.x', 'MySQL', 'Stripe'],
                'template_name' => 'wordpress_specialist',
                'ats_score' => 93,
            ],
            [
                'title' => 'Full Stack Web Engineer CV',
                'slug' => 'full-stack-web-engineer-cv',
                'role_profile_id' => $fullStackRole?->id,
                'is_global_default' => false,
                'target_role_name' => 'Full Stack Web Engineer',
                'primary_skills' => ['PHP / Laravel', 'JavaScript / TypeScript', 'Vue.js / React', 'Tailwind CSS', 'Vite', 'Git'],
                'template_name' => 'full_stack_developer',
                'ats_score' => 91,
            ],
            [
                'title' => 'General Web Solutions Developer CV',
                'slug' => 'general-web-solutions-cv',
                'role_profile_id' => $generalRole?->id,
                'is_global_default' => false,
                'target_role_name' => 'General Web Developer',
                'primary_skills' => ['PHP', 'JavaScript', 'HTML5', 'CSS3', 'MySQL', 'Linux', 'Apache/Nginx'],
                'template_name' => 'ats_classic',
                'ats_score' => 89,
            ],
            [
                'title' => 'Contract & Freelance MVP Consultant CV',
                'slug' => 'contract-freelance-mvp-cv',
                'role_profile_id' => $contractRole?->id,
                'is_global_default' => false,
                'target_role_name' => 'Freelance Technical Consultant',
                'primary_skills' => ['Rapid MVP Delivery', 'Custom CRM / SaaS', 'Payment Gateways', 'Legacy Code Modernization'],
                'template_name' => 'project_focused',
                'ats_score' => 92,
            ],
        ];

        $created = collect();

        foreach ($standards as $item) {
            $resume = Resume::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'slug' => $item['slug'],
                ],
                [
                    'title' => $item['title'],
                    'role_profile_id' => $item['role_profile_id'],
                    'is_global_default' => $item['is_global_default'],
                    'target_role_name' => $item['target_role_name'],
                    'primary_skills' => $item['primary_skills'],
                ]
            );

            $latex = $this->templateService->getTemplateSource($item['template_name'], $user);

            // Create initial Version 1
            $version = ResumeVersion::firstOrCreate(
                [
                    'resume_id' => $resume->id,
                    'version_number' => 1,
                ],
                [
                    'template_name' => $item['template_name'],
                    'source_type' => 'LaTeX',
                    'ats_score' => $item['ats_score'],
                    'latex_source' => $latex,
                    'optimization_notes' => 'Baseline ATS-optimized version generated.',
                ]
            );

            $resume->update(['current_version_id' => $version->id]);
            $created->push($resume);
        }

        return $created;
    }
}
