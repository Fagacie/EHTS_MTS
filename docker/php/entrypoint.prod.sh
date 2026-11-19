#!/bin/sh
set -e

STORAGE_ROOT="${ETMS_STORAGE_ROOT:-/srv/etms/storage}"
BACKUP_ROOT="${ETMS_BACKUP_ROOT:-/srv/etms/backups}"

mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         "${STORAGE_ROOT}/materials" \
         "${STORAGE_ROOT}/videos" \
         "${STORAGE_ROOT}/recordings" \
         "${STORAGE_ROOT}/certificates" \
         "${BACKUP_ROOT}/storage" \
         "${BACKUP_ROOT}/database" \
         /var/www/html/bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data /var/www/html/storage "${STORAGE_ROOT}" "${BACKUP_ROOT}" /var/www/html/bootstrap/cache
    chmod -R 750 /var/www/html/storage "${STORAGE_ROOT}" "${BACKUP_ROOT}" /var/www/html/bootstrap/cache
fi

if [ "$1" = "php-fpm" ] && [ -f /var/www/html/artisan ]; then
    php /var/www/html/artisan storage:link --no-interaction || true
    php /var/www/html/artisan config:cache --no-interaction
    php /var/www/html/artisan route:cache --no-interaction
    php /var/www/html/artisan view:cache --no-interaction
    php /var/www/html/artisan event:cache --no-interaction
    php /var/www/html/artisan filament:cache-components --no-interaction || true
fi

exec "$@"
