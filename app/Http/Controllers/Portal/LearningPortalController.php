<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EncodingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\RecordingAccessPolicy;
use App\Enums\RecordingStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\ModuleVideo;
use App\Models\SessionRecording;
use App\Models\SessionDocumentEntitlement;
use App\Services\Media\SelfHostedVideoService;
use App\Support\PortalPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class LearningPortalController extends Controller
{
    public function modules(Request $request): InertiaResponse
    {
        $user = $request->user();

        $entitlements = ModuleEntitlement::query()
            ->with([
                'module.category',
                'module.publishedMaterials',
                'module.publishedVideos',
                'order',
            ])
            ->where('user_id', $user->id)
            ->orderByDesc('granted_at')
            ->get();

        $activeEntitlements = $entitlements
            ->where('status', EntitlementStatus::Active)
            ->map(static fn (ModuleEntitlement $entitlement): ?array => PortalPresenter::activeModuleEntitlement($entitlement))
            ->filter()
            ->values()
            ->all();

        $revokedEntitlements = $entitlements
            ->where('status', EntitlementStatus::Revoked)
            ->map(static fn (ModuleEntitlement $entitlement): array => PortalPresenter::revokedModuleEntitlement($entitlement))
            ->values()
            ->all();

        return Inertia::render('Portal/Modules/Index', [
            'activeEntitlements' => $activeEntitlements,
            'revokedEntitlements' => $revokedEntitlements,
            'sessionDocuments' => SessionDocumentEntitlement::query()
                ->with(['trainingSession', 'material'])
                ->where('user_id', $user->id)
                ->where('status', EntitlementStatus::Active->value)
                ->latest('granted_at')
                ->get()
                ->map(static function (SessionDocumentEntitlement $entitlement): array {
                    return [
                        'id' => $entitlement->id,
                        'sessionTitle' => $entitlement->trainingSession?->title ?? 'Training session',
                        'documentTitle' => $entitlement->material?->title ?? 'Session document',
                        'grantedDateLabel' => $entitlement->granted_at?->format('j M Y') ?? '—',
                        'downloadUrl' => $entitlement->material
                            ? URL::temporarySignedRoute(
                                'session-materials.download',
                                now()->addMinutes(10),
                                ['material' => $entitlement->material],
                            )
                            : null,
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    public function showModule(
        Request $request,
        Module $module,
        SelfHostedVideoService $videoService,
    ): InertiaResponse {
        $user = $request->user();
        $module->loadMissing(['category', 'publishedMaterials', 'publishedVideos']);

        $isAuthorized = $user->isActive() && (
            $user->hasActiveModuleEntitlement($module->id)
            || ($user->isAdministrator() && $user->hasPermission('modules.manage'))
        );

        abort_unless(
            $isAuthorized,
            403,
            'Access denied: An active lifetime module entitlement is required to open this module learning workspace.',
        );

        $readyVideos = $module->publishedVideos
            ->filter(fn (ModuleVideo $video): bool => $video->isPlayable())
            ->values();

        $activeVideo = $readyVideos->first();
        $streamManifestUrl = null;

        if ($activeVideo !== null) {
            $streamManifestUrl = $videoService->temporaryModuleVideoManifestUrl(
                video: $activeVideo,
                ttlSeconds: (int) config('etms.video.token_ttl_seconds', 3600),
            );
        }

        $entitlement = ModuleEntitlement::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', EntitlementStatus::Active->value)
            ->first();

        return Inertia::render(
            'Portal/Modules/Show',
            PortalPresenter::moduleWorkspaceData($module, $entitlement, $activeVideo, $streamManifestUrl),
        );
    }

    public function watchModuleVideo(
        Request $request,
        Module $module,
        ModuleVideo $video,
        SelfHostedVideoService $videoService,
    ): InertiaResponse {
        if ($request->has('signature') || $request->has('expires')) {
            abort_unless(
                $request->hasValidSignature(),
                403,
                'This temporary video playback link has expired or is invalid.',
            );
        }

        abort_unless($video->module_id === $module->id, 404);

        $video->loadMissing('module');
        Gate::authorize('watch', $video);

        $streamManifestUrl = $videoService->temporaryModuleVideoManifestUrl(
            video: $video,
            ttlSeconds: (int) config('etms.video.token_ttl_seconds', 3600),
        );

        AuditLog::record(
            event: 'module_video.watched',
            auditableType: ModuleVideo::class,
            auditableId: $video->id,
            oldValues: null,
            newValues: [
                'module_id' => $module->id,
                'provider_video_id' => $video->provider_video_id,
                'hls_manifest_key' => $video->hls_manifest_key,
            ],
            reason: 'Authorized trainee accessed self-hosted module video stream.',
            actor: $request->user(),
        );

        $module->loadMissing(['category', 'publishedMaterials', 'publishedVideos']);

        $entitlement = ModuleEntitlement::query()
            ->where('user_id', $request->user()->id)
            ->where('module_id', $module->id)
            ->where('status', EntitlementStatus::Active->value)
            ->first();

        return Inertia::render(
            'Portal/Modules/Show',
            PortalPresenter::moduleWorkspaceData($module, $entitlement, $video, $streamManifestUrl),
        );
    }

    public function streamModuleVideoAsset(
        Request $request,
        Module $module,
        ModuleVideo $video,
        string $asset,
        SelfHostedVideoService $videoService,
    ): Response {
        abort_unless(
            $request->hasValidSignature(),
            403,
            'This protected video stream signature has expired or is invalid.',
        );

        abort_unless($video->module_id === $module->id, 404);

        $video->loadMissing('module');
        Gate::authorize('watch', $video);

        $disk = $video->storage_disk ?: 'videos';
        $storageKey = $videoService->resolveAssetStorageKey(
            hlsManifestKey: $video->hls_manifest_key,
            sourceStorageKey: $video->source_storage_key,
            requestedAsset: $asset,
        );

        abort_unless(
            $storageKey !== null && Storage::disk($disk)->exists($storageKey),
            404,
            'The requested video stream asset was not found on server storage.',
        );

        if (str_ends_with(strtolower($asset), '.m3u8')) {
            $rawManifest = (string) Storage::disk($disk)->get($storageKey);
            $rewritten = $videoService->rewriteManifestWithSignedAssets(
                $rawManifest,
                fn (string $segmentName): string => URL::temporarySignedRoute(
                    'portal.modules.videos.stream',
                    now()->addSeconds((int) config('etms.video.token_ttl_seconds', 3600)),
                    [
                        'module' => $module->slug,
                        'video' => $video->id,
                        'asset' => $segmentName,
                    ],
                ),
            );

            return response($rewritten, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $contentType = str_ends_with(strtolower($asset), '.ts')
            ? 'video/mp2t'
            : ($video->mime_type ?: 'video/mp4');

        return $videoService->streamProtectedFile($request, $disk, $storageKey, $contentType);
    }

    public function recordings(Request $request): InertiaResponse
    {
        $user = $request->user();

        $bookings = Booking::query()
            ->with([
                'trainingSession.recordings' => fn ($q) => $q
                    ->where('status', RecordingStatus::Published->value)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'trainingSession.publishedMaterials',
                'attendance',
            ])
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::Confirmed->value)
            ->orderByDesc('created_at')
            ->get();

        $bookingItems = $bookings->map(function (Booking $booking): array {
            $session = $booking->trainingSession;
            $attendance = $booking->attendance;

            $isEligible = false;
            $denialReason = null;

            if (! $session) {
                $denialReason = 'Training session is no longer available.';
            } elseif ($session->recording_access_policy !== RecordingAccessPolicy::AttendeesOnly) {
                $denialReason = 'Post-session recording access is disabled for this training session (recording_access_policy = none).';
            } elseif ($booking->status !== BookingStatus::Confirmed) {
                $denialReason = "Booking status is {$booking->status->label()} (requires Confirmed booking).";
            } elseif (! $attendance) {
                $denialReason = 'Attendance has not been recorded yet.';
            } elseif ($attendance->attendance_status !== AttendanceStatus::Present) {
                $denialReason = "Attendance status is {$attendance->attendance_status->label()} — session recordings are restricted to participants marked Present (D-017).";
            } elseif ($attendance->review_status !== AttendanceReviewStatus::Verified) {
                $denialReason = "Attendance review status is {$attendance->review_status->label()} — administrator verification is required before recording playback unlocks (D-017).";
            } else {
                $isEligible = true;
            }

            return PortalPresenter::recordingBookingGroup([
                'booking' => $booking,
                'session' => $session,
                'attendance' => $attendance,
                'recordings' => $session?->recordings ?? collect(),
                'isEligible' => $isEligible,
                'denialReason' => $denialReason,
            ]);
        })->values()->all();

        return Inertia::render('Portal/Recordings/Index', [
            'bookingItems' => $bookingItems,
        ]);
    }

    public function watchRecording(
        Request $request,
        SessionRecording $recording,
        SelfHostedVideoService $videoService,
    ): InertiaResponse {
        if ($request->has('signature') || $request->has('expires')) {
            abort_unless(
                $request->hasValidSignature(),
                403,
                'This temporary recording playback link has expired or is invalid.',
            );
        }

        $recording->loadMissing('trainingSession.module');

        Gate::authorize('watch', $recording);

        $streamManifestUrl = $videoService->temporaryRecordingManifestUrl(
            recording: $recording,
            ttlSeconds: (int) config('etms.video.token_ttl_seconds', 3600),
        );

        AuditLog::record(
            event: 'session_recording.watched',
            auditableType: SessionRecording::class,
            auditableId: $recording->id,
            oldValues: null,
            newValues: [
                'training_session_id' => $recording->training_session_id,
                'provider_video_id' => $recording->provider_video_id,
                'hls_manifest_key' => $recording->hls_manifest_key,
            ],
            reason: 'Verified present attendee accessed self-hosted session recording.',
            actor: $request->user(),
        );

        return Inertia::render(
            'Portal/Recordings/Watch',
            PortalPresenter::recordingWatchData($recording, $recording->trainingSession, $streamManifestUrl),
        );
    }

    public function streamRecordingAsset(
        Request $request,
        SessionRecording $recording,
        string $asset,
        SelfHostedVideoService $videoService,
    ): Response {
        abort_unless(
            $request->hasValidSignature(),
            403,
            'This protected session recording stream signature has expired or is invalid.',
        );

        $recording->loadMissing('trainingSession.module');
        Gate::authorize('watch', $recording);

        $disk = $recording->storage_disk ?: 'recordings';
        $storageKey = $videoService->resolveAssetStorageKey(
            hlsManifestKey: $recording->hls_manifest_key,
            sourceStorageKey: $recording->source_storage_key,
            requestedAsset: $asset,
        );

        abort_unless(
            $storageKey !== null && Storage::disk($disk)->exists($storageKey),
            404,
            'The requested session recording asset was not found on server storage.',
        );

        if (str_ends_with(strtolower($asset), '.m3u8')) {
            $rawManifest = (string) Storage::disk($disk)->get($storageKey);
            $rewritten = $videoService->rewriteManifestWithSignedAssets(
                $rawManifest,
                fn (string $segmentName): string => URL::temporarySignedRoute(
                    'portal.recordings.stream',
                    now()->addSeconds((int) config('etms.video.token_ttl_seconds', 3600)),
                    [
                        'recording' => $recording->id,
                        'asset' => $segmentName,
                    ],
                ),
            );

            return response($rewritten, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $contentType = str_ends_with(strtolower($asset), '.ts')
            ? 'video/mp2t'
            : ($recording->mime_type ?: 'video/mp4');

        return $videoService->streamProtectedFile($request, $disk, $storageKey, $contentType);
    }
}
