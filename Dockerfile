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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files from laravel directory
COPY laravel/ .

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN npm install && npm run build && rm -rf node_modules

COPY laravel/deploy/nginx-render.conf /etc/nginx/http.d/default.conf
COPY laravel/deploy/supervisord-render.conf /etc/supervisord.conf
COPY laravel/deploy/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80 10000

CMD ["/entrypoint.sh"]
