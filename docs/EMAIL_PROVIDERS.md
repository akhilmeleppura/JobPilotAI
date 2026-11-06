# Email Providers & Acceptable-Use Policy Verification

JobPilot AI integrates with free email platforms to deliver job applications and recruiter outreach. Every provider is subject to strict acceptable-use policy verification.

---

## 1. Acceptable-Use Policy Statuses

- **`VERIFIED_ALLOWED`**:
  The provider's documented terms and conditions permit direct, candidate-to-recruiter 1-to-1 job applications (e.g., Gmail API, Google Apps Script, Outlook Graph).
- **`REQUIRES_REVIEW`**:
  The platform permits email sending, but specific restrictions (such as port blocking, web-only sending, or custom domain SPF/DKIM verification) apply (e.g., Zoho Mail Free).
- **`PROHIBITED`**:
  The platform's acceptable-use policy mandates opt-in subscriptions, marketing consent, or unsubscribe footers, explicitly forbidding unsolicited direct HR outreach (e.g., Mailjet, SMTP2GO). Even if they offer free monthly emails, they are blocked for job application dispatch.
- **`UNKNOWN`**:
  Unverified provider terms. Blocked by default under strict fail-closed free-only mode.

---

## 2. Google Ecosystem Rules

### Gmail API
- **Protocol**: OAuth 2.0 with restricted `gmail.send` scope.
- **Guidance**: ~500 recipients/day guidance for consumer web/API accounts.
- **Remaining Quota Counter**: Google does not expose a live counter via Gmail API; usage is tracked locally.
- **Quota Source**: `LOCALLY_MEASURED`.

### Google Apps Script
- **Protocol**: REST Execution or Webhook.
- **Quota Detection**: Queries `MailApp.getRemainingDailyQuota()`.
- **Remaining Quota Counter**: Actual live integer reported directly by Google servers.
- **Quota Source**: `PROVIDER_REPORTED`.
- **Shared Account Warning**: If connected using the same Google email address as Gmail API, the underlying recipient pool is shared. Allowances must not be summed together.

---

## 3. Microsoft Outlook Graph
- **Protocol**: OAuth 2.0 with Microsoft Graph `Mail.Send` permission.
- **Guidance**: ~300 recipients/day guidance for free personal Microsoft accounts.
- **Remaining Quota Counter**: Not exposed for consumer accounts.
- **Quota Source**: `LOCALLY_MEASURED`.
- **Policy Status**: `VERIFIED_ALLOWED`.
