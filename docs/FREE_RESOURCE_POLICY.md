# JobPilot AI — Absolute Free-Cost Policy V4.0

## 1. Core Mandate & Philosophy

JobPilot AI enforces an uncompromising **Absolute Free-Cost Policy**:
- **Zero Paid Subscriptions**
- **Zero Unexpected API Charges**
- **Zero Automatic Upgrades**
- **Zero Paid Fallback Requests**

The system operates strictly under a **fail-closed** paradigm: If a service or model cannot fulfill an operation within its verified free tier, the system immediately pauses execution, preserves all candidate data, and provides clear user notification.

---

## 2. Central Configuration Flags

Configured in `laravel/.env` and enforced via `AIGatewayService` and `CredentialVaultService`:

```env
# JobPilot AI — Absolute Free-Cost Policy V4.0
FREE_MODE=true
FREE_RESOURCES_ONLY=true
ALLOW_PAID_APIS=false
ALLOW_AUTO_UPGRADES=false
ALLOW_BILLING=false
```

### Pre-Request Verification Pipeline
Before dispatching any external API request, the gateway executes a 7-step security check:
1. **Identify Configured Provider:** Checks against registered free adapters.
2. **Identify Exact Model or Endpoint:** Verifies exact model string (e.g. `gemini-2.5-flash`, `llama-3.3-70b-versatile`).
3. **Verify Pricing Classification:** Rejects known paid endpoints (`gpt-4o`, `claude-3-5-sonnet`, `o1`).
4. **Account Eligibility Check:** Ensures selected provider is operating under developer/free tier.
5. **Usage Limit Inspection:** Checks daily and RPM quota caps.
6. **Billing Verification:** Confirms billing is disabled on the provider account.
7. **Fail-Closed Gate:** Aborts call with `PaidResourceBlockedException` if pricing is paid or unknown.

---

## 3. Strict Quota Reporting Standard

JobPilot AI never fabricates or estimates quota numbers. Every integration monitoring card displays:
- **Free Eligibility:** Verified / Standby / Blocked
- **Verified Pricing Date:** Exact timestamp of last documentation review
- **Allowance:** Actual provider limit (e.g. `15 RPM / 1M tokens/day`)
- **Measured Usage:** Recorded local requests & tokens consumed
- **Remaining Usage:** Displayed as `UNKNOWN` if provider API does not return real-time balances.
- **Reset Schedule:** Provider reset time where available.

---

## 4. Anti-Abuse & Integrity Guarantees

JobPilot AI strictly prohibits:
- Account rotation to evade quotas
- Multi-key rotation to circumvent rate limits
- Paid fallback chains when free quota expires
- Automatic billing upgrades or hidden micro-transactions



---

## 5. Dynamic Free Email Allowance Standard

- **Elimination of Arbitrary 200 Daily Ceiling**:
  JobPilot AI does not enforce arbitrary application-side rate limits. Universal 200 emails/day ceilings are removed from models, migrations, and UI components.
- **Accurate Quota Source Attribution**:
  - `PROVIDER_REPORTED`: Live readings from provider APIs (such as `MailApp.getRemainingDailyQuota()`).
  - `DOCUMENTED_PLAN`: Official free-tier terms.
  - `LOCALLY_MEASURED`: Internal recipient counting.
  - `UNKNOWN`: The exact remaining count is presented as "Exact remaining quota unavailable". No numbers are fabricated.
- **Strict Acceptable-Use Policy Verification**:
  Transactional platforms (such as Mailjet or SMTP2GO) that prohibit cold 1-to-1 recruitment applications without prior recipient opt-in are classified as `PROHIBITED` and blocked from dispatching job applications.
