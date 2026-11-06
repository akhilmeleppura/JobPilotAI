#!/usr/bin/env bash
# ==============================================================================
# JobPilot AI — Oracle Cloud Always Free VM (ARM64 Ampere A1) Setup Script
# Target: Ubuntu 24.04 LTS (4 OCPU, 24GB RAM, Always Free Tier)
# ==============================================================================

set -e

echo "=== [1/7] Updating System Packages & Installing Core Utilities ==="
sudo apt-get update && sudo apt-get upgrade -y
sudo apt-get install -y software-properties-common curl git unzip zip ufw supervisor certbot python3-certbot-nginx

echo "=== [2/7] Installing PHP 8.4-FPM & Extensions ==="
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update
sudo apt-get install -y \
    php8.4-fpm \
    php8.4-cli \
    php8.4-curl \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-zip \
    php8.4-mysql \
    php8.4-sqlite3 \
    php8.4-gd \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-opcache \
    php8.4-redis

echo "=== [3/7] Installing Composer ==="
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

echo "=== [4/7] Configuring Nginx Web Server ==="
sudo apt-get install -y nginx
sudo cp deploy/nginx-jobpilot.conf /etc/nginx/sites-available/jobpilot
sudo ln -sf /etc/nginx/sites-available/jobpilot /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

echo "=== [5/7] Configuring Directory Permissions & Storage Link ==="
sudo chown -R www-data:www-data /var/www/jobpilot/laravel/storage
sudo chown -R www-data:www-data /var/www/jobpilot/laravel/bootstrap/cache
sudo chmod -R 775 /var/www/jobpilot/laravel/storage
sudo chmod -R 775 /var/www/jobpilot/laravel/bootstrap/cache

cd /var/www/jobpilot/laravel
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

echo "=== [6/7] Configuring Supervisor for Queue Workers ==="
sudo cp /var/www/jobpilot/deploy/jobpilot-worker.conf /etc/supervisor/conf.d/jobpilot-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start jobpilot-worker:*

echo "=== [7/7] Configuring Linux Crontab (Scheduler & Backups) ==="
CRON_JOB="* * * * * cd /var/www/jobpilot/laravel && php artisan schedule:run >> /dev/null 2>&1"
BACKUP_CRON="0 2 * * * cd /var/www/jobpilot/laravel && php artisan jobpilot:backup >> /dev/null 2>&1"

(crontab -l 2>/dev/null | grep -Fv "schedule:run"; echo "$CRON_JOB") | crontab -
(crontab -l 2>/dev/null | grep -Fv "jobpilot:backup"; echo "$BACKUP_CRON") | crontab -

echo "=============================================================================="
echo "✓ JobPilot AI production deployment configured successfully on Oracle Cloud!"
echo "Run: sudo certbot --nginx -d yourdomain.com to finalize Let's Encrypt SSL."
echo "=============================================================================="

