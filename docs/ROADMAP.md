# JobPilot AI — Development Roadmap

## Phase Overview

- [x] **PHASE 0: Environment Inspection & Project Structure**
  - Verify PHP, Composer, Node.js, npm, git.
  - Setup `JobPilotAI/` project folder, `docs/`, `backups/`, and `laravel/`.
  - Establish documentation suite.

- [x] **PHASE 1: Laravel Initialization, Authentication & Core UI**
  - Create Laravel application with core scaffolding.
  - Setup Fortify / Session authentication foundation (login, register, password reset, security settings).
  - Responsive Bootstrap 5 layout with navigation, sidebar, and base dashboard.

- [x] **PHASE 2: Secure Modular Architecture & TiDB Configuration**
  - Modular directory layout (`app/Modules/*` with automatic discovery).
  - TiDB Cloud Starter MySQL connection configuration and SSL verification.
  - Shared domain interfaces, contracts (`FingerprintableInterface`), DTO base, and base event dispatcher.

- [x] **PHASE 3: Candidate Profile & Role Profiles Manager**
  - Default roles: Laravel/PHP Backend, WordPress/WooCommerce, Full Stack, General Web, Freelance.
  - Skills, experience, target titles, custom signatures, and location preferences.
  - Complete role profile manager (CRUD, default switcher, activation toggle, auto-seeder).

- [x] **PHASE 4: Permanent Application Registry & Duplicate Checking**
  - `companies`, `job_vacancies`, `application_registry`, `application_identity_keys`, `applications`, `application_events`.
  - SHA-256 vacancy fingerprinting and cross-platform deduplication engine (`FingerprintService`, `DuplicateCheckerService`).
  - Real-time duplicate checking, 60-day cool-off enforcement, and lifecycle timeline event auditing.

- [x] **PHASE 5: Google Drive & Monthly Sheets Archives**
  - Scheduled JSONL.gz packaging and checksum validation (`ArchivePackagingService`).
  - Google Drive upload and Google Sheets sync for long-term record permanence (`GoogleDriveArchiveService`).
  - Automated monthly artisan command (`jobpilot:archive-monthly`) and download manager.

- [x] **PHASE 6: Central Settings & Integration Manager**
  - Encrypted credential vault (`APP_KEY` AES-256-GCM via `CredentialVaultService`).
  - Connection health checks, ping monitors (`HealthCheckService`), quota reporting, and rate limit tracking.
  - Multi-provider settings manager (AI, Email, Cloud Archives, Job Sources, Policy Governance).

- [x] **PHASE 7: Free-Only AI Providers Gateway**
  - Google AI Studio (Gemini 2.5/Flash), Groq (Llama 3.3 70B), OpenRouter Free (`:free` tags), Local Ollama.
  - `FREE_RESOURCES_ONLY=true` policy enforcer (`AIGatewayService` with automatic resilient fallback).
  - Unified completion contract (`AIProviderInterface`, `AIRequestDTO`, `AIResponseDTO`) and prompt playground.

- [x] **PHASE 8: Gmail API & Mail Automation Engine**
  - `MailProviderInterface`, `GmailAPIProvider` (OAuth2 RFC 2822 base64url), and `LogRelayProvider`.
  - Daily application ceiling (default 200/day) and auto-pausing quota tracker (`MailAutomationService`).
  - To, CC, and BCC recipient counting, anti-abuse zero-guessed-emails verification, and human-in-the-loop review.

- [x] **PHASE 9: Email Studio & Multiple Communication Templates**
  - 8 Communication categories, 6 standard auto-seeded templates, and CRUD manager (`EmailTemplateService`).
  - Placeholder interpolation engine (`{{company}}`, `{{job_title}}`, `{{tech_stack}}`, `{{email_signature}}`).
  - Mini AI Assistant for vacancy-tailored email drafting using Free AI Gateway.

- [x] **PHASE 10: Global CV Assignment Manager**
  - Modes 1–4 resolution pipeline (Global Default, Per-Role Profile, Per-Application override, AI Semantic Keyword Match).
  - Resume & ResumeVersion models, auto-seeding 5 role-targeted CVs with baseline LaTeX sources and ATS scores.
  - Interactive assignment matrix UI, role binding switcher, and AJAX semantic vacancy keyword evaluator.

- [x] **PHASE 11: CV Studio & Overleaf-Compatible LaTeX Editor**
  - 6 Modular ATS LaTeX templates (backend_developer, wordpress_specialist, full_stack_developer, ats_classic, executive_lead, project_focused).
  - Isolated compilation sandbox service (`LatexCompilerService`) with `-no-shell-escape` security, macro injection sanitization, 10s execution timeout, and authentic PDF fallback generation.
  - ATS Scoring & Analysis service (`ATSAnalysisService`) checking sections, verbs, metrics, and red flags (tabular structures, embedded images).
  - Interactive split-screen editor UI with monospace code editor, live PDF preview iframe, version history checkpoints, and 1-click Overleaf compatibility.

- [x] **PHASE 12: Job Source API Integrations**
  - Unified `JobSourceInterface` and `JobVacancyDTO` contract.
  - 6 Free & public adapters: Himalayas, Remote OK, Remotive, Adzuna (Developer API), Jooble, and ATS Boards (Greenhouse, Lever, Ashby).
  - Ingestion & deduplication engine (`JobDiscoveryService`) auto-registering canonical fingerprints into `ApplicationRegistry`.
  - 1-Click "Stage for Outreach" binding Mode 1–4 optimal CVs and email templates into draft applications.
  - Interactive discovery dashboard (`jobs::discovery.index`), multi-filter controls, and Artisan command (`jobpilot:fetch-jobs`).

