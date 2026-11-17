<?php

namespace App\Modules\AI\app\Services;

use App\Models\User;
use App\Modules\AI\app\DTOs\AIRequestDTO;
use App\Modules\Applications\app\Models\Application;
use App\Modules\Email\app\Models\EmailAccount;
use App\Modules\Integrations\app\Models\IntegrationCredential;
use App\Modules\Integrations\app\Services\CredentialVaultService;
use App\Modules\Integrations\app\Services\HealthCheckService;
use App\Modules\Outlook\app\Models\OutlookMessage;
use App\Modules\Resume\app\Models\Resume;
use App\Modules\RoleProfiles\app\Models\RoleProfile;
use Illuminate\Support\Facades\Log;

class CopilotAssistantService
{
    public function __construct(
        protected AIGatewayService $aiGateway,
        protected CredentialVaultService $vaultService,
        protected HealthCheckService $healthService
    ) {}

    /**
     * Process an incoming user prompt from the global Copilot chatbot widget.
     * Capable of:
     * 1. Parsing user commands for API configuration ("set my gemini key ...", "connect gmail ...").
     * 2. Auto-filling settings and storing in AES-256 encrypted vault.
     * 3. Recommending best next steps & direct page navigation ("Go to CV Studio", "Review your applications").
     * 4. Answering career and setup questions using Gemini / Groq or structured fallback.
     */
    public function handleUserMessage(User $user, string $userMessage): array
    {
        $trimmed = trim($userMessage);
        $lower = strtolower($trimmed);

        // 1. Detect API Key or Provider Setting Intent
        $apiActionResult = $this->detectAndProcessApiAction($user, $trimmed, $lower);
        if ($apiActionResult) {
            return $apiActionResult;
        }

        // 2. Detect Navigation / Action Intent
        $navAction = $this->detectNavigationIntent($lower);
        if ($navAction) {
            return $navAction;
        }

        // 3. Fallback to AI Generative Response with Hydraulic JobPilot Context
        return $this->generateGenerativeGuidance($user, $trimmed);
    }

    /**
     * Detect if user wants to set an API key or configure a provider.
     */
    protected function detectAndProcessApiAction(User $user, string $rawMessage, string $lower): ?array
    {
        // Patterns like: "my gemini api key is AIzaSy..." or "set groq key gsk_..." or "api key: ..."
        $patterns = [
            'google_ai_studio' => '/(?:gemini|google\s*ai(?:\s*studio)?)\s*(?:api)?\s*(?:key)?\s*(?:is|=|:)?\s*([a-zA-Z0-9_\-]{25,})/i',
            'groq' => '/groq\s*(?:api)?\s*(?:key)?\s*(?:is|=|:)?\s*(gsk_[a-zA-Z0-9_\-]{20,})/i',
            'openrouter' => '/openrouter\s*(?:api)?\s*(?:key)?\s*(?:is|=|:)?\s*(sk-or-v1-[a-zA-Z0-9_\-]{20,})/i',
        ];

        foreach ($patterns as $providerKey => $regex) {
            if (preg_match($regex, $rawMessage, $matches)) {
                $extractedKey = trim($matches[1]);
                return $this->saveApiKeyFromChat($user, $providerKey, $extractedKey);
            }
        }

        // Check if user says "how to add api key" or "api setup"
        if (str_contains($lower, 'api') && (str_contains($lower, 'how') || str_contains($lower, 'where') || str_contains($lower, 'setup') || str_contains($lower, 'order') || str_contains($lower, 'add'))) {
            return [
                'reply' => "Here is your **Order-Wise API Setup Guide** to get 100% free AI & automated outreach working:\n\n" .
                    "1. **Google AI Studio (Gemini 2.5 Flash)** *(Top Recommended)*: Get a free key at [aistudio.google.com](https://aistudio.google.com). Paste it here: `my gemini key is <YOUR_KEY>` or enter it on the Settings page.\n" .
                    "2. **Groq Cloud (Llama 3.3 70B)** *(Fastest Backup)*: Free high-speed inference key at [console.groq.com](https://console.groq.com).\n" .
                    "3. **Personal Email (Gmail / Outlook)**: Connect your personal email with an App Password so JobPilot AI can send applications directly from your address.\n\n" .
                    "You can paste your key right into this chat, and I will auto-save it to your encrypted vault and test it live!",
                'quick_actions' => [
                    ['label' => 'Open Settings & Vault', 'url' => '/settings', 'icon' => 'bi-key-fill', 'autonav' => true],
                    ['label' => 'Configure Email Studio', 'url' => '/email-studio', 'icon' => 'bi-envelope-at', 'autonav' => true],
                ],
                'suggested_prompts' => [
                    'My gemini key is AIzaSy...',
                    'Where do I start as a new user?',
                    'Open Outlook Hub'
                ]
            ];
        }

        return null;
    }

