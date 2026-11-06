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
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisord.conf
