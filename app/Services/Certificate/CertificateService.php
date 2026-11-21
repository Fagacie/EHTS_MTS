<?php

namespace App\Services\Certificate;

use App\Enums\BookingStatus;
use App\Enums\CertificateStatus;
use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PdfGenerationStatus;
use App\Enums\EntitlementStatus;
use App\Enums\ModuleStatus;
use App\Enums\SessionStatus;
use App\Jobs\GenerateCertificatePdfJob;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\SystemSetting;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    public const DEFAULT_ORGANISATION = 'Ecohydrotech Solution';

    public const DEFAULT_SIGNATORY_NAME = 'Ir. Dr. Ahmad Zulkifli, P.Eng';

    public const DEFAULT_SIGNATORY_TITLE = 'Managing Director, Ecohydrotech Solution';

    public const DEFAULT_LOGO_PATH = 'images/ecohydrotech-logo.svg';

    /**
     * Retrieve global certificate configuration values from `system_settings`.
     *
     * @return array{issuing_organisation: string, signatory_name: string, signatory_title: string, logo_path: string, signature_path: string}
     */
    public function globalSettings(): array
    {
        return [
            'issuing_organisation' => (string) (SystemSetting::getValue('certificates.issuing_organisation') ?: self::DEFAULT_ORGANISATION),
            'signatory_name' => (string) (SystemSetting::getValue('certificates.signatory_name') ?: self::DEFAULT_SIGNATORY_NAME),
            'signatory_title' => (string) (SystemSetting::getValue('certificates.signatory_title') ?: self::DEFAULT_SIGNATORY_TITLE),
            'logo_path' => (string) (SystemSetting::getValue('certificates.logo_path') ?: self::DEFAULT_LOGO_PATH),
            'signature_path' => (string) (SystemSetting::getValue('certificates.signature_path') ?: ''),
        ];
    }

    /**
     * Persist global certificate signatory & organisation configuration in `system_settings`.
     */
    public function updateGlobalSettings(
        User $admin,
        string $issuingOrganisation,
        string $signatoryName,
        string $signatoryTitle,
        ?string $logoPath = null,
        ?string $signaturePath = null,
    ): void {
        $old = $this->globalSettings();

        SystemSetting::setValue(
            key: 'certificates.issuing_organisation',
            value: $issuingOrganisation,
            group: 'certificates',
            type: 'string',
            description: 'Organisation name printed on issued training certificates (D-027).',
            isPublic: true,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'certificates.signatory_name',
            value: $signatoryName,
            group: 'certificates',
            type: 'string',
            description: 'Authorised signatory full name printed on issued certificates (D-027).',
            isPublic: false,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'certificates.signatory_title',
            value: $signatoryTitle,
            group: 'certificates',
            type: 'string',
            description: 'Authorised signatory designation printed on issued certificates (D-027).',
            isPublic: false,
            updatedBy: $admin->id,
        );

        if ($logoPath !== null && $logoPath !== '') {
            SystemSetting::setValue(
                key: 'certificates.logo_path',
                value: $logoPath,
                group: 'certificates',
                type: 'string',
                description: 'Logo asset path rendered on official PDF certificates.',
                isPublic: false,
                updatedBy: $admin->id,
            );
        }

        if ($signaturePath !== null && $signaturePath !== '') {
            SystemSetting::setValue(
                key: 'certificates.signature_path',
                value: $signaturePath,
                group: 'certificates',
                type: 'string',
                description: 'Uploaded authorised signatory image rendered on official PDF certificates.',
                isPublic: false,
                updatedBy: $admin->id,
            );
        }

        AuditLog::record(
            event: 'certificates.signatory_updated',
            auditableType: 'system_settings',
            auditableId: 0,
            oldValues: $old,
            newValues: $this->globalSettings(),
            reason: 'Administrator updated global certificate organisation and signatory configuration.',
            actor: $admin,
        );
    }

    /**
     * Resolve SVG/logo data URI cleanly for DomPDF & HTML certificate previews without requiring ext-gd.
     */
    public function resolveLogoDataUri(?string $logoPath = null): ?string
    {
        $configured = $logoPath ?: ($this->globalSettings()['logo_path'] ?? self::DEFAULT_LOGO_PATH);

        $publicFullPath = public_path($configured);
        if (! is_file($publicFullPath)) {
            $publicFullPath = public_path('storage/'.$configured);
        }
        if (! is_file($publicFullPath)) {
            $publicFullPath = storage_path('app/public/'.$configured);
        }
        if (is_file($publicFullPath)) {
            $ext = strtolower(pathinfo($publicFullPath, PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                return 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($publicFullPath));
            }
        }

        $fallbackSvg = public_path(self::DEFAULT_LOGO_PATH);
        if (is_file($fallbackSvg)) {
            return 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($fallbackSvg));
        }

        return null;
    }

    public function resolveSignatureDataUri(): ?string
    {
        $path = (string) ($this->globalSettings()['signature_path'] ?? '');
        if ($path === '') {
            return null;
        }

        $publicFullPath = public_path('storage/'.$path);
        if (! is_file($publicFullPath)) {
            $publicFullPath = storage_path('app/public/'.$path);
        }
        if (! is_file($publicFullPath)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($publicFullPath, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => null,
        };

        return $mime ? 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($publicFullPath)) : null;
    }

    /**
     * Issue a new certificate and render & store its PDF securely on the private R2 disk.
     */
    public function issueCertificate(
        User $admin,
        User $trainee,
        string $recordType,
        int $recordId,
        Carbon|string $completionDate,
        ?string $notes = null,
    ): Certificate {
        if (! $trainee->isActive()) {
            throw new DomainException('Certificates can only be issued to active trainee accounts.');
        }

        if (! in_array($recordType, ['session', 'module'], true)) {
            throw new DomainException('Training record type must be either a physical session or a module.');
        }

        $parsedCompletionDate = $completionDate instanceof Carbon
            ? $completionDate->copy()->startOfDay()
            : Carbon::parse($completionDate)->startOfDay();

        $trainingSession = null;
        $module = null;
        $booking = null;
        $entitlement = null;

        if ($recordType === 'session') {
            /** @var TrainingSession $trainingSession */
            $trainingSession = TrainingSession::query()->with('module')->findOrFail($recordId);

            if ($trainingSession->status === SessionStatus::Cancelled) {
                throw new DomainException('Certificates cannot be issued for a cancelled training session.');
            }
            if ($trainingSession->status !== SessionStatus::Completed) {
                throw new DomainException('A physical-session certificate requires official administrator completion.');
            }

            $module = $trainingSession->module;
            $booking = Booking::query()
                ->where('user_id', $trainee->id)
                ->where('training_session_id', $trainingSession->id)
                ->where('status', BookingStatus::Confirmed->value)
                ->first();
            if ($booking === null) {
                throw new DomainException('The trainee does not have a confirmed booking for this session.');
            }

            $attendance = \App\Models\Attendance::query()
                ->where('booking_id', $booking->id)
                ->where('attendance_status', AttendanceStatus::Present->value)
                ->where('review_status', AttendanceReviewStatus::Verified->value)
                ->first();
            if ($attendance === null) {
                throw new DomainException('A physical-session certificate requires verified present attendance.');
            }

            $codeSnapshot = $trainingSession->session_code;
            $titleSnapshot = $trainingSession->title;
        } else {
            /** @var Module $module */
            $module = Module::query()->findOrFail($recordId);

            if ($module->status === ModuleStatus::Archived) {
                throw new DomainException('Certificates cannot be issued for an archived training module.');
            }

            $entitlement = ModuleEntitlement::query()
                ->where('user_id', $trainee->id)
                ->where('module_id', $module->id)
                ->where('status', EntitlementStatus::Active->value)
                ->first();
            if ($entitlement === null) {
                throw new DomainException('The trainee must have an active module entitlement before a module certificate can be approved.');
            }

            $codeSnapshot = $module->code;
            $titleSnapshot = $module->title;
        }

        $settings = $this->globalSettings();
        $now = now();

        /** @var Certificate $certificate */
        $certificate = DB::transaction(function () use (
            $admin,
            $trainee,
            $recordType,
            $trainingSession,
            $module,
            $booking,
            $entitlement,
            $codeSnapshot,
            $titleSnapshot,
            $parsedCompletionDate,
            $settings,
            $notes,
            $now,
        ): Certificate {
            // Prevent duplicate active (non-revoked) certificates for the exact same trainee + training record
            $duplicateQuery = Certificate::query()
                ->where('user_id', $trainee->id)
                ->where('record_type', $recordType)
                ->whereIn('status', [
                    CertificateStatus::Pending->value,
                    CertificateStatus::Issued->value,
                ]);

            if ($recordType === 'session') {
                $duplicateQuery->where('training_session_id', $trainingSession->id);
            } else {
                $duplicateQuery->where('module_id', $module->id);
            }

            if ($duplicateQuery->lockForUpdate()->exists()) {
                throw new DomainException(
                    "An active certificate already exists for {$trainee->name} on {$codeSnapshot}."
                );
            }

            $certificateNumber = Certificate::generateCertificateNumber();
            $verificationToken = bin2hex(random_bytes(32));

            $certificate = Certificate::query()->create([
                'certificate_number' => $certificateNumber,
                'verification_token' => $verificationToken,
                'user_id' => $trainee->id,
                'record_type' => $recordType,
                'training_session_id' => $trainingSession?->id,
                'module_id' => $module->id,
                'booking_id' => $booking?->id,
                'module_entitlement_id' => $entitlement?->id,
                'status' => CertificateStatus::Pending,
                'trainee_name_snapshot' => $trainee->name,
                'training_code_snapshot' => $codeSnapshot,
                'training_title_snapshot' => $titleSnapshot,
                'completion_date' => $parsedCompletionDate->toDateString(),
                'issued_at' => $now,
                'issuing_organisation_snapshot' => $settings['issuing_organisation'],
                'signatory_name_snapshot' => $settings['signatory_name'],
                'signatory_title_snapshot' => $settings['signatory_title'],
                'pdf_storage_disk' => 'certificates',
                'issued_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => $now,
                'pdf_generation_status' => PdfGenerationStatus::Queued,
                'pdf_generation_attempts' => 0,
                'notes' => $notes,
            ]);

            AuditLog::record(
                event: 'certificate.approved',
                auditableType: Certificate::class,
                auditableId: $certificate->id,
                oldValues: null,
                newValues: [
                    'certificate_number' => $certificate->certificate_number,
                    'user_id' => $trainee->id,
                    'record_type' => $recordType,
                    'training_session_id' => $trainingSession?->id,
                    'module_id' => $module->id,
                    'completion_date' => $parsedCompletionDate->toDateString(),
                ],
                reason: $notes ?: 'Administrator approved certificate and queued PDF generation.',
                actor: $admin,
            );

            DB::afterCommit(fn () => GenerateCertificatePdfJob::dispatch($certificate->id));

            return $certificate->fresh(['user', 'trainingSession', 'module', 'issuer']);
        });

        return $certificate->fresh(['user', 'trainingSession', 'module', 'issuer']);
    }

    public function generateApprovedPdf(int $certificateId): Certificate
    {
        try {
            $certificate = DB::transaction(function () use ($certificateId): Certificate {
                $certificate = Certificate::query()
                    ->whereKey($certificateId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($certificate->status === CertificateStatus::Revoked || $certificate->status === CertificateStatus::Issued) {
                    return $certificate;
                }

                $certificate->update([
                    'pdf_generation_status' => PdfGenerationStatus::Generating,
                    'pdf_generation_attempts' => $certificate->pdf_generation_attempts + 1,
                    'pdf_generation_error' => null,
                ]);

                $this->renderAndStorePdfLocked($certificate);
                $certificate->update([
                    'status' => CertificateStatus::Issued,
                    'issued_at' => now(),
                    'pdf_generation_status' => PdfGenerationStatus::Completed,
                ]);

                AuditLog::record(
                    event: 'certificate.issued',
                    auditableType: Certificate::class,
                    auditableId: $certificate->id,
                    oldValues: ['status' => CertificateStatus::Pending->value],
                    newValues: [
                        'status' => CertificateStatus::Issued->value,
                        'pdf_generation_status' => PdfGenerationStatus::Completed->value,
                    ],
                    reason: 'Approved certificate PDF generation completed.',
                    actor: $certificate->approved_by ? User::find($certificate->approved_by) : null,
                );

                return $certificate->fresh(['user']);
            });
        } catch (\Throwable $e) {
            Certificate::query()->whereKey($certificateId)->update([
                'pdf_generation_status' => PdfGenerationStatus::Failed,
                'pdf_generation_error' => mb_substr($e->getMessage(), 0, 2000),
            ]);
            throw $e;
        }

        if ($certificate->user) {
            app(EtmsNotificationService::class)->notifyUser(
                user: $certificate->user,
                eventType: 'certificate.issued',
                title: "Certificate Issued: {$certificate->training_title_snapshot}",
                message: "Your completion certificate {$certificate->certificate_number} is ready to view and download.",
                actionUrl: route('portal.certificates.show', $certificate, false),
                actionLabel: 'View & Download Certificate',
                details: ['Certificate Number' => $certificate->certificate_number],
                reference: $certificate->certificate_number,
            );
        }

        return $certificate;
    }

    /**
     * Render a PDF using the immutable snapshot attributes and store it on private R2 storage.
     */
    public function renderPdfBytes(Certificate $certificate, ?string $logoPath = null): string
    {
        $logoDataUri = $this->resolveLogoDataUri($logoPath);

        return Pdf::loadView('pdf.certificate', [
            'certificate' => $certificate,
            'logoDataUri' => $logoDataUri,
            'signatureDataUri' => $this->resolveSignatureDataUri(),
        ])
            ->setPaper('a4', 'landscape')
            ->output();
    }

    /**
     * Regenerate the stored PDF binary for an existing certificate without altering its snapshots.
     */
    public function regeneratePdf(Certificate $certificate, User $actor): Certificate
    {
        $this->renderAndStorePdfLocked($certificate);

        AuditLog::record(
            event: 'certificate.pdf_regenerated',
            auditableType: Certificate::class,
            auditableId: $certificate->id,
            oldValues: null,
            newValues: [
                'pdf_storage_key' => $certificate->pdf_storage_key,
                'pdf_checksum_sha256' => $certificate->pdf_checksum_sha256,
            ],
            reason: 'Certificate PDF regenerated on private R2 storage.',
            actor: $actor,
        );

        return $certificate->fresh();
    }

    /**
     * Revoke an issued certificate with a mandatory administrator reason (`chk_certificates_revoked`).
     */
    public function revokeCertificate(Certificate $certificate, User $admin, string $reason): Certificate
    {
        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new DomainException('A revocation reason is mandatory when revoking a certificate.');
        }

        return DB::transaction(function () use ($certificate, $admin, $trimmedReason): Certificate {
            /** @var Certificate $lockedCert */
            $lockedCert = Certificate::query()
                ->whereKey($certificate->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCert->status === CertificateStatus::Revoked) {
                throw new DomainException('This certificate has already been revoked.');
            }

            $oldStatus = $lockedCert->status->value;
            $now = now();

            $lockedCert->update([
                'status' => CertificateStatus::Revoked,
                'revoked_by' => $admin->id,
                'revoked_at' => $now,
                'revocation_reason' => $trimmedReason,
            ]);

            AuditLog::record(
                event: 'certificate.revoked',
                auditableType: Certificate::class,
                auditableId: $lockedCert->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => CertificateStatus::Revoked->value,
                    'revoked_at' => $now->toIso8601String(),
                ],
                reason: $trimmedReason,
                actor: $admin,
            );

            $freshCertificate = $lockedCert->fresh(['user', 'revoker']);

            DB::afterCommit(function () use ($freshCertificate, $trimmedReason): void {
                if (! $freshCertificate?->user) {
                    return;
                }

                app(EtmsNotificationService::class)->notifyUser(
                    user: $freshCertificate->user,
                    eventType: 'certificate.revoked',
                    title: "Certificate Revoked: {$freshCertificate->certificate_number}",
                    message: "Certificate {$freshCertificate->certificate_number} ({$freshCertificate->training_title_snapshot}) has been revoked and is no longer valid for download.",
                    actionUrl: route('portal.certificates.show', $freshCertificate, false),
                    actionLabel: 'View Certificate Notice',
                    details: [
                        'Certificate Number' => $freshCertificate->certificate_number,
                        'Training' => $freshCertificate->training_title_snapshot,
                        'Revocation Reason' => $trimmedReason,
                    ],
                    reference: $freshCertificate->certificate_number,
                );
            });

            return $freshCertificate;
        });
    }

    protected function renderAndStorePdfLocked(Certificate $certificate, ?string $logoPath = null): void
    {
        $pdfBytes = $this->renderPdfBytes($certificate, $logoPath);
        $year = $certificate->issued_at?->format('Y') ?? now()->format('Y');
        $storageDisk = $certificate->pdf_storage_disk ?: 'certificates';
        $storageKey = "{$year}/{$certificate->certificate_number}.pdf";

        Storage::disk($storageDisk)->put($storageKey, $pdfBytes);

        $certificate->update([
            'pdf_storage_disk' => $storageDisk,
            'pdf_storage_key' => $storageKey,
            'pdf_checksum_sha256' => hash('sha256', $pdfBytes),
            'pdf_file_size_bytes' => strlen($pdfBytes),
        ]);
    }
}
