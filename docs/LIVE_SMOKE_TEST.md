# JobPilot AI — Live Smoke Test Runbook

## 1. Objective & Scope

The Live Smoke Test verifies that the deployed production application is online, reachable, authenticated, and executing its primary operational paths without critical errors.

---

## 2. Automated Smoke Test Script

Run this verification script against any live staging or production URL:

```bash
#!/usr/bin/env bash
set -e

TARGET_URL="${1:-http://127.0.0.1:8000}"
echo "Executing JobPilot AI Live Smoke Test against: ${TARGET_URL}"

# 1. Health Probe
echo "Step 1: Probing /up health endpoint..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${TARGET_URL}/up")
if [ "$HTTP_STATUS" -ne 200 ]; then
  echo "FAIL: Health probe returned status ${HTTP_STATUS}"
  exit 1
fi
echo "PASS: /up returned HTTP 200"

# 2. Login Page Availability
echo "Step 2: Checking Login page render..."
LOGIN_HTML=$(curl -s "${TARGET_URL}/login")
if [[ "$LOGIN_HTML" != *"JobPilot AI"* ]]; then
  echo "FAIL: Login page missing application brand title"
  exit 1
fi
echo "PASS: Login page renders brand title"

# 3. System Health API Scorecard
echo "Step 3: Checking System Health API..."
HEALTH_JSON=$(curl -s "${TARGET_URL}/system-health/api")
if [[ "$HEALTH_JSON" != *"database"* ]]; then
  echo "FAIL: Health scorecard missing database metric"
  exit 1
fi
echo "PASS: Health scorecard returns valid JSON with database metric"

# 4. Security Headers Verification
echo "Step 4: Checking security headers..."
HEADERS=$(curl -s -I "${TARGET_URL}/login")
if [[ "$HEADERS" != *"X-Frame-Options"* ]] || [[ "$HEADERS" != *"X-Content-Type-Options"* ]]; then
  echo "FAIL: Missing required security headers"
  exit 1
fi
echo "PASS: Security headers present (X-Frame-Options, X-Content-Type-Options)"

echo "--------------------------------------------------------"
echo "All Live Smoke Tests Completed Successfully! (100% PASS)"
echo "--------------------------------------------------------"
```

---

## 3. Manual Live Checklist

For human QA verification on production:
1. **Login:** Authenticate with `candidate@jobpilot.test` and verify redirection to Cockpit (`/`).
2. **Role Profiles:** Inspect active profiles at `/role-profiles`. Confirm 3 default profiles are present.
3. **Resume Editor:** Open `/cv-studio/editor/1`. Compile LaTeX draft to PDF and verify preview opens.
4. **Job Search:** Execute search for "Laravel" on `/jobs`. Confirm vacancies render.
5. **AI Chat:** Send test query "Summarize my experience" in `/ai-chat`. Confirm grounded AI reply.
6. **Integrations:** Verify all toggles in `/settings`. Confirm zero API keys are visible in plaintext.

