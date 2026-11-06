FROM php:8.4-fpm-alpine

# Install system dependencies, PHP extensions, and tools
RUN apk add --no-cache \
    nginx \
    curl \
    ca-certificates \
    zip \
    unzip \
    git \
    supervisor \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    sqlite-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring zip bcmath gd intl opcache

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install PHP production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend production assets
RUN npm install && npm run build && rm -rf node_modules

# Configure Nginx and Supervisor
COPY deploy/nginx-render.conf /etc/nginx/http.d/default.conf
COPY deploy/supervisord-render.conf /etc/supervisord.conf
COPY deploy/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80 10000

CMD ["/entrypoint.sh"]