- [x] **PHASE 13: Spreadsheet/Document Import & AI Normalization**
  - Native zero-dependency document parser (`DocumentParserService`) supporting CSV (auto-delimiter, UTF-8 BOM), XLSX (ZipArchive/XML streaming), Google Sheets public links, and email text snippets.
  - Heuristic column schema inferrer (`SchemaMappingService`) with fuzzy synonym matching across Company, Title, Status, Date, URL, and Location.
  - Ingestion & deduplication engine (`ImportNormalizationService`) resolving companies, hashing fingerprints, and creating historical `Application` records.
  - Import Studio UI (`importexport::import.index` and `mapping.blade.php`), batch tracking (`import_batches`), and sample template exporter.

- [x] **PHASE 14: Natural Language AI Job Search & Matching**
  - Conversational query parser (`NaturalLanguageJobSearchService`) supporting Free AI Gateway (Gemini/Groq/Ollama) and regex heuristic fallbacks.
  - Multi-factor match scoring engine (`JobMatchScoringService`): skills overlap (35%), title & seniority (20%), work mode (15%), compensation (10%), and prompt keywords (10%).
  - Ranked search results workbench (`jobs::search.ai`) with dynamic scoring persona switcher, circular match badges, pros/cons breakdown, and 1-click application staging.
  - Real-time match score inspection API (`/jobs/{id}/match-score`).

- [x] **PHASE 15: Full AI Chat Workspace**
  - Multi-session chat schema (`chat_sessions` and `chat_messages` tables).
  - Grounded career context builder (`AIChatContextService`) merging candidate personas, master ATS resumes, and real-time application pipelines.
  - Conversational copilot engine (`AIChatService`) supporting multi-document attachments, auto-naming, and Free-Only AI Gateway fallback.
  - Multi-pane workspace UI (`ai::chat.index`) featuring session history, stream scroll, quick prompt chips, and live career context drawer.

- [x] **PHASE 16: LinkedIn Profile Optimizer**
  - Migration & Eloquent model (`linkedin_profiles`, `LinkedInProfile`).
  - Recruiter keyword gap analysis & discoverability scoring engine (`LinkedInOptimizerService::auditProfile`).
  - 4-variation high-converting headline generator under 220 characters (`Impact & Authority`, `Keyword-Dense Boolean SEO`, `Quantified Metrics`, `Advisory & Modernization`).
  - First-person narrative About section generator with above-the-fold hook preview.
  - Experience bullet point transformer using Google XYZ framework (*Accomplished [X], as measured by [Y], by doing [Z]*).
  - Multi-persona switcher workbench UI (`linkedin::optimizer.index`) with real-time audit recalculation and zero-password safety.

- [x] **PHASE 17: LinkedIn Content & Image Studio**
  - Schema & Eloquent model for technical posts (`content_posts`, `ContentPost`) supporting drafts, scheduled slots, and published history.
  - Zero-cost visual asset generator (`CloudflareImageService`): Cloudflare Workers AI SDXL integration (10k free daily neurons) with deterministic local high-res SVG developer promotional banner fallback.
  - Technical topic ideator & post drafting engine (`ContentStudioService`) generating compelling hooks, architectural breakdowns, bulleted takeaways, curated hashtags, and image prompts.
  - Interactive multi-pane workbench UI (`contentstudio::studio.index`) with 4-card statistics, live desktop/mobile LinkedIn post feed preview, 1-click topic selection, and content calendar.
  - Direct sidebar navigation integration (`/content-studio`).

- [x] **PHASE 18: Authorized LinkedIn Publishing**
  - Official LinkedIn Member API v2 integration with OAuth2 workflow (`w_member_social`, `openid`, `profile`).
  - Mandatory Human-in-the-Loop review queue (`linkedin::publishing.index`) preventing background automated posting.
  - Strict zero-bot and anti-spam governance (no automated connections, messaging, or scraping).
  - Multi-asset post publisher (`LinkedInPublisherService`) with official UGC Posts API (`/v2/ugcPosts`), digital media upload registration (`/v2/assets`), and post state lifecycle tracking (`ContentPost::published`).
  - Safe publishing frequency guardrails (max 25 posts/day) and direct clickable live post URLs.

- [x] **PHASE 19: Monitoring, Security, Backups & Deployment**
  - Subsystem health and observability engine (`SystemMonitoringService`) auditing Database, Filesystem Storage, AI Gateway Zero-Spend policy, Queue execution, and Runtime Security.
  - Automated database backup engine (`BackupService`) supporting SQLite and TiDB Cloud snapshots with SHA-256 integrity checksums, 7-backup automatic rotation, and 1-click downloads.
  - Artisan backup command (`jobpilot:backup`) runnable on scheduled cron.
  - Production security middleware (`SecurityHeadersMiddleware`) enforcing `nosniff`, `SAMEORIGIN`, `1; mode=block`, and strict referrer policies.
  - Oracle Cloud Always Free VM (ARM64 Ampere A1, Ubuntu 24.04 LTS) setup and deployment scripts (`deploy/oracle_cloud_setup.sh`, `nginx-jobpilot.conf`, `jobpilot-worker.conf`).
  - System Monitoring and Backups Dashboard (`monitoring::index`) with runtime environment audit and real-time health JSON endpoint (`/system-health/api`).

