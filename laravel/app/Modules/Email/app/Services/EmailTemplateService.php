<?php

namespace App\Modules\Email\app\Services;

use App\Models\User;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\AI\app\Services\AIGatewayService;
use App\Modules\Candidate\app\Models\CandidateProfile;
use App\Modules\Email\app\Models\EmailTemplate;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmailTemplateService
{
    public function __construct(
        protected TemplateInterpolationService $interpolationService,
        protected AIGatewayService $aiGateway
    ) {}

    /**
     * Get all templates for a candidate, auto-seeding standard 6 templates if empty.
     */
    public function getOrSeedTemplatesForUser(User $user): Collection
    {
        if (EmailTemplate::where('user_id', $user->id)->count() === 0) {
            $this->seedStandardTemplates($user);
        }

        return EmailTemplate::where('user_id', $user->id)
            ->orderBy('category')
            ->orderByDesc('is_default')
            ->get();
    }

    /**
     * Populate standard 6 high-converting templates.
     */
    public function seedStandardTemplates(User $user): Collection
    {
        $standards = [
            [
                'category' => 'InitialOutreach',
                'name' => 'Direct Cold Application (Laravel & PHP Specialist)',
                'slug' => 'cold-app-laravel-backend',
                'subject_template' => 'Application: {{job_title}} — {{candidate_name}}',
                'body_template' => "<p>Dear {{company}} Hiring Team,</p><p>I am writing to express my enthusiastic interest in the <strong>{{job_title}}</strong> position at {{company}}.</p><p>With over {{experience_years}} years of hands-on experience architecting high-throughput backend systems in {{tech_stack}}, I have engineered resilient database architectures, microservice APIs, and automated queue systems.</p><p>Attached is my ATS-tailored resume for your review. You can also explore my open-source work and projects at {{github_url}}.</p><p>I would welcome the opportunity to discuss how my technical expertise can accelerate {{company}}'s engineering roadmap.</p><p>{{email_signature}}</p>",
                'is_default' => true,
            ],
            [
                'category' => 'InitialOutreach',
                'name' => 'Direct Cold Application (WordPress & WooCommerce Specialist)',
                'slug' => 'cold-app-wordpress-woocommerce',
                'subject_template' => '{{job_title}} Inquiry — {{candidate_name}} (WordPress & WooCommerce Specialist)',
                'body_template' => "<p>Dear {{company}} Team,</p><p>I am reaching out regarding the <strong>{{job_title}}</strong> opening at {{company}}.</p><p>As a WordPress and WooCommerce engineer with {{experience_years}} years of experience building bespoke plugins, complex checkout flows, and high-performance custom themes in {{tech_stack}}, I specialize in delivering scalable, secure digital commerce platforms.</p><p>I have attached my tailored resume and would be delighted to share relevant code samples and client case studies.</p><p>{{email_signature}}</p>",
                'is_default' => false,
            ],
            [
                'category' => 'InitialOutreach',
                'name' => 'Direct Cold Application (Full Stack Web Engineer)',
                'slug' => 'cold-app-full-stack',
                'subject_template' => 'Application for {{job_title}} — {{candidate_name}} (Full Stack Web Engineer)',
                'body_template' => "<p>Hi {{company}} Engineering Team,</p><p>I am excited to submit my application for the <strong>{{job_title}}</strong> role at {{company}}.</p><p>My background spans end-to-end full stack development using {{tech_stack}}, building reactive web applications, modern APIs, and clean, responsive UI components.</p><p>Please find my resume attached. Looking forward to discussing how my skillset aligns with {{company}}'s current initiatives.</p><p>{{email_signature}}</p>",
                'is_default' => false,
            ],
            [
                'category' => 'FollowUp',
                'name' => 'Post-Application Follow-Up (5-Day Check-in)',
                'slug' => 'follow-up-5-day-checkin',
                'subject_template' => 'Follow-up regarding {{job_title}} application — {{candidate_name}}',
                'body_template' => "<p>Dear {{company}} Team,</p><p>I hope you are having a productive week.</p><p>I wanted to follow up on the application I submitted recently for the <strong>{{job_title}}</strong> position at {{company}}.</p><p>I remain very enthusiastic about {{company}}'s mission and would love to confirm if any additional technical work samples or portfolio details are needed.</p><p>Thank you for your time and consideration.</p><p>{{email_signature}}</p>",
                'is_default' => true,
            ],
            [
                'category' => 'PostInterviewThankYou',
                'name' => 'Post-Technical Interview Thank You',
                'slug' => 'thank-you-post-interview',
                'subject_template' => 'Thank you for the interview — {{candidate_name}} ({{job_title}})',
                'body_template' => "<p>Dear {{company}} Team,</p><p>Thank you for taking the time to speak with me today regarding the <strong>{{job_title}}</strong> role.</p><p>I thoroughly enjoyed our conversation about your team's technical architecture, challenges, and goals. The discussion reinforced my strong interest in joining {{company}} and contributing to your team's success with my background in {{tech_stack}}.</p><p>Please let me know if you need any further information from my end.</p><p>{{email_signature}}</p>",
                'is_default' => true,
            ],
            [
                'category' => 'ContractProposal',
                'name' => 'Freelance & Contract Pitch (MVP & Rapid Delivery)',
                'slug' => 'contract-proposal-rapid-mvp',
                'subject_template' => 'Contract / Project Collaboration: {{job_title}} — {{candidate_name}}',
                'body_template' => "<p>Hi {{company}} Team,</p><p>I noticed {{company}} is actively looking for technical support for <strong>{{job_title}}</strong>.</p><p>As an independent software consultant with {{experience_years}} years of experience delivering rapid MVPs, database optimization, and legacy modernization in {{tech_stack}}, I am available for immediate contract engagements.</p><p>I would love to set up a brief 15-minute scoping call to explore how I can help you deliver your upcoming milestones on time.</p><p>{{email_signature}}</p>",
                'is_default' => true,
            ],
        ];

        $created = collect();
        foreach ($standards as $item) {
            $created->push(EmailTemplate::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'slug' => $item['slug'],
                ],
                $item
            ));
        }

        return $created;
    }

    /**
     * Build contextual variables array for placeholder interpolation.
     */
    public function buildContext(User $user, ?RoleProfile $role = null, ?array $vacancy = null): array
    {
        $profile = CandidateProfile::where('user_id', $user->id)->first();

        $techStack = $role ? implode(', ', $role->technical_skills ?? []) : 'PHP, Laravel, MySQL, JavaScript';
        $expYears = $role ? $role->experience_years : ($profile ? $profile->total_experience_years : 5.0);
        $signature = $role?->email_signature ?? "Best regards,\n{$user->name}\nEmail: {$user->email}";

        return [
            'candidate_name' => $user->name,
            'candidate_email' => $user->email,
            'candidate_phone' => $profile?->phone ?? '',
            'candidate_location' => $profile?->location ?? 'Remote',
            'company' => $vacancy['company_name'] ?? 'Your Company',
            'job_title' => $vacancy['job_title'] ?? 'Software Engineer',
            'role_profile' => $role?->name ?? 'Software Engineer',
            'experience_years' => $expYears,
            'tech_stack' => $techStack,
            'apply_url' => $vacancy['apply_url'] ?? '',
            'github_url' => $profile?->github_url ?? 'github.com',
            'linkedin_url' => $profile?->linkedin_url ?? 'linkedin.com',
            'portfolio_url' => $profile?->portfolio_url ?? '',
            'current_date' => date('F j, Y'),
            'email_signature' => nl2br(e($signature)),
        ];
    }

    /**
     * Tailor email body using Free-Only AI Assistant.
     */
    public function tailorEmailWithAI(
        User $user,
        EmailTemplate $template,
        array $vacancyContext,
        ?string $candidateInstructions = null
    ): string {
        $context = $this->buildContext($user, null, $vacancyContext);
        $rendered = $this->interpolationService->renderTemplate($template, $context);

        $prompt = "You are an expert career advisor. Tailor the following job application email to specifically highlight relevance to the target company and position.\n\n"
            . "Target Company: " . ($vacancyContext['company_name'] ?? 'Target Employer') . "\n"
            . "Target Job Title: " . ($vacancyContext['job_title'] ?? 'Developer') . "\n"
            . "Job Description / Requirements: " . substr((string) ($vacancyContext['description'] ?? ''), 0, 1000) . "\n"
            . "Candidate Instructions: " . ($candidateInstructions ?: 'Make it concise, persuasive, and professional.') . "\n\n"
            . "Original Email Body:\n" . strip_tags($rendered['body']) . "\n\n"
            . "Return only the tailored email body in clean HTML format (<p>, <strong>, etc.). Do not invent fictitious qualifications.";

        $req = new AIRequestDTO(
            prompt: $prompt,
            systemPrompt: 'You are a professional executive career outreach coach. Write concise, compelling job outreach emails.',
            temperature: 0.6
        );

        $response = $this->aiGateway->complete($user, $req);
        return $response->content;
    }
}

