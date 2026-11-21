<?php

namespace App\Services\Operations;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    /**
     * Execute a full ETMS backup (private server storage + database snapshot/dump) into a separate backup root.
     *
     * @return array{
     *     timestamp: string,
     *     backup_dir: string,
     *     storage_archive: string,
     *     storage_sha256: string,
     *     storage_files_count: int,
     *     database_archive: ?string,
     *     database_mode: string,
     *     pruned_archives: int
     * }
     */
    public function runBackup(?int $keepDays = null, bool $includeDatabase = true, ?User $actor = null): array
    {
        $storageRoot = rtrim((string) config('etms.storage_root'), '/\\');
        $backupRoot = rtrim((string) config('etms.backup.root'), '/\\');
        $retentionDays = max(1, $keepDays ?? (int) config('etms.backup.retention_days', 14));

        $normStorage = str_replace('\\', '/', $storageRoot);
        $normBackup = str_replace('\\', '/', $backupRoot);

        if ($normBackup === '' || $normStorage === '' || str_starts_with($normBackup.'/', $normStorage.'/')) {
            throw new RuntimeException('ETMS_BACKUP_ROOT must point to a directory separate from ETMS_STORAGE_ROOT.');
        }

        File::ensureDirectoryExists("{$backupRoot}/storage");
        File::ensureDirectoryExists("{$backupRoot}/database");

        $stamp = now('Asia/Kuala_Lumpur')->format('Ymd-His-u');

        // 1. Archive private storage directories (materials, videos, recordings, certificates) + public assets
        $storageArchivePath = "{$backupRoot}/storage/etms-storage-{$stamp}.zip";
        $filesArchived = $this->createStorageArchive($storageRoot, storage_path('app/public'), $storageArchivePath);
        $storageSha256 = hash_file('sha256', $storageArchivePath) ?: '';

        // 2. Database backup via mysqldump if available, otherwise structured PHP table export
        $dbArchivePath = null;
        $dbMode = 'skipped';

        if ($includeDatabase) {
            $result = $this->backupDatabase("{$backupRoot}/database", $stamp);
            $dbArchivePath = $result['path'];
            $dbMode = $result['mode'];
        }

        // 3. Enforce retention window
        $pruned = $this->pruneOlderThan($backupRoot, $retentionDays);

        AuditLog::record(
            event: 'system.backup_completed',
            auditableType: 'system',
            auditableId: 0,
            oldValues: null,
            newValues: [
                'storage_archive' => basename($storageArchivePath),
                'storage_sha256' => $storageSha256,
                'storage_files_count' => $filesArchived,
                'database_archive' => $dbArchivePath ? basename($dbArchivePath) : null,
                'database_mode' => $dbMode,
                'retention_days' => $retentionDays,
                'pruned_archives' => $pruned,
            ],
            reason: 'Automated ETMS backup archived to isolated backup directory.',
            actor: $actor,
        );

        return [
            'timestamp' => $stamp,
            'backup_dir' => $backupRoot,
            'storage_archive' => $storageArchivePath,
            'storage_sha256' => $storageSha256,
            'storage_files_count' => $filesArchived,
            'database_archive' => $dbArchivePath,
            'database_mode' => $dbMode,
            'pruned_archives' => $pruned,
        ];
    }

    protected function createStorageArchive(string $storageRoot, string $publicRoot, string $archivePath): int
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'etms-storage-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to allocate a temporary ETMS storage archive.');
        }

        $zip = new ZipArchive;
        if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryPath);
            throw new RuntimeException("Unable to open temporary backup zip archive.");
        }

        $count = 0;

        $sources = [
            'private' => $storageRoot,
            'public' => $publicRoot,
        ];

        foreach ($sources as $prefix => $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $realFile = $file->getRealPath();
                $realBase = realpath($dir);
                if (! $realFile || ! $realBase) {
                    continue;
                }

                $relative = ltrim(str_replace('\\', '/', substr($realFile, strlen($realBase))), '/');
                $zip->addFile($realFile, "{$prefix}/{$relative}");
                $count++;
            }
        }

        if ($count === 0) {
            $zip->addFromString('EMPTY_STORAGE_MARKER.txt', "ETMS storage backup created at ".now()->toIso8601String()."\n");
        }

        if ($zip->close() !== true) {
            @unlink($temporaryPath);
            throw new RuntimeException("Unable to finalise backup zip archive at [{$archivePath}].");
        }

        if (! rename($temporaryPath, $archivePath)) {
            @unlink($temporaryPath);
            throw new RuntimeException("Unable to move backup zip archive to [{$archivePath}].");
        }

        return $count;
    }

    /**
     * @return array{path: string, mode: string}
     */
    protected function backupDatabase(string $dbBackupDir, string $stamp): array
    {
        $conn = config('database.connections.'.config('database.default'));
        $host = (string) ($conn['host'] ?? 'mysql');
        $port = (string) ($conn['port'] ?? '3306');
        $database = (string) ($conn['database'] ?? 'etms');
        $username = (string) ($conn['username'] ?? 'etms');
        $password = (string) ($conn['password'] ?? '');

        $which = new Process(['sh', '-c', 'command -v mysqldump']);
        $which->run();

        if ($which->isSuccessful() && trim($which->getOutput()) !== '') {
            $sqlPath = "{$dbBackupDir}/etms-db-{$stamp}.sql.gz";
            $cmd = sprintf(
                'mysqldump --single-transaction --routines --triggers --no-tablespaces -h %s -P %s -u %s %s | gzip -c > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($sqlPath),
            );

            $dump = new Process(['sh', '-c', $cmd], null, ['MYSQL_PWD' => $password]);
            $dump->setTimeout(600);
            $dump->run();

            if ($dump->isSuccessful() && is_file($sqlPath) && filesize($sqlPath) > 0) {
                return ['path' => $sqlPath, 'mode' => 'mysqldump_gzip'];
            }
        }

        // Portable logical fallback snapshot when lean PHP runtime omits mysql client binaries
        // (primary production stack also runs native `mysqldump` via docker/scripts/backup.sh)
        $snapshotPath = "{$dbBackupDir}/etms-db-snapshot-{$stamp}.json.gz";
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn (object $row): string => (string) collect((array) $row)->first())
            ->values();

        $payload = [
            'database' => $database,
            'exported_at_utc' => now()->toIso8601String(),
            'tables' => [],
        ];

        foreach ($tables as $table) {
            if ($table === 'sessions') {
                continue;
            }

            $payload['tables'][$table] = DB::table($table)->get()->all();
        }

        $compressed = gzencode(json_encode($payload, JSON_THROW_ON_ERROR), 6);
        file_put_contents($snapshotPath, $compressed);

        return ['path' => $snapshotPath, 'mode' => 'php_logical_snapshot_gzip'];
    }

    protected function pruneOlderThan(string $backupRoot, int $retentionDays): int
    {
        $cutoff = now()->subDays($retentionDays)->timestamp;
        $pruned = 0;

        foreach (['storage', 'database'] as $sub) {
            $dir = "{$backupRoot}/{$sub}";
            if (! is_dir($dir)) {
                continue;
            }

            foreach (File::files($dir) as $file) {
                if ($file->getMTime() < $cutoff) {
                    File::delete($file->getPathname());
                    $pruned++;
                }
            }
        }

        return $pruned;
    }
}
