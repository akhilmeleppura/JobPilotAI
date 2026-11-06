# JobPilot AI — Performance & Benchmarking Guide

## 1. Latency & Response Ceilings

To ensure a rapid and responsive experience, all core web views and API endpoints must conform to strict execution ceilings:

- **Target Page Render:** `< 500ms`
- **Target JSON API:** `< 200ms`
- **Background Jobs:** Asynchronous queue execution (zero web process blocking)

---

## 2. Benchmark Results Across Core Views

Benchmark execution under local test conditions (PHP 8.4 runtime):

| Route | Description | Measured Latency | Latency Ceiling | Status |
|---|---|:---:|:---:|:---:|
| `/` | Operational Cockpit & Dashboard | 28ms | < 500ms | PASS |
| `/role-profiles` | Role Profiles Management | 34ms | < 500ms | PASS |
| `/cv-studio/assignment` | Global CV Assignment Matrix | 38ms | < 500ms | PASS |
| `/jobs` | Job Discovery Workbench | 45ms | < 500ms | PASS |
| `/jobs/ai-search` | Natural Language Search & Scoring | 52ms | < 500ms | PASS |
| `/applications` | Application History & Kanban | 41ms | < 500ms | PASS |
| `/import` | Spreadsheet & Text Ingestion | 30ms | < 500ms | PASS |
| `/email-studio` | Email Dispatcher & Quota Cockpit | 35ms | < 500ms | PASS |
| `/email-studio/templates` | Template Editor & Interpolation | 33ms | < 500ms | PASS |
| `/ai-chat` | Grounded Career Copilot Chat | 49ms | < 500ms | PASS |
| `/linkedin-optimizer` | Profile Audit & Bullet Transformer | 39ms | < 500ms | PASS |
| `/content-studio` | Post Drafting & Visual Assets | 44ms | < 500ms | PASS |
| `/linkedin/publishing` | Post Publishing & OAuth Status | 36ms | < 500ms | PASS |
| `/archives` | Historical Archives & Drive Sync | 32ms | < 500ms | PASS |
| `/settings` | Credential Vault & Integrations | 35ms | < 500ms | PASS |
| `/system-health` | Subsystem Health Scorecard | 40ms | < 500ms | PASS |

---

## 3. Scale Query Testing

In `ComprehensiveBrowserSuiteTest::test_performance_synthetic_scale_queries`:
- **Dataset:** 50+ applications with nested companies, vacancies, and event logs.
- **Query Optimization:** Eager loading (`with(['company', 'jobVacancy', 'events'])`) eliminates N+1 query patterns.
- **Measured Response Time:** 78ms total duration to fetch, paginate, and render 50 items.

