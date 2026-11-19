# ETMS Production Deployment, Storage & Recovery Runbook

## 1. Architecture Summary

- **Web / TLS Tier:** `nginx` (`1.27-alpine`) terminating TLSv1.2/TLSv1.3 on `:443`, redirecting `:80` (except ACME HTTP-01 challenge) to HTTPS, enforcing security headers, blocking dotfiles/sensitive config files, and serving private assets internally via `X-Accel-Redirect`.
- **Application Runtime:** `app` (PHP `8.3.36` FPM `bookworm` target `production` in `Dockerfile`), `queue` worker (`--queue=webhooks,videos,pdfs,notifications,default`), and `scheduler` (`schedule:work`) running compiled Vite assets, optimized OPcache (`opcache.validate_timestamps=0`), Composer `--no-dev --optimize-autoloader --classmap-authoritative`, and FFmpeg/FFprobe for HLS video generation.
- **Data Services:** MySQL `8.4` (`utf8mb4_unicode_ci`, UTC) + Redis `7-alpine` (`appendonly yes` with password authentication).
- **Company Server Private Storage (`ETMS_STORAGE_ROOT`):**
  - `${ETMS_STORAGE_ROOT}/materials` — Module learning materials (`materials` disk)
  - `${ETMS_STORAGE_ROOT}/videos` — Module uploaded source videos and FFmpeg HLS streams (`videos` disk)
  - `${ETMS_STORAGE_ROOT}/recordings` — Session uploaded recordings and FFmpeg HLS streams (`recordings` disk)
  - `${ETMS_STORAGE_ROOT}/certificates` — Generated DomPDF A4 completion certificates (`certificates` disk)
  All four directories sit strictly **outside** `public/` and are configured with `'visibility' => 'private'` and `'serve' => false`.

## 2. Production Provisioning Checklist

1. **Prepare Host Server Directories (Linux / Company Server):**
   ```sh
   sudo mkdir -p /srv/etms/storage/materials /srv/etms/storage/videos /srv/etms/storage/recordings /srv/etms/storage/certificates
   sudo mkdir -p /srv/etms/backups/database /srv/etms/backups/storage
   sudo mkdir -p ./docker/certs
   ```
   Mount `/srv/etms/backups` onto an independent disk partition or off-server NAS/NFS target separate from `/srv/etms/storage`.

2. **Configure Production Environment & Secrets:**
   ```sh
   cp .env.production.example .env.production
   chmod 600 .env.production
   ```
   Populate real credentials in `.env.production`:
   - Generate `APP_KEY` (`php artisan key:generate --show`).
   - Generate `ETMS_MEDIA_SIGNING_KEY` (`openssl rand -hex 32`).
   - Configure strong `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `REDIS_PASSWORD`.
   - Set `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN`, `APP_URL=https://...`, `APP_DEBUG=false`.
   - Set production `BILLPLZ_*` (`BILLPLZ_SANDBOX=false`) and `RESEND_API_KEY`.
   - Place valid TLS certificate and private key in `./docker/certs/fullchain.pem` and `./docker/certs/privkey.pem` (mode `600`).

3. **Compile Frontend Assets & Start Production Stack:**
   ```sh
   npm ci && npm run build
   docker compose -f docker-compose.prod.yml up -d --build
   ```

4. **Run Migrations, Production Readiness Check & Cache Warmup:**
   ```sh
   docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan migrate --force
   docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan etms:production-check --strict
   ```
   Confirm zero `[FAIL]` diagnostics before opening external DNS traffic.

5. **Configure Host Docker Boot Auto-Start:**
   - All containers in `docker-compose.prod.yml` specify `restart: always`.
   - Ensure the Docker daemon is enabled at boot on Linux hosts:
     ```sh
     sudo systemctl enable --now docker
     ```

## 3. Backup & Disaster Recovery

### Automated Schedule
- **In-App Schedule (`routes/console.php`):** `etms:backup` runs daily at `02:30` MYT (`Asia/Kuala_Lumpur`), archiving private company server storage and enforcing retention (`ETMS_BACKUP_RETENTION_DAYS`, default `30` days in `.env.production.example`).
- **Host Cron for Native MySQL + Storage Tarball (`docker/scripts/backup.sh`):**
  ```cron
  30 2 * * * COMPOSE_FILE=/opt/etms/docker-compose.prod.yml ENV_FILE=/opt/etms/.env.production /opt/etms/docker/scripts/backup.sh >> /var/log/etms-backup.log 2>&1
  ```
  Each run outputs:
  - `${ETMS_BACKUP_ROOT}/database/etms-db-YYYYMMDD-HHMMSS.sql.gz` + `.sha256`
  - `${ETMS_BACKUP_ROOT}/storage/etms-storage-YYYYMMDD-HHMMSS.tar.gz` + `.sha256`

### Recovery Procedure
1. Verify backup integrity checksums (`sha256sum -c`).
2. Execute the restore script:
   ```sh
   sh docker/scripts/restore.sh \
     /srv/etms/backups/database/etms-db-YYYYMMDD-HHMMSS.sql.gz \
     /srv/etms/backups/storage/etms-storage-YYYYMMDD-HHMMSS.tar.gz
   ```
3. Validate application health and readiness:
   ```sh
   docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan etms:production-check --strict
   ```

## 4. Routine Maintenance & Rollout

- **Deploy code update:**
  ```sh
  git pull --ff-only
  npm ci && npm run build
  docker compose -f docker-compose.prod.yml up -d --build
  docker compose -f docker-compose.prod.yml exec -T -u www-data app php artisan migrate --force
  docker compose -f docker-compose.prod.yml restart queue scheduler
  ```
- **Verify running workers & queue depth:**
  ```sh
  docker compose -f docker-compose.prod.yml ps
  docker compose -f docker-compose.prod.yml logs --tail=50 queue scheduler nginx app
  ```
