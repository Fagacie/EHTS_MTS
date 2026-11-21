<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use App\Enums\PdfGenerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class Certificate extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'certificate_number',
        'verification_token',
        'user_id',
        'record_type',
        'training_session_id',
        'module_id',
        'booking_id',
        'module_entitlement_id',
        'status',
        'trainee_name_snapshot',
        'training_code_snapshot',
        'training_title_snapshot',
        'completion_date',
        'issued_at',
        'issuing_organisation_snapshot',
        'signatory_name_snapshot',
        'signatory_title_snapshot',
        'pdf_storage_disk',
        'pdf_storage_key',
        'pdf_checksum_sha256',
        'pdf_file_size_bytes',
        'pdf_generation_status',
        'pdf_generation_attempts',
        'pdf_generation_error',
        'issued_by',
        'approved_by',
        'approved_at',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CertificateStatus::class,
            'completion_date' => 'date',
            'pdf_generation_status' => PdfGenerationStatus::class,
            'pdf_generation_attempts' => 'integer',
            'issued_at' => 'datetime',
            'approved_at' => 'datetime',
            'revoked_at' => 'datetime',
            'pdf_file_size_bytes' => 'integer',
        ];
    }

    public static function generateCertificateNumber(): string
    {
        $year = now()->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('Y');

        do {
            $nextSeq = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $candidate = "EHT-{$year}-{$nextSeq}";
        } while (self::query()->where('certificate_number', $candidate)->exists());

        return $candidate;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function moduleEntitlement(): BelongsTo
    {
        return $this->belongsTo(ModuleEntitlement::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isIssued(): bool
    {
        return $this->status === CertificateStatus::Issued;
    }

    public function isRevoked(): bool
    {
        return $this->status === CertificateStatus::Revoked;
    }

    public function recordTypeLabel(): string
    {
        return $this->record_type === 'session' ? 'Physical Training Session' : 'Training Module';
    }

    public function formattedFileSize(): string
    {
        $bytes = max(0, (int) $this->pdf_file_size_bytes);

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function temporaryDownloadUrl(int $minutes = 15): string
    {
        return URL::temporarySignedRoute(
            'certificates.download',
            now()->addMinutes($minutes),
            ['certificate' => $this->certificate_number],
        );
    }

    /**
     * Retrieve immutable issuance & revocation history from `audit_logs`.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AuditLog>
     */
    public function historyLogs()
    {
        return AuditLog::query()
            ->with('actor')
            ->where('auditable_type', self::class)
            ->where('auditable_id', $this->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
