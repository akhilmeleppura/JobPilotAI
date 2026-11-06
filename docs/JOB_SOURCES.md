# JobPilot AI — Job Discovery Sources & Connectors

## 1. Supported Discovery Sources
JobPilot AI integrates external job vacancies through official, authorized public APIs.

| Provider | Integration Type | Authentication | Primary Scope | Free Tier Constraints |
|---|---|---|---|---|
| **Adzuna** | REST API | `app_id` + `app_key` | Global / Country-specific | Free developer tier |
| **Jooble** | REST API | API Key | Global / Keyword & Location | Free partner tier |
| **Himalayas** | REST API | None / Public | Remote developer roles | Public open feed |
| **Remote OK** | JSON API | Public read | Remote tech jobs | Free public endpoint |
| **Remotive** | REST API | Public read | Remote engineering | Free public endpoint |
| **Greenhouse** | Boards API | Public board token | Company-specific listings | Public career boards |
| **Lever** | Postings API | Public site token | Company-specific listings | Public career boards |
| **Ashby** | Public Job Board API | Public company token | High-growth tech startups | Public career boards |
| **SmartRecruiters**| Public API | Public identifier | Global enterprise jobs | Public career board API |
| **Tavily** | Search AI API | API Key | Deep-web vacancy discovery | Free monthly tier |

## 2. Scraping & Bot Policies
- **Strict Compliance**: No bypassing CAPTCHAs, paywalls, or authenticated portals without explicit APIs.
- **Source Attribution**: Every recorded vacancy permanently retains `source_provider`, `source_url`, and the timestamp of retrieval.
- **Public HR Email Verification**: When HR contact emails are detected, the system records `hr_email_source_url` proving the email was publicly published by the employer.

