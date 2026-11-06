# JobPilot AI — Permanent Application History & Deduplication

## 1. The Permanence Mandate
A core architectural requirement of JobPilot AI is that application history must **never** be lost or accidentally overwritten. Even when monthly archives migrate detailed records to Google Drive or cold storage, a compact deduplication index remains permanently active inside the database.

## 2. Canonical Identity & Fingerprinting

### SHA-256 Vacancy Fingerprint
Every vacancy imported or discovered is assigned a canonical 64-character hash:
```
hash = SHA256(
    normalize(company_name) + "::" +
    normalize(job_title) + "::" +
    normalize(location) + "::" +
    (external_requisition_id ?: normalize(canonical_apply_url))
)
```

### Normalization Pipeline
1. **Lowercase & Trim**: Strip leading/trailing whitespaces, punctuation, and Unicode oddities.
2. **Company Normalization**: Remove legal suffixes (`Inc.`, `LLC`, `Ltd`, `Technologies`, `Pvt Ltd`).
3. **Title Normalization**: Standardize synonyms (e.g. `Sr.` -> `Senior`, `PHP / Laravel` -> `Laravel / PHP`).
4. **Location Normalization**: Country / City standard format (`Bangalore, India` -> `bengaluru_in`).

## 3. Duplicate Detection Logic
- **Exact Fingerprint Match**: If `canonical_fingerprint` exists in `application_registry`:
  - Default action: Block duplicate application attempt.
  - Warning alert displayed to candidate with previous application date, CV version used, and result.
- **Cross-Platform Posting**: When the same vacancy appears on Adzuna and LinkedIn:
  - Fingerprint maps to the existing registry entry.
  - New discovery records `discovered_from` without triggering duplicate application.
- **Reopened Vacancy**: If employer reposts after 60+ days:
  - System requires explicit candidate override and confirmation to re-apply.
- **Discovered vs Applied Separation**:
  - `discovered_from`: The platform/feed where the vacancy was first crawled.
  - `applied_via`: The exact channel utilized for submission (e.g. Direct Email, Portal, Recruiter).

