# JobPilot AI — Playwright Browser Automation Guide

## 1. Overview & Architecture

JobPilot AI incorporates an end-to-end browser test automation suite utilizing **Playwright Test** (`@playwright/test` v1.64.1). Browser testing validates DOM interactions, client-side JavaScript, responsive layouts, accessibility selectors, and full multi-page candidate workflows.

### Directory Structure
```
laravel/
├── playwright.config.ts
└── tests/
    └── browser/
        ├── auth/
        │   └── auth.spec.ts           # Login, Registration, Remember Me, Invalid auth
        ├── dashboard/
        │   └── dashboard.spec.ts      # Navigation, Quota monitors, System indicators
        ├── jobs/
        │   └── jobs.spec.ts           # Discovery workbench, Filters, AI Search
        ├── applications/
        │   └── applications.spec.ts   # Staging, Deduplication alerts, Status kanban
        ├── resume/
        │   └── resume.spec.ts         # LaTeX CV editor, Template switcher, ATS score
        ├── email/
        │   └── email.spec.ts          # Template manager, AI email tailoring, Review queue
        ├── ai/
        │   └── ai.spec.ts             # Career Copilot Chat, zero-cost streaming
        ├── linkedin/
        │   └── linkedin.spec.ts       # XYZ Bullet transformer, Content drafts, Banners
        ├── archives/
        │   └── archives.spec.ts       # CSV/JSONL generation, Google Drive sync
        └── settings/
            └── settings.spec.ts       # Credential vault, Provider toggles, Ping check
```

---

## 2. Multi-Device Matrix Configuration

The Playwright configuration (`laravel/playwright.config.ts`) runs across 4 distinct viewports to ensure 100% responsive reliability:

| Device Profile | Resolution | User Agent | Target Platform |
|---|---|---|---|
| **Desktop High-Res** | 1920 × 1080 | Desktop Chrome | Ultra-wide workstation |
| **Laptop Standard** | 1366 × 768 | Desktop Chrome | Typical laptop screen |
| **Tablet Portrait** | 768 × 1024 | Mobile Safari (iPad Gen 7) | Collapsible sidebar layout |
| **Mobile Modern** | 390 × 844 | Mobile Safari (iPhone 13) | Off-canvas mobile navigation |

---

## 3. Running Browser Automation

### Running Full Browser Suite
```powershell
Set-Location c:\xampp\htdocs\JobPilotAI\laravel
npx playwright test
```

### Running Targeted Module Specs
```powershell
# Authentication Flow
npx playwright test tests/browser/auth/auth.spec.ts

# Resume & CV Studio
npx playwright test tests/browser/resume/resume.spec.ts

# Jobs Discovery & Application Staging
npx playwright test tests/browser/jobs/jobs.spec.ts
```

### Generating Visual Traces & Screenshots
To run in headed mode and inspect execution live:
```powershell
npx playwright test --headed
```

To view HTML test reports and trace viewer:
```powershell
npx playwright show-report
```

Screenshots and test artifacts are captured automatically in `laravel/playwright-report/` and `laravel/test-results/` on failure.

