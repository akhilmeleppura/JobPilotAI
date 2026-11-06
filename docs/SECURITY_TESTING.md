# JobPilot AI — Security & Penetration Testing Guide

## 1. Threat Model & Security Posture

JobPilot AI enforces strict zero-trust principles across multi-tenant boundaries, credential storage, external API dispatch, and AI gateway access.

### Core Security Principles:
1. **Multi-Tenant Data Isolation**: All database queries are scoped to the authenticated `user_id`. Cross-tenant record enumeration returns HTTP 403 or 404.
2. **Encrypted Credential Vault**: API keys, OAuth tokens, and client secrets are encrypted using AES-256-CBC via Laravel's `Crypt` facade before disk persistence.
3. **Zero-Spend Policy Enforcement**: `FREE_RESOURCES_ONLY=true` actively halts requests targeting paid models or unknown billing tiers.
4. **LaTeX Compiler Sandboxing**: The LaTeX compiler executes with `-no-shell-escape` and intercepts malicious directives (`\write18`, `\input`, `\openout`).
5. **CSRF & Security Headers**: All state-modifying requests require CSRF tokens. All HTTP responses include `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, and strict CSP policies.

---

## 2. Security Test Scenarios & Verifications

### 2.1 Cross-Tenant Access Enforcement
- **Test:** Authenticated `User B` attempts to read applications or content drafts owned by `User A`.
- **Result:** Pass (`ComprehensiveBrowserSuiteTest::test_multi_user_data_isolation_and_cross_tenant_access_control`).
- **Assertion:** `assertDontSee('Confidential Corp')` and `assertDontSee('Private Draft Strategy')`.

### 2.2 LaTeX Injection Sandboxing
- **Test:** User inputs malicious TeX code: `\write18{cat /etc/passwd}` or `\input{config.php}`.
- **Result:** Pass (`CVStudioTest::test_latex_compiler_service_sanitizes_dangerous_macro_commands`).
- **Mechanism:** `LatexCompilerService::sanitizeSource()` strips dangerous command invocations and replaces them with safe comments.

### 2.3 Credential Vault Protection
- **Test:** Inspect raw database rows for `integration_credentials.encrypted_secrets`.
- **Result:** Pass. Plaintext tokens never appear in database tables or logs.
- **Verification:** Only authenticated owners can retrieve decrypted credentials into memory during active dispatch.

### 2.4 Human-In-The-Loop Verification
- **Test:** Trigger automated outreach or LinkedIn publishing.
- **Result:** Pass (`MailAutomationTest` and `LinkedInPublishingTest`).
- **Safety Rule:** No external emails or LinkedIn posts are dispatched without explicit human sign-off in the review queue.

