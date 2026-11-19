#!/bin/sh
# Standalone ETMS Disaster Recovery Restore Script
set -eu

if [ "$#" -lt 2 ]; then
    echo "Usage: $0 <path-to-db-dump.sql.gz> <path-to-storage-archive.tar.gz>"
    exit 1
fi

DB_DUMP="$1"
STORAGE_ARCHIVE="$2"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
ENV_FILE="${ENV_FILE:-.env.production}"

if [ -f "${ENV_FILE}" ]; then
    set -a
    # shellcheck disable=SC1090
    . "${ENV_FILE}"
    set +a
fi

if [ -f "${DB_DUMP}.sha256" ]; then
    sha256sum -c "${DB_DUMP}.sha256"
fi

if [ -f "${STORAGE_ARCHIVE}.sha256" ]; then
    sha256sum -c "${STORAGE_ARCHIVE}.sha256"
fi

echo "[$(date -Iseconds)] Putting application into maintenance mode..."
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan down || true

echo "[$(date -Iseconds)] Restoring MySQL database..."
gzip -dc "${DB_DUMP}" | docker compose -f "${COMPOSE_FILE}" exec -T mysql \
    mysql -u"${DB_USERNAME:-etms}" -p"${DB_PASSWORD}" "${DB_DATABASE:-etms}"

echo "[$(date -Iseconds)] Restoring company server storage directories..."
docker compose -f "${COMPOSE_FILE}" exec -T app \
    sh -c "mkdir -p '${ETMS_STORAGE_ROOT:-/srv/etms/storage}' && tar -C '${ETMS_STORAGE_ROOT:-/srv/etms/storage}' -xzf - && chown -R www-data:www-data '${ETMS_STORAGE_ROOT:-/srv/etms/storage}'" \
    < "${STORAGE_ARCHIVE}"

echo "[$(date -Iseconds)] Refreshing Laravel caches and exiting maintenance mode..."
docker compose -f "${COMPOSE_FILE}" exec -T -u www-data app php artisan migrate --force --no-interaction
docker compose -f "${COMPOSE_FILE}" exec -T -u www-data app php artisan optimize
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan up

echo "[$(date -Iseconds)] Restore completed."
