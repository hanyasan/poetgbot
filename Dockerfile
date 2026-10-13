# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

FROM dunglas/frankenphp:1-php8.4-alpine AS base
RUN install-php-extensions pdo_pgsql pgsql redis opcache pcntl
COPY docker/Caddyfile /etc/caddy/Caddyfile
WORKDIR /app

# Production image: code and dependencies baked in.
FROM base AS prod
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data

# Development image: code is bind-mounted, dev dependencies installed at runtime.
FROM base AS dev
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
