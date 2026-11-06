# JobPilot AI — Rollback & Disaster Recovery Runbook

## 1. Automated Backups & Snapshots

JobPilot AI includes an automated backup engine (`BackupService`) accessible via CLI, web dashboard, and automated cron scheduler.

### Manual Backup Generation
```powershell
Set-Location c:\xampp\htdocs\JobPilotAI\laravel
& "C:\xampp\htdocs\JobPilotAI\bin\php84\php.exe" artisan system:backup
```
- **Output Directory:** `storage/app/backups/`
- **Integrity Validation:** Every archive includes a SHA-256 integrity checksum alongside the SQL schema and data dump.
- **Rotation Policy:** Automated pruning keeps the latest 7 daily snapshots and 4 monthly archives.

---

## 2. Disaster Recovery & Rollback Scenarios

### Scenario A: Corrupted Database / Bad Migration
1. Put application into maintenance mode:
   ```bash
   php artisan down --secret="jobpilot-emergency-restore"
   ```
2. Locate the most recent verified backup file:
   ```bash
   ls -la storage/app/backups/
   ```
3. Restore database snapshot:
   ```bash
   # For MySQL / TiDB Cloud
   mysql -u $DB_USERNAME -p -h $DB_HOST -P $DB_PORT $DB_DATABASE < storage/app/backups/backup_2026_11_01_xxxx.sql

   # For SQLite Development
   cp storage/app/backups/database_backup_latest.sqlite database/database.sqlite
   ```
4. Verify schema status:
   ```bash
   php artisan migrate:status
   ```
5. Clear caches and resume application:
   ```bash
   php artisan optimize:clear
   php artisan up
   ```

---

### Scenario B: Erroneous Code Release
1. Revert to the prior Git commit or release tag:
   ```bash
   git log -n 5 --oneline
   git checkout <LAST_KNOWN_STABLE_COMMIT_HASH>
   ```
2. Reinstall Composer dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Re-run configuration caching:
   ```bash
   php artisan optimize:clear
   php artisan optimize
   ```
4. Execute smoke tests:
   ```bash
   ./scripts/smoke_test.sh
   ```

