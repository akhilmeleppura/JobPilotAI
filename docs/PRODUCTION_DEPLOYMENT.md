# JobPilot AI — Production Deployment Guide

## 1. Deployment Topology & Free-Tier Cloud Stack

JobPilot AI is architected to operate on 100% free-tier cloud infrastructure without recurring subscription fees:

| Component | Target Provider | Free Tier Allocation | Protocol / Specification |
|---|---|---|---|
| **Web & App Runtime** | Render / Fly.io / Koyeb | 512MB RAM, shared vCPU | PHP 8.4 + Nginx / FrankenPHP |
| **Relational Database** | TiDB Cloud Serverless | 5 GiB Storage, 50M Request Units/mo | MySQL 8.0 Protocol (TLS 1.3) |
| **Object & File Storage** | Cloudflare R2 | 10 GB Storage, 1M Class A ops | S3-Compatible API (zero egress fee) |
| **Worker & Scheduler** | Supercronic / Embedded | 1 worker process | `queue:work --stop-when-empty` |
| **External Mail** | Gmail API (OAuth2) | 500 emails / day | Zero cost (Google Cloud Free Project) |
| **AI Inference** | Google Gemini 2.5 Flash / Groq | Free Dev Quotas | Zero-Spend Gateway Fallback |

---

## 2. Pre-Deployment Readiness Checklist

Before pushing to production, verify:
- [x] Application key generated (`php artisan key:generate`).
- [x] `FREE_RESOURCES_ONLY=true` active in environment configuration.
- [x] TiDB Cloud database reachable over TLS (`MYSQL_ATTR_SSL_CA`).
- [x] All 24 database migrations migrated (`php artisan migrate --force`).
- [x] Seed data applied with custom admin password (`php artisan db:seed`).
- [x] Route, config, and view caches compiled:
  ```powershell
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [x] Automated test suite passes 100% (130 tests, 732 assertions).

---

## 3. Environment Variables Template (`.env.production`)

```env
APP_NAME="JobPilot AI"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://jobpilot.yourdomain.com

FREE_RESOURCES_ONLY=true

# TiDB Cloud Serverless Database
DB_CONNECTION=mysql
DB_HOST=gateway01.us-east-1.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=jobpilot_ai
DB_USERNAME=your_tidb_username
DB_PASSWORD=your_tidb_password
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

# Session, Cache, Queue
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail Settings
MAIL_MAILER=log

# AI Gateway Default Key
GEMINI_API_KEY=your_free_gemini_api_key
GROQ_API_KEY=your_free_groq_api_key
```

---

## 4. Production Release Runbook

### Step 1: Clone & Install Dependencies
```bash
git clone https://github.com/your-org/JobPilotAI.git .
cd laravel
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### Step 2: Database Migration & Seeding
```bash
php artisan migrate --force
php artisan db:seed --force
```

### Step 3: Optimization Caching
```bash
php artisan optimize:clear
php artisan optimize
```

### Step 4: Health Verification
```bash
curl -I https://jobpilot.yourdomain.com/up
curl -s https://jobpilot.yourdomain.com/system-health/api | jq .
```

