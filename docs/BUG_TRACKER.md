# JobPilot AI — Bug Tracker & Quality Remediation Log

**Status:** All Identified Bugs Remediated & Verified with Regression Tests  
**Active Open Critical Bugs:** 0  
**Active Open High Bugs:** 0

---

## Remediation Log

### BUG-001: Method Name Hyphenation in LinkedIn Route
- **Module:** `LinkedIn`
- **Severity:** Medium
- **Symptom:** `BadMethodCallException: Method LinkedInController::transform-bullets does not exist` when requesting `/linkedin-optimizer/transform-bullets`.
- **Root Cause:** Route definition mapped to kebab-case string `'transform-bullets'` rather than camelCase method `'transformBullets'`.
- **Fix:** Corrected route action to `[LinkedInController::class, 'transformBullets']`.
- **Verification:** Regression test `LinkedInOptimizerTest::test_transform_bullets_converts_to_google_xyz_framework` passes cleanly.

---

### BUG-002: Composite Technical Skills Literal Substring Mismatch
- **Module:** `LinkedIn`
- **Severity:** Medium
- **Symptom:** Recruiter score audit returned lower score (54/100) because composite skill names such as `MySQL / TiDB Cloud` and `Redis & Caching` were matched literally against candidate copy.
- **Root Cause:** Raw keyword tokens contained slashes and ampersands, preventing matching of individual stack mentions like `MySQL` or `Redis`.
- **Fix:** Split composite keyword strings on `/`, `&`, and `,`, and stripped version numbers in `extractTargetKeywords`.
- **Verification:** `LinkedInOptimizerTest::test_audit_calculates_recruiter_score_and_identifies_missing_keywords` now asserts score > 75 and passes.

---

### BUG-003: HTML Entity Escaping in Blade Page Titles
- **Module:** `ContentStudio` & `Monitoring`
- **Severity:** Low
- **Symptom:** Tests asserting `assertSee('LinkedIn Content & Image Studio')` and `assertSee('System Health, Security & Backups')` failed due to Blade rendering `&amp;`.
- **Root Cause:** Standard Blade output encodes ampersands into HTML entities.
- **Fix:** Passed `false` to `assertSee` to allow unescaped checking or asserted clean text segments.
- **Verification:** Both `ContentStudioTest` and `SystemMonitoringTest` pass 100%.

---

### BUG-004: In-Memory SQLite Touch Attempt During Testing
- **Module:** `Monitoring`
- **Severity:** Medium
- **Symptom:** `touch(): Unable to create file :memory:` during `BackupService::createBackup` in test environment.
- **Root Cause:** In PHPUnit with `RefreshDatabase`, `database.connections.sqlite.database` is `:memory:`, which cannot be copied as a physical disk file.
- **Fix:** Added check in `BackupService::createBackup` and `SystemMonitoringService::checkDatabase` for `:memory:`, generating in-memory table snapshots instead.
- **Verification:** `SystemMonitoringTest` passes 9/9 tests including backup creation and rotation.

---

### BUG-005: Encrypted Secrets Key Mismatch in Credential Updates
- **Module:** `LinkedIn` (Publishing)
- **Severity:** Medium
- **Symptom:** Disconnect method did not clear encrypted token because `updateIntegration` retained existing values when empty string was passed.
- **Root Cause:** `CredentialVaultService::updateIntegration` merges non-null secrets, skipping empty strings.
- **Fix:** Added explicit `$cred->setSecrets([])` and `$cred->is_enabled = false` in `LinkedInPublisherService::disconnect`.
- **Verification:** `LinkedInPublishingTest::test_user_can_disconnect_linkedin_account` passes cleanly.

---

### BUG-006: Unset `normalized_name` Constraint on Direct Company Instantiation
- **Module:** `Jobs`
- **Severity:** Medium
- **Symptom:** `SQLSTATE[23000]: NOT NULL constraint failed: companies.normalized_name` when instantiating `Company` without explicit `normalized_name`.
- **Root Cause:** Schema requires `normalized_name` for indexed lookups, but model lacked an Eloquent lifecycle hook to automatically normalize from `name`.
- **Fix:** Added `booted()` creating callback in `Company` model: `if (empty($company->normalized_name)) $company->normalized_name = strtolower(trim($company->name));`.
- **Verification:** `ComprehensiveBrowserSuiteTest` creates companies dynamically without errors.

---

### BUG-007: Missing Automatic Generation of `vacancy_fingerprint` in Eloquent Lifecycle
- **Module:** `Jobs`
- **Severity:** Medium
- **Symptom:** `SQLSTATE[23000]: NOT NULL constraint failed: job_vacancies.vacancy_fingerprint` when creating `JobVacancy` models directly.
- **Root Cause:** `JobVacancy` implemented `FingerprintableInterface` and `generateFingerprint()`, but did not hook it into `booted()` creating event.
- **Fix:** Added `booted()` creating lifecycle hook in `JobVacancy` model to auto-compute `vacancy_fingerprint` if not supplied.
- **Verification:** `ComprehensiveBrowserSuiteTest` successfully executes synthetic scale queries across 50+ vacancies and applications.
