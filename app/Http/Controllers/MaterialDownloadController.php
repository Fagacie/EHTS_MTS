<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ModuleMaterial;
use App\Services\Media\SelfHostedVideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MaterialDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        ModuleMaterial $material,
        SelfHostedVideoService $storageStreamer,
    ): Response {
        if ($request->has('signature') || $request->has('expires')) {
            abort_unless(
                $request->hasValidSignature(),
                403,
                'This temporary material download link has expired or is invalid.',
            );
        }

        $material->loadMissing('module');

        Gate::authorize('download', $material);

        $disk = $material->storage_disk ?: 'materials';

        abort_unless(
            Storage::disk($disk)->exists($material->storage_key),
            404,
            'The requested learning material file is not available in server storage.',
        );

        AuditLog::record(
            event: 'module_material.downloaded',
            auditableType: 'module_materials',
            auditableId: $material->id,
            newValues: [
                'module_id' => $material->module_id,
                'original_filename' => $material->original_filename,
            ],
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
