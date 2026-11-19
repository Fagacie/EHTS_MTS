<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ProductionReadinessCheckCommand extends Command
{
    protected $signature = 'etms:production-check {--strict : Fail if any non-production development defaults remain}';

    protected $description = 'Verify ETMS environment security, server storage isolation, FFmpeg/HLS binaries, MySQL, and Redis health.';

    public function handle(): int
    {
        $strict = (bool) $this->option('strict') || app()->isProduction();
        $failures = [];
        $warnings = [];

        // 1. Database connectivity
        try {
            DB::connection()->getPdo();
            $this->info('[OK] MySQL database connection healthy.');
        } catch (\Throwable $e) {
            $failures[] = 'MySQL connection failed: '.$e->getMessage();
        }

        // 2. Redis connectivity
        try {
            $pong = Redis::connection()->ping();
            if ($pong) {
                $this->info('[OK] Redis cache & queue connection healthy.');
            }
        } catch (\Throwable $e) {
            $failures[] = 'Redis connection failed: '.$e->getMessage();
        }

        // 3. FFmpeg / FFprobe binaries
        foreach (['ffmpeg' => config('etms.video.ffmpeg_binary', 'ffmpeg'), 'ffprobe' => config('etms.video.ffprobe_binary', 'ffprobe')] as $name => $bin) {
            $proc = new Process(['sh', '-c', "command -v {$bin}"]);
            $proc->run();
            if ($proc->isSuccessful() && trim($proc->getOutput()) !== '') {
                $this->info("[OK] {$name} binary located: ".trim($proc->getOutput()));
            } else {
                $failures[] = "{$name} binary [{$bin}] was not found in runtime container.";
            }
        }

        // 4. Separate Private Storage Directories outside public web root
        $publicRoot = realpath(public_path()) ?: public_path();
        foreach (['materials', 'videos', 'recordings', 'certificates'] as $disk) {
            $diskRoot = (string) config("filesystems.disks.{$disk}.root");
            Storage::disk($disk)->makeDirectory('.');
            $realDisk = realpath($diskRoot) ?: $diskRoot;

            if (str_starts_with(str_replace('\\', '/', $realDisk), str_replace('\\', '/', $publicRoot))) {
                $failures[] = "Private disk [{$disk}] is placed inside public/: {$realDisk}";
            } elseif (! is_writable($diskRoot)) {
                $failures[] = "Private disk [{$disk}] directory is not writable: {$diskRoot}";
            } else {
                $this->info("[OK] Private server disk [{$disk}] isolated at: {$diskRoot}");
            }
        }

        // 5. Backup root separation
        $storageRoot = str_replace('\\', '/', (string) config('etms.storage_root'));
        $backupRoot = str_replace('\\', '/', (string) config('etms.backup.root'));
        if ($backupRoot === '' || str_starts_with($backupRoot.'/', $storageRoot.'/')) {
            $failures[] = 'Backup directory must be separate from ETMS_STORAGE_ROOT.';
        } else {
            $this->info("[OK] Backup directory isolated at: {$backupRoot}");
        }

        // 6. Strict production security checks
        $appKey = (string) config('app.key');
        if ($appKey === '') {
            $failures[] = 'APP_KEY is empty.';
        }

        $checks = [
            'APP_DEBUG must be false in production' => (bool) config('app.debug') === false,
            'APP_URL must enforce HTTPS in production' => str_starts_with((string) config('app.url'), 'https://'),
            'SESSION_SECURE_COOKIE must be true in production' => (bool) config('session.secure') === true,
            'SESSION_HTTP_ONLY must be true' => (bool) config('session.http_only') === true,
            'Billplz production API key must replace sandbox placeholder' => ! str_contains((string) config('services.billplz.api_key'), 'sandbox'),
            'Billplz production X-Signature key must replace sandbox placeholder' => ! str_contains((string) config('services.billplz.x_signature_key'), 'sandbox'),
        ];

        foreach ($checks as $message => $passed) {
            if (! $passed) {
                if ($strict) {
                    $failures[] = $message;
                } else {
                    $warnings[] = $message;
                }
            }
        }

        foreach ($warnings as $warning) {
            $this->warn("[WARN] {$warning}");
        }

        foreach ($failures as $failure) {
            $this->error("[FAIL] {$failure}");
        }

        return empty($failures) ? self::SUCCESS : self::FAILURE;
    }
}
