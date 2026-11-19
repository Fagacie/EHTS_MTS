<?php

namespace App\Console\Commands;

use App\Services\Operations\BackupService;
use Illuminate\Console\Command;

class BackupEtmsCommand extends Command
{
    protected $signature = 'etms:backup {--keep-days= : Override retention window in days} {--skip-db : Archive storage only}';

    protected $description = 'Create an isolated ETMS backup (server storage archive + database snapshot) and enforce retention.';

    public function handle(BackupService $backups): int
    {
        $keepOption = $this->option('keep-days');
        $keepDays = $keepOption !== null ? (int) $keepOption : null;
        $includeDatabase = ! (bool) $this->option('skip-db');

        $result = $backups->runBackup($keepDays, $includeDatabase);

        $this->info("ETMS backup completed at {$result['timestamp']}");
        $this->line(" - Backup Root     : {$result['backup_dir']}");
        $this->line(" - Storage Archive : {$result['storage_archive']} ({$result['storage_files_count']} file(s), sha256: {$result['storage_sha256']})");

        if ($result['database_archive']) {
            $this->line(" - Database Backup : {$result['database_archive']} (mode: {$result['database_mode']})");
        }

        $this->line(" - Pruned Old Files: {$result['pruned_archives']}");

        return self::SUCCESS;
    }
}
