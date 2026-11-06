# JobPilot AI — Complete Quality Assurance & Testing Plan

**Project:** JobPilot AI — Laravel 13 Modular Monolith  
**Runtime:** PHP 8.4.27 CLI / Node.js v24.13.0 / Bootstrap 5 / SQLite & TiDB Cloud  
**Policy:** `FREE_RESOURCES_ONLY=true` (Zero-Spend Enforcement)

---

## 1. Scope & Objectives
This testing plan defines end-to-end quality assurance across all modules, automated feature testing, browser automation, security auditing, and deployment verification.

The target is 100% test passability without bypassing sandbox policies or running unverified code.

---

## 2. Test Execution Matrix

| Tier | Engine / Harness | Focus Area | Status |
|---|---|---|:---:|
| **Unit & Feature** | PHPUnit 12 on PHP 8.4 | Domain logic, controllers, policies, algorithms | 126 / 126 Passing |
| **Database & Integrity** | SQLite & TiDB Schema tests | Migrations, foreign keys, SHA-256 deduplication, backups | Passing |
| **Browser E2E** | Playwright (Chromium) | User authentication, workflows, forms, modals | Scheduled |
| **Responsive UI** | Playwright Multi-Viewport | Desktop (1920x1080), Laptop (1366x768), Tablet (768x1024), Mobile (390x844) | Scheduled |
| **Integration & Mocks** | Laravel Http::fake / Providers | AI Gateway, Gmail, Cloudflare, LinkedIn, Drive | Verified |
| **Security Audit** | Security Headers & Penetration Checks | SQLi, XSS, CSRF, IDOR, SSRF, LaTeX injection | Verified |
| **Performance** | Synthetic Data Benchmarking | Query count, N+1 detection, p95 latency | Scheduled |
| **Deployment & Smoke** | Live Health Endpoint & Smoke Tests | `/system-health/api`, Oracle Cloud Always Free VM readiness | Verified |

---

## 3. Module Test Coverage Blueprint

### A. Authentication & Access Control (`Identity`)
- User Registration with validation (name, email format, password confirmation, unique constraint).
- User Login (valid credentials, invalid credentials, remember me, session regeneration).
- Logout & Session Invalidation.
- Unauthenticated route protection (`auth` middleware) across all private endpoints.

### B. Career & Profile Modules (`RoleProfiles`, `Resume`, `LinkedIn`)
- 5 default seeded personas (`Laravel & PHP Backend Specialist`, `WordPress Specialist`, `Full Stack Developer`, `Lead Software Architect`, `Technical Consultant`).
- Role CRUD, custom role creation, default switching, active toggling.
- CV Assignment Manager: 4 Modes (Global Default, Role-Linked, Application Override, AI Semantic Match).
- CV Studio: ATS template rendering (6 LaTeX templates), macro sanitization (`-no-shell-escape`), version checkpoints, PDF streaming.
- LinkedIn Optimizer: Recruiter score calculation, missing keyword analysis, 4-headline variation picker under 220 chars, About section hook preview, Google XYZ bullet restructuring.
- LinkedIn Publishing: OAuth2 authorization flow, human-in-the-loop review queue, multi-asset post publishing, zero-bot protection.

### C. Jobs & Application Pipeline (`Jobs`, `Applications`, `ImportExport`)
- Job Discovery: 6 zero-cost connectors (Adzuna, Jooble, Himalayas, Remote OK, Remotive, ATS Boards).
- Natural Language AI Job Search: Conversational intent parsing, multi-factor scoring (Skills, Title, Work Mode, Compensation, Query specifics).
- Application Registry: SHA-256 fingerprint deduplication, status transition logging, 60-day reopening rules.
- Spreadsheet Import: Native XLSX/CSV parsing, fuzzy synonym column mapping, bulk deduplication.

### D. Outreach & Archives (`Email`, `Archives`)
- Email Studio: Multi-template interpolation, HTML/plain-text previews, anti-abuse email validation.
- Mail Automation: Gmail OAuth2 outreach, daily quota enforcement, Human-in-the-Loop review queue.
- Drive & Sheets Archive: Monthly batch archives, SHA-256 integrity checksums, Google Drive/Sheets sync.

### E. Intelligence & System Monitoring (`AI`, `ContentStudio`, `Monitoring`)
- AI Chat Workspace: Multi-turn grounded career context copilot, attachment parsing, token analytics.
- Content Studio: Technical topic ideator, post drafting, Cloudflare Workers AI image generation with local SVG vector fallback.
- System Monitoring: Subsystem health checks (DB, Storage, AI, Queues, Security), automated backups (`jobpilot:backup`), 7-snapshot rotation, security headers middleware.

---

## 4. Test Environments
1. **Local Test Environment (`.env.testing`)**:
   - SQLite in-memory / dedicated test database.
   - `APP_ENV=testing`, `APP_DEBUG=true`, `FREE_RESOURCES_ONLY=true`.
   - Mocked external APIs for all third-party services.
2. **Local Live Server (`php artisan serve`)**:
   - Running at `http://127.0.0.1:8000`.
   - Used for browser automation and end-to-end UI verification.
3. **Production Target (`Oracle Cloud Always Free Ampere A1`)**:
   - Ubuntu 24.04 LTS, Nginx HTTP/2, Let's Encrypt SSL, PHP 8.4-FPM, Supervisor workers, Linux crontab.

