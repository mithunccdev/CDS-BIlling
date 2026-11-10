# ==============================================================================
# Stage 1: Build Frontend Assets (Vite, Tailwind, Bootstrap, JS)
# ==============================================================================
FROM node:20-alpine AS frontend-builder
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# ==============================================================================
# Stage 2: Install PHP Composer Dependencies
# ==============================================================================
FROM composer:2 AS composer-builder
WORKDIR /app

COPY composer*.json ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ==============================================================================
# Stage 3: Final Production Image (PHP 8.3-FPM + Nginx + Supervisor)
# ==============================================================================
FROM php:8.3-fpm-alpine

# Install essential system packages
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    mysql-client

# Install PHP extensions using docker-php-extension-installer
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    bcmath \
    curl \
    xml \
    zip \
    opcache \
    pcntl \
    intl \
    gd \
    redis

# Prepare directories and Nginx/Supervisor configs
RUN mkdir -p /var/log/supervisor /run/nginx /var/www/html

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copy application source code
COPY . /var/www/html

# Copy vendor dependencies from Stage 2
COPY --from=composer-builder /app/vendor /var/www/html/vendor

# Copy compiled Vite assets from Stage 1
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Setup entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Configure permissions for storage & cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

