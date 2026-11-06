# JobPilot AI — Hosting & Infrastructure Guide

## 1. Primary Target: Oracle Cloud Always Free
- **Architecture**: Ampere A1 Compute (ARM64), 4 OCPUs, 24GB RAM.
- **OS**: Ubuntu 24.04 LTS.
- **Web Server**: Nginx with HTTP/2 and TLS (Let's Encrypt / Certbot).
- **PHP**: PHP-FPM 8.4+ with OPcache enabled.
- **Database**: TiDB Cloud Starter (Free Tier) over TLS.
- **Process Management**:
  - `systemd` or `supervisor` managing Laravel Queue Workers:
    - `php artisan queue:work --queue=high,default,emails,archives --sleep=3 --tries=3`
  - Linux `crontab` managing Laravel Scheduler:
    - `* * * * * cd /var/www/jobpilot/laravel && php artisan schedule:run >> /dev/null 2>&1`

## 2. Local Windows Development Environment
- **Server**: PHP built-in server or local Nginx/Apache in XAMPP (`c:\xampp\htdocs\JobPilotAI\laravel`).
- **PHP**: PHP 8.4 CLI configured with OpenSSL, cURL, PDO MySQL, Zip, Fileinfo, Mbstring.
- **Database**: TiDB Cloud Starter connection or local MySQL 8.0 fallback.

## 3. Alternative Fallback Hosts
If Oracle Cloud free tier regions are capacity-constrained:
- Render.com / Fly.io for web worker + external managed Redis.
- Free VPS tiers (e.g. Google Cloud e2-micro, AWS Free Tier EC2).