    /**
     * Save extracted key into the User's Encrypted Vault.
     */
    protected function saveApiKeyFromChat(User $user, string $providerKey, string $apiKey): array
    {
        $integrations = $this->vaultService->getOrSeedIntegrationsForUser($user);
        $integration = $integrations->firstWhere('provider_key', $providerKey);

        if (!$integration) {
            $integration = IntegrationCredential::where('user_id', $user->id)
                ->where('provider_key', $providerKey)
                ->first();
        }

        if ($integration) {
            $integration->setSecrets(['api_key' => $apiKey]);
            $integration->is_enabled = true;
            $integration->save();

            // Run instant health check ping
            $ping = $this->healthService->check($integration);

            $providerDisplayName = match($providerKey) {
                'google_ai_studio' => 'Google AI Studio (Gemini 2.5 Flash)',
                'groq' => 'Groq Cloud (Llama 3.3 70B)',
                'openrouter' => 'OpenRouter',
                default => ucfirst($providerKey)
            };

            return [
                'reply' => "🎉 **Success!** Your API key for **{$providerDisplayName}** has been securely encrypted with AES-256-GCM and saved to your personal vault.\n\n" .
                    "**Live Health Ping**: " . ($ping['status'] === 'Healthy' ? '🟢 Healthy & Connected' : '🟡 ' . $ping['message']) . "\n\n" .
                    "Would you like me to take you to the **Settings** page to review your integrations, or to **AI Chat** to start tailoring CVs and generating cover letters?",
                'quick_actions' => [
                    ['label' => 'Review in Settings & Vault', 'url' => '/settings', 'icon' => 'bi-sliders', 'autonav' => false],
                    ['label' => 'Start Career Chat', 'url' => '/ai-chat', 'icon' => 'bi-chat-dots-fill', 'autonav' => true],
                    ['label' => 'Explore Jobs', 'url' => '/jobs', 'icon' => 'bi-briefcase-fill', 'autonav' => true],
                ],
                'autofill' => [
                    'target_page' => '/settings',
                    'field' => $providerKey,
                    'status' => 'configured'
                ]
            ];
        }

        return [
            'reply' => "I received your key, but could not locate the provider slot. Please visit the **Settings & Vault** page to enter it manually.",
            'quick_actions' => [
                ['label' => 'Open Settings', 'url' => '/settings', 'icon' => 'bi-gear']
            ]
        ];
    }

