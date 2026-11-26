<?php

namespace App\Http\Controllers\Portal;

use App\Enums\CertificateStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Services\Certificate\CertificateService;
use App\Services\Media\SelfHostedVideoService;
use App\Support\PortalPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        $certificates = Certificate::query()
            ->with(['trainingSession', 'module'])
            ->where('user_id', $user->id)
            ->orderByDesc('issued_at')
            ->get();

        return Inertia::render('Portal/Certificates/Index', [
            'issuedCertificates' => $certificates
                ->where('status', CertificateStatus::Issued)
                ->map(static fn (Certificate $certificate): array => PortalPresenter::certificateListItem($certificate))
                ->values()
                ->all(),
            'revokedCertificates' => $certificates
                ->where('status', CertificateStatus::Revoked)
                ->map(static fn (Certificate $certificate): array => PortalPresenter::certificateListItem($certificate))
                ->values()
                ->all(),
        ]);
    }

    public function show(Request $request, Certificate $certificate, CertificateService $certificateService): InertiaResponse
    {
        Gate::authorize('view', $certificate);

        $certificate->loadMissing(['trainingSession', 'module']);

        return Inertia::render('Portal/Certificates/Show', [
            'certificate' => PortalPresenter::certificateDetail($certificate),
            'logoDataUri' => $certificateService->resolveLogoDataUri(),
            'signatureDataUri' => $certificateService->resolveSignatureDataUri(),
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
        $pdfBytes = null;

        if (filled($storageKey) && Storage::disk($disk)->exists($storageKey)) {
            $pdfBytes = Storage::disk($disk)->get($storageKey);
        }

        if (blank($pdfBytes)) {
            try {
                $certificate = $certificateService->regeneratePdf($certificate, $request->user());
                $storageKey = $certificate->pdf_storage_key;
                if (filled($storageKey) && Storage::disk($disk)->exists($storageKey)) {
                    $pdfBytes = Storage::disk($disk)->get($storageKey);
                }
            } catch (\Throwable) {
                $pdfBytes = null;
            }
        }

        if (blank($pdfBytes)) {
            $pdfBytes = $certificateService->renderPdfBytes($certificate);
        }

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

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$certificate->certificate_number.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
