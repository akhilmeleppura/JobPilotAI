<?php

namespace App\Modules\RoleProfiles\app\Services;

use App\Models\User;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleProfileService
{
    /**
     * Get all role profiles for a user.
     */
    public function getProfilesForUser(int $userId): Collection
    {
        return RoleProfile::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Seed or get the standard default 5 role profiles for a candidate.
     */
    public function seedDefaultProfilesForUser(User $user): Collection
    {
        $defaults = [
            [
                'name' => 'Laravel & PHP Backend Specialist',
                'slug' => 'laravel-php-backend',
                'target_titles' => [
                    'Senior Laravel Developer',
                    'PHP Backend Engineer',
                    'Lead PHP Architect',
                    'API & Microservices Software Engineer',
                ],
                'technical_skills' => [
                    'PHP 8.4',
                    'Laravel 11/12/13',
                    'MySQL / TiDB Cloud',
                    'PostgreSQL',
                    'Redis & Caching',
                    'RESTful APIs',
                    'GraphQL',
                    'Docker',
                    'PHPUnit & TDD',
                    'Queues & Event Dispatching',
                ],
                'secondary_skills' => [
                    'Livewire',
                    'Alpine.js',
                    'Tailwind CSS',
                    'AWS S3 & Cloud Architecture',
                    'GitHub Actions CI/CD',
                ],
                'experience_years' => 5.0,
                'summary' => 'Dedicated Backend Engineer specializing in robust Laravel architectures, high-performance database design, asynchronous queuing systems, and RESTful/GraphQL microservice APIs.',
                'location_preferences' => ['Remote Worldwide', 'United States', 'Europe', 'India'],
                'work_modes' => ['Remote', 'Hybrid'],
                'salary_min' => 45000,
                'salary_max' => 95000,
                'currency' => 'USD',
                'search_keywords' => ['Laravel', 'PHP', 'Symfony', 'Backend Developer', 'API Engineer', 'Remote PHP'],
                'email_signature' => "Best regards,\n{$user->name}\nSenior Laravel & PHP Backend Specialist\nEmail: {$user->email}",
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'WordPress & WooCommerce Specialist',
                'slug' => 'wordpress-woocommerce-specialist',
                'target_titles' => [
                    'Senior WordPress Developer',
                    'WooCommerce Architect',
                    'Full Stack WordPress Engineer',
                    'Custom Theme & Plugin Developer',
                ],
                'technical_skills' => [
                    'WordPress Core API',
                    'WooCommerce',
                    'Custom Plugin Architecture',
                    'Hooks & Filters Engine',
                    'REST API WP',
                    'MySQL & Query Optimization',
                    'PHP 8.x',
                    'Advanced Custom Fields (ACF)',
                    'Gutenberg Blocks',
                ],
                'secondary_skills' => [
                    'React.js',
                    'Stripe & PayPal APIs',
                    'Core Web Vitals Optimization',
                    'WP-CLI',
                    'Elementor Custom Modules',
                ],
                'experience_years' => 5.0,
                'summary' => 'Expert WordPress & WooCommerce engineer crafting secure, scalable plugins, enterprise-grade custom themes, headless WP solutions, and seamless checkout optimization.',
                'location_preferences' => ['Remote Worldwide', 'United States', 'United Kingdom'],
                'work_modes' => ['Remote', 'Hybrid'],
                'salary_min' => 40000,
                'salary_max' => 85000,
                'currency' => 'USD',
                'search_keywords' => ['WordPress', 'WooCommerce', 'WP Developer', 'Custom Plugins', 'Headless WordPress'],
                'email_signature' => "Best regards,\n{$user->name}\nWordPress & WooCommerce Architect\nEmail: {$user->email}",
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Full Stack Web Engineer',
                'slug' => 'full-stack-web-engineer',
                'target_titles' => [
                    'Full Stack Web Developer',
                    'Full Stack Engineer (PHP/Vue/React)',
                    'Senior Web Application Engineer',
                ],
                'technical_skills' => [
                    'PHP / Laravel',
                    'JavaScript (ES6+) & TypeScript',
                    'Vue.js / React',
                    'Node.js',
                    'Bootstrap 5 & Tailwind CSS',
                    'Relational & NoSQL Databases',
                    'Vite & Build Tooling',
                    'Git / Version Control',
                ],
                'secondary_skills' => [
                    'Docker',
                    'Redis',
                    'WebSockets',
                    'Inertia.js',
                    'Cypress & Integration Testing',
                ],
                'experience_years' => 5.0,
                'summary' => 'Comprehensive Full Stack Engineer adept at building end-to-end web applications with modern frontend frameworks and rock-solid backend services.',
                'location_preferences' => ['Remote Worldwide', 'North America', 'Europe'],
                'work_modes' => ['Remote'],
                'salary_min' => 50000,
                'salary_max' => 100000,
                'currency' => 'USD',
                'search_keywords' => ['Full Stack Developer', 'Laravel Vue', 'React PHP', 'Web Application Engineer'],
                'email_signature' => "Best regards,\n{$user->name}\nFull Stack Web Engineer\nEmail: {$user->email}",
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'General Web Developer',
                'slug' => 'general-web-developer',
                'target_titles' => [
                    'Web Developer',
                    'Software Engineer',
                    'Backend/Frontend Web Developer',
                    'Web Solutions Engineer',
                ],
                'technical_skills' => [
                    'PHP',
                    'JavaScript',
                    'HTML5 & Semantic Markup',
                    'CSS3 & Modern Layouts',
                    'MySQL',
                    'REST APIs & Integrations',
                    'Git & GitHub',
                    'Linux / Apache / Nginx Deployment',
                ],
                'secondary_skills' => [
                    'jQuery',
                    'Bootstrap',
                    'SEO & Accessibility Audits',
                    'Performance Tuning',
                    'Security Hardening',
                ],
                'experience_years' => 4.0,
                'summary' => 'Versatile web developer experienced in cross-platform website development, systems integration, bug resolution, and digital maintenance.',
                'location_preferences' => ['Remote Worldwide', 'Any'],
                'work_modes' => ['Remote', 'Hybrid', 'Onsite'],
                'salary_min' => 35000,
                'salary_max' => 70000,
                'currency' => 'USD',
                'search_keywords' => ['Web Developer', 'Software Developer', 'PHP Web Developer', 'Web Solutions'],
                'email_signature' => "Best regards,\n{$user->name}\nWeb Developer\nEmail: {$user->email}",
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Freelance & Contract Consultant',
                'slug' => 'freelance-contract-consultant',
                'target_titles' => [
                    'Freelance Full Stack Developer',
                    'Contract PHP/Laravel Consultant',
                    'Technical Project Contractor',
                    'MVP & Fast-Track Developer',
                ],
                'technical_skills' => [
                    'Rapid MVP Prototyping',
                    'Custom CRM / SaaS Architecture',
                    'Payment Gateways (Stripe, PayPal, Razorpay)',
                    'WordPress & CMS Solutions',
                    'Database Migrations & Tuning',
                    'Legacy Code Modernization',
                ],
                'secondary_skills' => [
                    'Client Scoping & SOW Preparation',
                    'Agile Milestone Delivery',
                    'DevOps & Server Hardening',
                    'Code Audits',
                ],
                'experience_years' => 5.0,
                'summary' => 'Independent software consultant delivering high-impact technical solutions, rapid MVP delivery, technical debt remediation, and custom business automation.',
                'location_preferences' => ['Remote Worldwide'],
                'work_modes' => ['Remote'],
                'salary_min' => 35, // Hourly rate or base
                'salary_max' => 75,
                'currency' => 'USD',
                'search_keywords' => ['Contract Developer', 'Freelance Laravel', 'Freelance PHP', 'Technical Consultant'],
                'email_signature' => "Best regards,\n{$user->name}\nFreelance Software Consultant & Engineer\nEmail: {$user->email}",
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        $created = collect();

        foreach ($defaults as $profileData) {
            $created->push(RoleProfile::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'slug' => $profileData['slug'],
                ],
                $profileData
            ));
        }

        return $created;
    }

    /**
     * Set a profile as the default profile for a user.
     */
    public function setDefaultProfile(User $user, int $profileId): bool
    {
        RoleProfile::where('user_id', $user->id)->update(['is_default' => false]);
        return (bool) RoleProfile::where('user_id', $user->id)
            ->where('id', $profileId)
            ->update(['is_default' => true]);
    }
}

