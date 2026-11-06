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

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Clear caches so dynamic runtime env variables take effect
php /var/www/html/artisan config:clear || true
php /var/www/html/artisan view:clear || true

# Auto-run database migrations and seed default test user
echo "==> Running database migrations on Render..."
php /var/www/html/artisan migrate --force || true

echo "==> Running database seeders..."
php /var/www/html/artisan db:seed --force || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
