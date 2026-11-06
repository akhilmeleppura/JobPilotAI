#!/bin/sh
set -e

# Dynamically set port in Nginx config if Render passes PORT env variable
PORT="${PORT:-80}"
sed -i "s/listen 80;/listen ${PORT};/g" /etc/nginx/http.d/default.conf

# Ensure storage directories exist with proper write permissions
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache \
         /var/www/html/database

touch /var/www/html/database/database.sqlite 2>/dev/null || true
touch /var/www/html/test 2>/dev/null || true

# If APP_KEY is empty or not base64, ensure a valid key is set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "null" ]; then
    export APP_KEY="base64:8XzhuAMYY0PBPzTRZKLbso+60kZ7tC3p7C1QZsn81z0="
fi

# Auto-detect TiDB connection if DB_HOST contains tidb
if [ -n "$DB_HOST" ]; then
    if echo "$DB_HOST" | grep -q "tidb"; then
        export DB_CONNECTION="tidb"
    fi
fi

# Write environment variables to .env so PHP-FPM and Laravel web workers have full access
env | grep -E '^(APP_|DB_|TIDB_|MYSQL_|SESSION_|CACHE_|QUEUE_|MAIL_|FREE_|ALLOW_|EMERGENCY_|LOG_)' > /var/www/html/.env

if ! grep -q "APP_KEY=" /var/www/html/.env; then
    echo "APP_KEY=base64:8XzhuAMYY0PBPzTRZKLbso+60kZ7tC3p7C1QZsn81z0=" >> /var/www/html/.env
fi

if [ -n "$DB_HOST" ] && ! grep -q "DB_CONNECTION=" /var/www/html/.env; then
    echo "DB_CONNECTION=tidb" >> /var/www/html/.env
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/.env /var/www/html/test
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod 666 /var/www/html/.env /var/www/html/test 2>/dev/null || true

# Configure PHP-FPM to preserve environment variables
echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf 2>/dev/null || true

# Clear cached config so runtime env variables take effect
php /var/www/html/artisan config:clear || true
php /var/www/html/artisan view:clear || true

# Auto-run database migrations and seed default test user
echo "==> Running database migrations on Render..."
php /var/www/html/artisan migrate --force || true

echo "==> Running database seeders..."
php /var/www/html/artisan db:seed --force || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
