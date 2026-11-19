<?php

namespace App\Http\Controllers\Portal;

use App\Enums\CertificateStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Services\Certificate\CertificateService;
use App\Services\Media\SelfHostedVideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $certificates = Certificate::query()
            ->with(['trainingSession', 'module'])
            ->where('user_id', $user->id)
            ->orderByDesc('issued_at')
            ->get();

        return view('portal.certificates.index', [
            'issuedCertificates' => $certificates->where('status', CertificateStatus::Issued),
            'revokedCertificates' => $certificates->where('status', CertificateStatus::Revoked),
        ]);
    }

    public function show(Request $request, Certificate $certificate, CertificateService $certificateService): View
    {
        Gate::authorize('view', $certificate);

        $certificate->loadMissing(['trainingSession', 'module', 'issuer', 'revoker']);

        return view('portal.certificates.show', [
            'certificate' => $certificate,
            'logoDataUri' => $certificateService->resolveLogoDataUri(),
        ]);
    }

    public function download(
        Request $request,
        Certificate $certificate,
        CertificateService $certificateService,
        SelfHostedVideoService $storageStreamer,
    ): Response {
        if ($request->has('signature') || $request->has('expires')) {
            abort_unless(
                $request->hasValidSignature(),
                403,
                'This temporary certificate download link has expired or is invalid.',
            );
        }

        Gate::authorize('download', $certificate);

        $disk = $certificate->pdf_storage_disk ?: 'certificates';
        $storageKey = $certificate->pdf_storage_key;

        if (blank($storageKey) || ! Storage::disk($disk)->exists($storageKey)) {
            $certificate = $certificateService->regeneratePdf($certificate, $request->user());
            $storageKey = $certificate->pdf_storage_key;
        }

        abort_unless(
            filled($storageKey) && Storage::disk($disk)->exists($storageKey),
            404,
            'The generated PDF certificate file is not available in storage.',
        );

        AuditLog::record(
            event: 'certificate.downloaded',
            auditableType: Certificate::class,
            auditableId: $certificate->id,
            oldValues: null,
            newValues: [
                'certificate_number' => $certificate->certificate_number,
                'storage_key' => $storageKey,
            ],
            reason: 'Certificate PDF downloaded.',
            actor: $request->user(),
        );

        return $storageStreamer->streamProtectedFile(
            request: $request,
            disk: $disk,
            storageKey: $storageKey,
            contentType: 'application/pdf',
            downloadFilename: "{$certificate->certificate_number}.pdf",
        );
    }
}
