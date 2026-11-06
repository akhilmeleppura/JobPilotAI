# JobPilot AI — Security Architecture & Guidelines

## 1. Core Threat Model & Mitigations
JobPilot AI acts as a personal career cockpit handling sensitive data (CVs, credentials, contact details, applications). It enforces defense-in-depth across the following vectors:

### A. Free-First Resource Enforcement
- **Policy Flag**: `FREE_RESOURCES_ONLY=true`.
- **Pre-flight Check**: Before dispatching any AI, image generation, or job scraping request, the provider adapter queries configured pricing. Unknown or non-zero pricing triggers an immediate abort and warning log.
- **Fail-Safe**: Never silently switch to a paid tier or charge customer cards/credits.

### B. Credential Vault & Encryption
- All third-party secrets (Google AI Studio keys, Groq API keys, Gmail OAuth refresh tokens, TiDB credentials) are encrypted using AES-256-GCM via Laravel's `Crypt` facade before storage in `settings` or `email_accounts`.
- Stored keys are **never** rendered into client-side JavaScript or HTML inputs.

### C. Server-Side Request Forgery (SSRF) Protection
- When fetching external URLs for job vacancy analysis or company research:
  - Block requests targeting private CIDR blocks (`127.0.0.0/8`, `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `169.254.0.0/16`).
  - Block internal hostnames (`localhost`, `*.internal`, `*.local`).
  - Disable redirect following beyond 3 hops and enforce timeout ceilings (5s max).

### D. LaTeX Compilation Security
- Compilations are executed with `-no-shell-escape` to prohibit arbitrary binary execution.
- TeX operations execute inside an isolated sandbox directory with restricted CPU (max 10s runtime) and memory limits (256MB).
- Uploaded `.tex` documents are scanned for malicious macro inclusions before invoking `pdflatex`.

### E. Untrusted Document Ingestion & AI Prompt Defense
- Job descriptions, uploaded PDFs, DOCX files, and spreadsheet cells are strictly sanitized as untrusted data.
- System prompts are architected with delimited boundaries to prevent prompt-injection jailbreaks from overriding system instructions.

### F. Multi-User Ownership & Isolation
- Every database query for user assets (resumes, applications, email accounts) is constrained by `user_id` and verified via Laravel Authorization Policies.

