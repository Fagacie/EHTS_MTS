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
             /var/www/html/bootstrap/cache

    chown -R www-data:www-data /var/www/html/storage "${STORAGE_ROOT}" /var/www/html/bootstrap/cache
    chmod -R ug+rwx /var/www/html/storage "${STORAGE_ROOT}" /var/www/html/bootstrap/cache
fi

exec "$@"
