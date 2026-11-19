#!/bin/sh
# Standalone ETMS Production Backup Script
# Stores MySQL dumps and private server storage archives strictly outside primary storage.
set -eu

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
ENV_FILE="${ENV_FILE:-.env.production}"
BACKUP_ROOT="${ETMS_BACKUP_ROOT:-/var/backups/etms}"
RETENTION_DAYS="${ETMS_BACKUP_RETENTION_DAYS:-14}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

if [ -f "${ENV_FILE}" ]; then
    set -a
    # shellcheck disable=SC1090
    . "${ENV_FILE}"
    set +a
fi

mkdir -p "${BACKUP_ROOT}/database" "${BACKUP_ROOT}/storage"

DB_DUMP_PATH="${BACKUP_ROOT}/database/etms-db-${TIMESTAMP}.sql.gz"
STORAGE_ARCHIVE_PATH="${BACKUP_ROOT}/storage/etms-storage-${TIMESTAMP}.tar.gz"

echo "[$(date -Iseconds)] Starting MySQL dump..."
docker compose -f "${COMPOSE_FILE}" exec -T mysql \
    mysqldump --single-transaction --routines --triggers --no-tablespaces \
    -u"${DB_USERNAME:-etms}" -p"${DB_PASSWORD}" "${DB_DATABASE:-etms}" \
    | gzip -c > "${DB_DUMP_PATH}"

sha256sum "${DB_DUMP_PATH}" > "${DB_DUMP_PATH}.sha256"

echo "[$(date -Iseconds)] Archiving private company server storage (materials, videos, recordings, certificates)..."
docker compose -f "${COMPOSE_FILE}" exec -T app \
    tar -C "${ETMS_STORAGE_ROOT:-/srv/etms/storage}" -czf - materials videos recordings certificates \
    > "${STORAGE_ARCHIVE_PATH}"

sha256sum "${STORAGE_ARCHIVE_PATH}" > "${STORAGE_ARCHIVE_PATH}.sha256"

echo "[$(date -Iseconds)] Pruning backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_ROOT}/database" -type f -mtime +"${RETENTION_DAYS}" -delete
find "${BACKUP_ROOT}/storage" -type f -mtime +"${RETENTION_DAYS}" -delete

echo "[$(date -Iseconds)] Backup completed cleanly:"
echo "  DB Dump : ${DB_DUMP_PATH}"
echo "  Storage : ${STORAGE_ARCHIVE_PATH}"
