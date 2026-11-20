# Stage 10 Multi-Target Dockerfile (Development + Immutable Production Runtime)

FROM php:8.3-fpm-bookworm AS base

ARG DEBIAN_FRONTEND=noninteractive

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    pcntl \
    zip \
    intl \
    redis

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    procps \
    libfcgi-bin \
    ffmpeg \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-etms.ini
COPY docker/php/zz-etms-fpm.conf /usr/local/etc/php-fpm.d/zz-etms-fpm.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ------------------------------------------------------------------------------
# Frontend Build Stage (Node 22 — compiled assets only)
# ------------------------------------------------------------------------------
FROM node:22-alpine AS node_assets

WORKDIR /var/www/html
COPY package.json package-lock.json vite.config.js tsconfig.json components.json ./
COPY resources ./resources
COPY app ./app
RUN npm ci && npm run build

# ------------------------------------------------------------------------------
# Immutable Production Runtime Target
# ------------------------------------------------------------------------------
FROM base AS production

COPY . /var/www/html
COPY --from=node_assets /var/www/html/public/build /var/www/html/public/build
COPY docker/php/php.prod.ini /usr/local/etc/php/conf.d/99-etms-prod.ini
COPY docker/php/entrypoint.prod.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --classmap-authoritative \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs \
                storage/app/public storage/app/private/etms/materials storage/app/private/etms/videos \
                storage/app/private/etms/recordings storage/app/private/etms/certificates storage/backups bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# ------------------------------------------------------------------------------
# Local Development Target (default when building docker-compose.yml)
# ------------------------------------------------------------------------------
FROM base AS development
