<?php

namespace App\Services\Media;

use App\Models\ModuleVideo;
use App\Models\SessionRecording;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

class SelfHostedVideoService
{
    public function computeStreamToken(string $streamKey, int $expiresTimestamp, ?string $signingKey = null): string
    {
        $secret = $signingKey ?: (string) config('etms.video.signing_key');

        return hash_hmac('sha256', "{$streamKey}|{$expiresTimestamp}", $secret);
    }

    public function verifyStreamToken(
        string $streamKey,
        string $token,
        int $expiresTimestamp,
        ?string $signingKey = null,
    ): bool {
        if ($token === '' || $expiresTimestamp <= now()->timestamp) {
            return false;
        }

        $expected = $this->computeStreamToken($streamKey, $expiresTimestamp, $signingKey);

        return hash_equals($expected, $token);
    }

    public function temporaryModuleVideoManifestUrl(ModuleVideo $video, int $ttlSeconds = 3600): ?string
    {
        $asset = basename((string) ($video->hls_manifest_key ?: $video->source_storage_key));

        if ($asset === '') {
            return null;
        }

        return URL::temporarySignedRoute(
            'portal.modules.videos.stream',
            now()->addSeconds($ttlSeconds),
            [
                'module' => $video->module?->slug ?? $video->module_id,
                'video' => $video->id,
                'asset' => $asset,
            ],
        );
    }

    public function temporaryRecordingManifestUrl(SessionRecording $recording, int $ttlSeconds = 3600): ?string
    {
        $asset = basename((string) ($recording->hls_manifest_key ?: $recording->source_storage_key));

        if ($asset === '') {
            return null;
        }

        return URL::temporarySignedRoute(
            'portal.recordings.stream',
            now()->addSeconds($ttlSeconds),
            [
                'recording' => $recording->id,
                'asset' => $asset,
            ],
        );
    }

    public function inspectDuration(string $disk, string $sourceKey): ?int
    {
        $storage = Storage::disk($disk);
        if (! $storage->exists($sourceKey)) {
            return null;
        }

        $absPath = $storage->path($sourceKey);
        if (! is_file($absPath)) {
            return null;
        }

        $ffprobe = (string) config('etms.video.ffprobe_binary', 'ffprobe');
        $process = new Process([
            $ffprobe,
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $absPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $raw = trim($process->getOutput());

        return is_numeric($raw) && (float) $raw > 0 ? (int) round((float) $raw) : null;
    }

    /**
     * Transcode an uploaded video stored on a private disk into VOD HLS (`.m3u8` + `.ts` segments).
     *
     * @return array{manifest_key: string, duration_seconds: int|null, file_size_bytes: int, mime_type: string}
     */
    public function transcodeToHls(string $disk, string $sourceKey, string $outputDir): array
    {
        $storage = Storage::disk($disk);
        if (! $storage->exists($sourceKey)) {
            throw new RuntimeException("Source video file [{$sourceKey}] was not found on disk [{$disk}].");
        }

        $sourcePath = $storage->path($sourceKey);
        $cleanOutputDir = trim(str_replace('\\', '/', $outputDir), '/');
        $manifestKey = "{$cleanOutputDir}/stream.m3u8";

        $storage->makeDirectory($cleanOutputDir);
        $absOutputDir = $storage->path($cleanOutputDir);
        $absManifestPath = $storage->path($manifestKey);
        $segmentPattern = "{$absOutputDir}/seg_%04d.ts";

        $ffmpeg = (string) config('etms.video.ffmpeg_binary', 'ffmpeg');
        $segmentSeconds = max(2, (int) config('etms.video.hls_segment_seconds', 6));

        $process = new Process([
            $ffmpeg,
            '-y',
            '-i', $sourcePath,
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-crf', '23',
            '-pix_fmt', 'yuv420p',
            '-g', '48',
            '-keyint_min', '48',
            '-sc_threshold', '0',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-ac', '2',
            '-f', 'hls',
            '-hls_time', (string) $segmentSeconds,
            '-hls_playlist_type', 'vod',
            '-hls_flags', 'independent_segments',
            '-hls_segment_filename', $segmentPattern,
            $absManifestPath,
        ]);
        $process->setTimeout(1800);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($absManifestPath)) {
            throw new RuntimeException(
                'FFmpeg HLS transcoding failed: '.mb_substr($process->getErrorOutput() ?: $process->getOutput(), 0, 1500)
            );
        }

        $durationSeconds = $this->inspectDuration($disk, $sourceKey) ?? $this->inspectDuration($disk, $manifestKey);
        $totalBytes = 0;

        foreach ($storage->files($cleanOutputDir) as $generatedFile) {
            $totalBytes += (int) $storage->size($generatedFile);
        }

        return [
            'manifest_key' => $manifestKey,
            'duration_seconds' => $durationSeconds,
            'file_size_bytes' => max(1, $totalBytes),
            'mime_type' => 'application/vnd.apple.mpegurl',
        ];
    }

    /**
     * Rewrite relative `.ts` segment / nested `.m3u8` paths inside an HLS manifest into signed ETMS URLs.
     *
     * @param  callable(string): string  $assetUrlResolver
     */
    public function rewriteManifestWithSignedAssets(string $rawManifest, callable $assetUrlResolver): string
    {
        $lines = preg_split("/\r\n|\r|\n/", $rawManifest) ?: [];
        $rewritten = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $rewritten[] = $line;

                continue;
            }

            if (str_starts_with($trimmed, '#')) {
                if (preg_match('/URI="([^"]+)"/', $line, $matches) === 1) {
                    $signedUri = $assetUrlResolver(basename($matches[1]));
                    $line = str_replace($matches[0], 'URI="'.$signedUri.'"', $line);
                }

                $rewritten[] = $line;

                continue;
            }

            $rewritten[] = $assetUrlResolver(basename($trimmed));
        }

