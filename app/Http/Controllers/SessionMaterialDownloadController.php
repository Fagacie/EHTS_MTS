<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SessionMaterial;
use App\Services\Media\SelfHostedVideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SessionMaterialDownloadController extends Controller
{
    public function __invoke(Request $request, SessionMaterial $material, SelfHostedVideoService $storageStreamer): Response
    {
        abort_unless(! $request->has('signature') || $request->hasValidSignature(), 403, 'This temporary material download link is invalid.');
        $material->loadMissing('trainingSession.instructors');
        Gate::authorize('download', $material);

        $disk = $material->storage_disk ?: 'session-materials';
        abort_unless(Storage::disk($disk)->exists($material->storage_key), 404, 'The requested session material is not available.');

        AuditLog::record(
            event: 'session_material.downloaded',
            auditableType: SessionMaterial::class,
            auditableId: $material->id,
            oldValues: null,
            newValues: [
                'training_session_id' => $material->training_session_id,
                'title' => $material->title,
                'original_filename' => $material->original_filename,
            ],
            reason: 'Authorized user downloaded session teaching material.',
            actor: $request->user(),
        );

        return $storageStreamer->streamProtectedFile(
            request: $request,
            disk: $disk,
            storageKey: $material->storage_key,
            contentType: $material->mime_type ?: 'application/octet-stream',
            downloadFilename: $material->original_filename,
        );
    }
}
