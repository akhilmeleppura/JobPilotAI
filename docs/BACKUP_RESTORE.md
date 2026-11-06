# JobPilot AI — Backup & Database Recovery Runbook V4.0

## 1. Automated Backup Architecture

JobPilot AI incorporates an automated backup subsystem (`BackupService`) providing automated snapshots, cryptographic integrity guarantees, and automated retention rotation:

- **Storage Target:** Local encrypted filesystem (`storage/app/backups/`) and Google Drive monthly archives.
- **Integrity Guarantee:** Every SQL dump is accompanied by an independently generated **SHA-256** checksum file.
- **Retention Policy:** The latest 7 daily snapshots and 4 monthly archives are retained; older archives are pruned automatically.

---

## 2. Triggering Backups

### Via Artisan CLI
```powershell
Set-Location c:\xampp\htdocs\JobPilotAI\laravel
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan system:backup
```

### Via Web Cockpit
Navigate to **System Health & Backups (`/system-health`)** and click **"Create Instant Backup"**.

---

## 3. Database Restoration Procedure

### Step 1: Engage Global Emergency Stop
Engage the kill switch to pause incoming requests and queue workers:
```powershell
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan jobpilot:emergency-stop --reason="Database disaster recovery"
```

### Step 2: Validate Checksum
Verify the SHA-256 hash before restoring:
```powershell
Get-FileHash -Path "storage/app/backups/backup_2026_11_01_xxxx.sql" -Algorithm SHA256
```

### Step 3: Restore Database Snapshot
```bash
# TiDB Cloud Serverless / MySQL 8.0:
mysql -u $DB_USERNAME -p -h $DB_HOST -P $DB_PORT $DB_DATABASE < storage/app/backups/backup_xxxx.sql

# Local Development (SQLite):
cp storage/app/backups/database_backup_latest.sqlite database/database.sqlite
```

### Step 4: Validate Schema & Resume Operations
```powershell
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan migrate:status
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan optimize:clear
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan jobpilot:emergency-stop --resume
```