        return implode("\n", $rewritten)."\n";
    }

    public function resolveAssetStorageKey(?string $hlsManifestKey, ?string $sourceStorageKey, string $requestedAsset): ?string
    {
        $safeAsset = basename($requestedAsset);
        if (
            $safeAsset === ''
            || $safeAsset !== $requestedAsset
            || str_contains($safeAsset, '..')
            || preg_match('/^[A-Za-z0-9._-]+\.(m3u8|ts|mp4)$/', $safeAsset) !== 1
        ) {
            return null;
        }

        if ($hlsManifestKey) {
            $hlsDir = trim(dirname($hlsManifestKey), './');
            $candidate = $hlsDir === '' ? $safeAsset : "{$hlsDir}/{$safeAsset}";

            return $candidate;
        }

        if ($sourceStorageKey && basename($sourceStorageKey) === $safeAsset) {
            return $sourceStorageKey;
        }

        return null;
    }

    public function streamProtectedFile(
        Request $request,
        string $disk,
        string $storageKey,
        string $contentType,
        ?string $downloadFilename = null,
    ): Response {
        abort_if(
            str_contains($storageKey, '..') || str_contains($storageKey, "\0"),
            403,
            'Invalid private storage path.',
        );

        $storage = Storage::disk($disk);
        abort_unless($storage->exists($storageKey), 404, 'Requested protected asset was not found on server storage.');

        if (
            ! app()->runningUnitTests()
            && config('etms.nginx.x_accel_enabled', false)
            && $this->diskUsesConfiguredStorageRoot($disk)
        ) {
            $prefix = (string) config('etms.nginx.x_accel_internal_prefix', '/_etms_internal_storage');
            $internalUri = "{$prefix}/{$disk}/".ltrim($storageKey, '/');

            $headers = [
                'X-Accel-Redirect' => $internalUri,
                'Content-Type' => $contentType,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ];

            if ($downloadFilename !== null) {
                $headers['Content-Disposition'] = 'attachment; filename="'.addslashes($downloadFilename).'"';
            }

            return response('', 200, $headers);
        }

        if ($downloadFilename !== null) {
            return $storage->download($storageKey, $downloadFilename, [
                'Content-Type' => $contentType,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $absolutePath = $storage->path($storageKey);
        if (is_file($absolutePath)) {
            $response = new BinaryFileResponse($absolutePath, 200, [
                'Content-Type' => $contentType,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
                'Accept-Ranges' => 'bytes',
            ]);
            $response->prepare($request);

            return $response;
        }

        return response((string) $storage->get($storageKey), 200, [
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    protected function diskUsesConfiguredStorageRoot(string $disk): bool
    {
        $configuredDiskRoot = config("filesystems.disks.{$disk}.root");
        $actualDiskRoot = Storage::disk($disk)->path('');

        if (! is_string($configuredDiskRoot) || ! is_string($actualDiskRoot)) {
            return false;
        }

        return rtrim(str_replace('\\', '/', $configuredDiskRoot), '/')
            === rtrim(str_replace('\\', '/', $actualDiskRoot), '/');
    }
}
