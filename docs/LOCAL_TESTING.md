# JobPilot AI — Local Testing Guide

## 1. Prerequisites & Environment Setup

JobPilot AI is architected as a modular monolith running on **PHP 8.4** and **Laravel 13**, coupled with SQLite (local development) and TiDB Cloud Serverless (MySQL 8.0 protocol).

### PHP 8.4 Binary Path
Always run CLI and Artisan commands using the designated PHP 8.4 binary:
```powershell
Set-Location c:\xampp\htdocs\JobPilotAI\laravel
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan --version
```

### Environment Configuration
The test environment uses a dedicated `.env.testing` configuration:
```env
APP_ENV=testing
APP_KEY=base64:XG8o9/B7r3Wv8g9xM8mZ2iQ9vV6d1l4e3r0y7t4u2i1=
FREE_RESOURCES_ONLY=true
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
```

---

## 2. Test Accounts & Synthetic Seed Data

The database seeder initializes synthetic personas with zero external PII:

| Account Type | Email Address | Password | Roles & Personas |
|---|---|---|---|
| **Primary Candidate** | `candidate@jobpilot.test` | `Password123!` | Laravel Developer, Full Stack Engineer, WordPress Specialist |
| **System Admin** | `admin@jobpilot.test` | `Password123!` | System Health, Quota Administration, Backup Management |
| **Unauthorized User** | `unauthorized@jobpilot.test` | `Password123!` | Isolated empty tenant for cross-tenant access testing |

To reseed the local development database:
```powershell
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan migrate:fresh --seed
```

---

## 3. Running Automated Tests

### Full Test Suite (130 Tests, 732 Assertions)
```powershell
Set-Location c:\xampp\htdocs\JobPilotAI\laravel
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan test
```

### Individual Test Suites
```powershell
# Comprehensive Integration & Browser Test
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan test tests/Feature/ComprehensiveBrowserSuiteTest.php

# Zero-Cost AI Gateway Test
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan test tests/Feature/AIGatewayTest.php

# Application Registry & Deduplication Test
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan test tests/Feature/ApplicationsTest.php

# LaTeX ATS Resume Studio Test
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan test tests/Feature/CVStudioTest.php

# Email Dispatch & Quota Engine Test
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan test tests/Feature/MailAutomationTest.php

# LinkedIn Optimizer & Studio Test
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan test tests/Feature/LinkedInOptimizerTest.php
```

---

## 4. Serving the Application Locally

To serve the application on `http://127.0.0.1:8000`:
```powershell
Set-Location c:\xampp\htdocs\JobPilotAI\laravel
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan serve --host=127.0.0.1 --port=8000
```

Verify application availability:
```powershell
Invoke-WebRequest -Uri "http://127.0.0.1:8000/login" -UseBasicParsing
```
Expected output: Status `200 OK`.