    /**
     * Detect user navigation / feature query.
     */
    protected function detectNavigationIntent(string $lower): ?array
    {
        $routesMap = [
            'outlook' => [
                'keywords' => ['outlook', 'mail', 'inbox', 'check mail', 'sent mail', 'calendar', 'to do', 'todo'],
                'url' => '/outlook',
                'title' => 'Outlook Hub',
                'description' => 'Your dedicated Microsoft Outlook Web environment for Mail (Inbox/Sent), Calendar interviews, Recruiter contacts, and Tasks.'
            ],
            'cv_studio' => [
                'keywords' => ['cv', 'resume', 'cv studio', 'tailor', 'resume builder', 'tailor cv'],
                'url' => '/cv-studio',
                'title' => 'CV Studio & Tailor',
                'description' => 'Tailor and optimize your resume for targeted job postings with ATS score matching.'
            ],
            'jobs' => [
                'keywords' => ['job', 'jobs', 'find job', 'job search', 'search jobs', 'vacancies'],
                'url' => '/jobs',
                'title' => 'Job Discovery Feed',
                'description' => 'Explore scraped and matched job listings aligned with your role profiles.'
            ],
            'ai_chat' => [
                'keywords' => ['ai chat', 'copilot', 'chat workspace', 'assistant', 'ask ai'],
                'url' => '/ai-chat',
                'title' => 'AI Chat Workspace',
                'description' => 'Full-screen career strategy copilot with full context from your resumes and target jobs.'
            ],
            'settings' => [
                'keywords' => ['settings', 'vault', 'api key', 'api keys', 'credentials', 'password'],
                'url' => '/settings',
                'title' => 'Settings & Encrypted Vault',
                'description' => 'Manage your external API keys, email SMTP credentials, and safety policies.'
            ],
            'email_studio' => [
                'keywords' => ['email studio', 'mailer', 'send email', 'outreach', 'email campaign'],
                'url' => '/email-studio',
                'title' => 'Email Outreach Studio',
                'description' => 'Manage connected sender accounts (Gmail, Outlook, SMTP), review queue, and delivery quotas.'
            ],
            'admin' => [
                'keywords' => ['admin', 'superadmin', 'admin cockpit', 'governance', 'kill switch'],
                'url' => '/admin',
                'title' => 'Admin Central Cockpit',
                'description' => 'Superadmin dashboard with emergency circuit breakers, cache flushes, and tenant audits.'
            ]
        ];

        foreach ($routesMap as $key => $info) {
            foreach ($info['keywords'] as $kw) {
                if (str_contains($lower, $kw) && (str_contains($lower, 'open') || str_contains($lower, 'go to') || str_contains($lower, 'take me') || str_contains($lower, 'show') || str_contains($lower, 'where') || $lower === $kw)) {
                    return [
                        'reply' => "I can take you directly to **{$info['title']}**! {$info['description']}\n\nClick the button below or wait for automatic navigation.",
                        'quick_actions' => [
                            ['label' => "Open {$info['title']}", 'url' => $info['url'], 'icon' => 'bi-box-arrow-up-right', 'autonav' => true],
                            ['label' => 'Back to Dashboard', 'url' => '/', 'icon' => 'bi-speedometer2', 'autonav' => false]
                        ],
                        'navigate_to' => $info['url']
                    ];
                }
            }
        }

        // New User Onboarding Query
        if (str_contains($lower, 'where to start') || str_contains($lower, 'first step') || str_contains($lower, 'help me start') || str_contains($lower, 'new user') || str_contains($lower, 'guide me')) {
            return [
                'reply' => "👋 **Welcome to JobPilot AI! Here is your quick 3-step roadmap to start getting interviews:**\n\n" .
                    "1. **Step 1: Role Profiles & CV Studio** (/role-profiles)\n" .
                    "   Define your target role (e.g. Senior Laravel Engineer) and upload or generate your baseline CV.\n\n" .
                    "2. **Step 2: Connect Your Free AI & Email** (/settings)\n" .
                    "   Add a free Google Gemini key or connect your personal Gmail/Outlook so the system can tailor resumes and dispatch applications.\n\n" .
                    "3. **Step 3: Discover & Apply via Outlook Hub** (/outlook)\n" .
                    "   Search matched jobs, send personalized recruiter outreach, and manage technical screen calendar invites in one place.\n\n" .
                    "Where would you like to begin right now?",
                'quick_actions' => [
                    ['label' => '1. Setup Role Profile', 'url' => '/role-profiles', 'icon' => 'bi-person-badge', 'autonav' => false],
                    ['label' => '2. Connect Free API Key', 'url' => '/settings', 'icon' => 'bi-key', 'autonav' => false],
                    ['label' => '3. Open Outlook Hub', 'url' => '/outlook', 'icon' => 'bi-envelope', 'autonav' => false],
                ]
            ];
        }

        return null;
    }

