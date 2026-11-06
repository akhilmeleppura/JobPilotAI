# Dynamic Free Email Allowance & Quota Engine

JobPilot AI enforces strict zero-spend resource governance without arbitrary application-side rate limits. Universal hardcoded quotas (such as 200 emails/day) have been completely removed. Instead, JobPilot AI dynamically discovers, monitors, and respects the actual free allowances provided by each connected email service.

---

## 1. Architectural Principles

1. **No Fixed Application Sending Ceilings**:
   - Universal hardcoded limits (e.g. 200 emails/day) are eliminated from database defaults, model structures, and business logic.
   - User preferred volume can only be set as an optional custom safety cap (disabled by default) and never expands provider capabilities.

2. **Source Classification Transparency**:
   Every allowance value displayed on the dashboard has a verified origin classification:
   - `PROVIDER_REPORTED`: Live value returned by provider APIs (e.g., Google Apps Script `MailApp.getRemainingDailyQuota()`).
   - `DOCUMENTED_PLAN`: Official free-tier allowance verified against provider documentation.
   - `LOCALLY_MEASURED`: Successful recipient counts tracked and aggregated in JobPilot AI.
   - `ESTIMATED`: Remaining allowance calculated from documented limit minus observable usage.
   - `UNKNOWN`: The provider does not expose reliable remaining quota. JobPilot AI displays:
     > *"Exact remaining quota unavailable"*
     Fake numbers or fabricated credits are strictly prohibited.

3. **Multi-Window Allowance Modeling**:
   - Rolling 24-Hour Windows (Gmail API, Outlook Graph)
   - Calendar Day Windows (Google Apps Script, Zoho Mail)
   - Hourly Rate Limits (SMTP2GO, SendGrid)
   - Monthly Allowances (Transactional providers)

4. **Shared Account Awareness**:
   - If multiple services (such as Gmail API and Google Apps Script) connect to the identical email account, JobPilot AI detects the shared underlying pool and prevents misleading additive sums.

---

## 2. Supported Email Providers & Classification

| Provider | Default Free Plan | Documented Allowance | Quota Source | Policy Status | Reset Cadence | Notes |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Gmail API** | Gmail Personal Free | ~500 recipients guidance | `LOCALLY_MEASURED` | `VERIFIED_ALLOWED` | Rolling 24h | Gmail API does not expose live remaining quota endpoint. |
| **Google Apps Script** | Consumer Free Plan | 100 recipients / day | `PROVIDER_REPORTED` | `VERIFIED_ALLOWED` | ~1:00 AM PST | Live quota synced via `MailApp.getRemainingDailyQuota()`. |
| **Outlook Graph** | Outlook.com Consumer | ~300 recipients guidance | `LOCALLY_MEASURED` | `VERIFIED_ALLOWED` | Rolling 24h | Personal consumer accounts do not expose live counters. |
| **Zoho Mail** | Forever Free (5 Users) | 150 webmail recipients | `LOCALLY_MEASURED` | `REQUIRES_REVIEW` | Midnight local | Free accounts restrict SMTP relay; requires web/API check. |
| **Mailjet** | Free Tier (200/day, 6k/mo) | 200/day, 6,000/mo | `DOCUMENTED_PLAN` | `PROHIBITED` | Midnight UTC | Policy prohibits cold job outreach without prior opt-in. |
| **SMTP2GO** | Free Tier (1,000/mo) | 1,000/month, 25/hour | `DOCUMENTED_PLAN` | `PROHIBITED` | Monthly rolling | Strict anti-spam policy restricts unsolicited 1-to-1 applications. |

---

## 3. Database Schema

The dynamic allowance engine uses four normalized tables:

1. **`email_provider_accounts`**:
   - `id`, `user_id`, `provider`, `account_email`, `plan_name`, `connection_status`, `policy_status`, `enabled`, `is_paused`, `pause_reason`, `custom_safety_cap`, `encrypted_credentials`, `last_synced_at`, `last_sent_at`.
2. **`email_provider_allowances`**:
   - `id`, `provider_account_id`, `allowance_type`, `allowance_limit`, `used_count`, `remaining_count`, `quota_source`, `measured_at`, `reset_at`, `status`.
3. **`email_provider_usage`**:
   - `id`, `provider_account_id`, `message_id`, `recipient_count`, `sent_at`, `provider_response`, `delivery_status`.
4. **`email_provider_errors`**:
   - `id`, `provider_account_id`, `error_code`, `error_message`, `occurred_at`, `retry_status`.

---

## 4. Anti-Abuse & Multi-Recipient Accounting

- **To + CC + BCC Accounting**: Total recipients are counted accurately (`count(To) + count(CC) + count(BCC)`). An email with 1 To and 2 CCs charges 3 recipient credits.
- **Fail-Closed Protection**: If a provider returns a 429 rate limit or quota exhaustion, its queue is paused, pending messages are preserved with `QuotaPaused` status, and the user is alerted.
- **Zero Paid Fallback**: When free quotas are exhausted, JobPilot AI never triggers paid fallback APIs or automatic billing.

