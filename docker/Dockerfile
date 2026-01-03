# Multi-stage Dockerfile for Laravel 12 Production
# Optimized for Coolify deployment with external PostgreSQL & Redis

# ============================================
# Stage 1: Composer Dependencies
# ============================================
FROM composer:2 AS composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install production dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

# ============================================
# Stage 2: Build Assets (Node.js)
# ============================================
FROM node:20-alpine AS assets

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy vendor from composer stage (needed for Flux CSS)
COPY --from=composer /app/vendor ./vendor

# Copy source files needed for build
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

# Build assets
RUN npm run build

# ============================================
# Stage 3: Production Runtime
# ============================================
FROM php:8.4-fpm-alpine

LABEL maintainer="OpsMind Team"

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    bash \
    curl

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
        pcntl

# Install Redis extension via PECL
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Set working directory
WORKDIR /var/www/html

# Copy composer dependencies
COPY --from=composer /app/vendor ./vendor

# Copy built assets
COPY --from=assets /app/public/build ./public/build

# Copy application code
COPY . .

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/laravel.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint.sh

# Create necessary directories for Nginx and Supervisor
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /run/nginx

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

# Use supervisor to manage processes
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