    /**
     * Synthesize rich response via Gemini or structured fallback.
     */
    protected function generateGenerativeGuidance(User $user, string $userMessage): array
    {
        // Hydraulic context of user state
        $profileCount = RoleProfile::where('user_id', $user->id)->count();
        $resumeCount = Resume::where('user_id', $user->id)->count();
        $appCount = Application::where('user_id', $user->id)->count();
        $msgCount = OutlookMessage::where('user_id', $user->id)->count();
        $isAdmin = $user->isAdmin();

        $systemPrompt = "You are JobPilot AI Assistant, a friendly, ultra-competent hydraulic career copilot and onboarding concierge. " .
            "You guide users step-by-step through setting up their API keys, configuring their personal email (Gmail/Outlook), finding jobs, tailoring CVs, and managing the Outlook Hub. " .
            "Current User: {$user->name} ({$user->email}), Admin: " . ($isAdmin ? 'Yes' : 'No') . ". " .
            "User Data Context: Profiles: {$profileCount}, Resumes: {$resumeCount}, Applications: {$appCount}, Outlook Messages: {$msgCount}. " .
            "Keep answers concise, actionable, and formatted in clean Markdown with clickable bold links. If user asks where to go, recommend the exact page (/settings, /outlook, /cv-studio, /jobs, /email-studio).";

        try {
            $req = new AIRequestDTO(
                prompt: $userMessage,
                systemPrompt: $systemPrompt,
                temperature: 0.6,
                maxTokens: 500
            );

            $response = $this->aiGateway->generate($req, 'gemini', $user);

            return [
                'reply' => $response->content,
                'quick_actions' => [
                    ['label' => 'Settings & Vault', 'url' => '/settings', 'icon' => 'bi-sliders', 'autonav' => false],
                    ['label' => 'Outlook Hub', 'url' => '/outlook', 'icon' => 'bi-envelope-fill', 'autonav' => false],
                    ['label' => 'AI Chat Workspace', 'url' => '/ai-chat', 'icon' => 'bi-chat-dots-fill', 'autonav' => false],
                ],
                'suggested_prompts' => [
                    'How do I get a free Gemini API key?',
                    'How do I connect my personal Outlook or Gmail?',
                    'Take me to Job Discovery'
                ]
            ];
        } catch (\Throwable $e) {
            Log::info('Generative copilot fallback used: ' . $e->getMessage());

            // High-quality structured fallback response
            return [
                'reply' => "I am here to guide you step-by-step through JobPilot AI!\n\n" .
                    "- **Need to setup API keys?** Tell me: `my gemini key is <KEY>` or go to **[Settings & Vault](/settings)**.\n" .
                    "- **Want to send emails from your own address?** Go to **[Email Studio](/email-studio)** to connect Gmail or Outlook SMTP.\n" .
                    "- **Ready to manage recruiter emails and interviews?** Open your dedicated **[Outlook Hub](/outlook)**.\n\n" .
                    "How can I assist you right now?",
                'quick_actions' => [
                    ['label' => 'Go to Settings & Vault', 'url' => '/settings', 'icon' => 'bi-key-fill', 'autonav' => false],
                    ['label' => 'Open Outlook Hub', 'url' => '/outlook', 'icon' => 'bi-envelope-open-fill', 'autonav' => false],
                    ['label' => 'View Applications', 'url' => '/applications', 'icon' => 'bi-journal-check', 'autonav' => false],
                ],
                'suggested_prompts' => [
                    'Where do I start?',
                    'Setup API keys order-wise',
                    'Open Outlook Hub'
                ]
            ];
        }
    }
}
