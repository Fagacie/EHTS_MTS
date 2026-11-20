#!/bin/sh
set -e

STORAGE_ROOT="${ETMS_STORAGE_ROOT:-/var/www/html/storage/app/private/etms}"

if [ "$(id -u)" = "0" ] && [ -d /var/www/html/storage ]; then
    mkdir -p /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/framework/cache \
             /var/www/html/storage/logs \
             /var/www/html/storage/app/public \
             "${STORAGE_ROOT}/materials" \
             "${STORAGE_ROOT}/videos" \
             "${STORAGE_ROOT}/recordings" \
             "${STORAGE_ROOT}/certificates" \
             /var/www/html/storage/backups \
             /var/www/html/bootstrap/cache \
             /var/www/html/vendor

    chown -R www-data:www-data \
        /var/www/html/storage/framework \
        /var/www/html/storage/logs \
        /var/www/html/storage/backups \
        /var/www/html/storage/app/public \
        "${STORAGE_ROOT}" \
        /var/www/html/bootstrap/cache

    chmod -R ug+rwx \
        /var/www/html/storage/framework \
        /var/www/html/storage/logs \
        /var/www/html/storage/backups \
        /var/www/html/storage/app/public \
        "${STORAGE_ROOT}" \
        /var/www/html/bootstrap/cache

    # Sync PHP dependencies into the native container volume whenever empty or composer.lock changes.
    VENDOR_SYNCED=0
    if [ -f /var/www/html/composer.lock ]; then
        LOCK_HASH="$(sha256sum /var/www/html/composer.lock | awk '{print $1}')"
        INSTALLED_HASH=""
        if [ -f /var/www/html/vendor/.composer.lock.sha256 ]; then
            INSTALLED_HASH="$(cat /var/www/html/vendor/.composer.lock.sha256 2>/dev/null || true)"
        fi

        if [ ! -f /var/www/html/vendor/autoload.php ] || [ "${LOCK_HASH}" != "${INSTALLED_HASH}" ]; then
            echo "[entrypoint] Syncing Composer dependencies into native container volume..."
            chown -R www-data:www-data /var/www/html/vendor
            su -s /bin/sh www-data -c "cd /var/www/html && COMPOSER_CACHE_DIR=/tmp/composer-cache composer install --prefer-dist --no-interaction --optimize-autoloader --no-scripts && php artisan package:discover --ansi --no-interaction"
            printf '%s' "${LOCK_HASH}" > /var/www/html/vendor/.composer.lock.sha256
            chown www-data:www-data /var/www/html/vendor/.composer.lock.sha256
            VENDOR_SYNCED=1
        fi
    fi

    # Never leave config cache in local dev so phpunit.xml environment overrides remain intact.
    rm -f /var/www/html/bootstrap/cache/config.php

    if [ -f /var/www/html/artisan ] && [ -f /var/www/html/vendor/autoload.php ]; then
        if [ "${VENDOR_SYNCED}" = "1" ] \
            || [ ! -d /var/www/html/bootstrap/cache/filament/panels ] \
            || [ ! -f /var/www/html/bootstrap/cache/blade-icons.php ]; then
            echo "[entrypoint] Warming Filament component & Blade icon caches..."
            su -s /bin/sh www-data -c "cd /var/www/html && php artisan filament:cache-components --no-interaction && php artisan icons:cache --no-interaction"
        fi
    fi
fi

exec "$@"
