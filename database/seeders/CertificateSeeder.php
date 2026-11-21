<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\SystemSetting;
use App\Models\TrainingSession;
use App\Models\User;
use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Services\Certificate\CertificateService;
use Illuminate\Database\Seeder;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->first();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->first();

        if (! $admin) {
            return;
        }

        // Seed default global certificate signatory & organisation settings (D-027)
        SystemSetting::setValue(
            key: 'certificates.issuing_organisation',
            value: CertificateService::DEFAULT_ORGANISATION,
            group: 'certificates',
            type: 'string',
            description: 'Organisation name printed on issued training certificates (D-027).',
            isPublic: true,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'certificates.signatory_name',
            value: CertificateService::DEFAULT_SIGNATORY_NAME,
            group: 'certificates',
            type: 'string',
            description: 'Authorised signatory full name printed on issued certificates (D-027).',
            isPublic: false,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'certificates.signatory_title',
            value: CertificateService::DEFAULT_SIGNATORY_TITLE,
            group: 'certificates',
            type: 'string',
            description: 'Authorised signatory designation printed on issued certificates (D-027).',
            isPublic: false,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'certificates.logo_path',
            value: CertificateService::DEFAULT_LOGO_PATH,
            group: 'certificates',
            type: 'string',
            description: 'Logo asset path rendered on official PDF certificates.',
            isPublic: false,
            updatedBy: $admin->id,
        );

        if (! $trainee) {
            return;
        }

        $certificateSession = TrainingSession::query()->where('session_code', 'SES-2026-003')->first();
        if ($certificateSession) {
            $certificateSession->update([
                'status' => SessionStatus::Completed,
                'completed_at' => $certificateSession->completed_at ?? now()->subDay(),
                'completed_by' => $certificateSession->completed_by ?? $admin->id,
                'completion_notes' => $certificateSession->completion_notes ?: 'Seeded demo session completion.',
                'completion_submitted_by' => $certificateSession->completion_submitted_by ?? $admin->id,
                'completion_submitted_at' => $certificateSession->completion_submitted_at ?? now()->subDays(2),
            ]);

            $booking = Booking::query()
                ->where('user_id', $trainee->id)
                ->where('training_session_id', $certificateSession->id)
                ->where('status', 'confirmed')
                ->firstOrFail();

            Attendance::query()
                ->updateOrCreate(
                    [
                        'training_session_id' => $certificateSession->id,
                        'booking_id' => $booking->id,
                    ],
                    [
                        'user_id' => $trainee->id,
                        'attendance_status' => AttendanceStatus::Present,
                        'review_status' => AttendanceReviewStatus::Verified,
                        'recorded_by' => $admin->id,
                        'recorded_at' => now()->subDay(),
                        'submitted_by' => $admin->id,
                        'submitted_at' => now()->subDay(),
                        'verified_by' => $admin->id,
                        'verified_at' => now()->subDay(),
                    ],
                );

            $alreadyExists = Certificate::query()
                ->where('user_id', $trainee->id)
                ->where('record_type', 'session')
                ->where('training_session_id', $certificateSession->id)
                ->exists();

            if (! $alreadyExists) {
                app(CertificateService::class)->issueCertificate(
                    admin: $admin,
                    trainee: $trainee,
                    recordType: 'session',
                    recordId: $certificateSession->id,
                    completionDate: now()->subDay()->toDateString(),
                    notes: 'Seeded demo completion certificate.',
                );
            }

            $certificateSession->update([
                'status' => SessionStatus::Published,
                'completed_at' => null,
                'completed_by' => null,
                'completion_notes' => null,
                'completion_submitted_by' => null,
                'completion_submitted_at' => null,
            ]);

            Attendance::query()
                ->where('training_session_id', $certificateSession->id)
                ->where('user_id', $trainee->id)
                ->update([
                    'attendance_status' => AttendanceStatus::Pending,
                    'review_status' => AttendanceReviewStatus::Draft,
                    'recorded_by' => null,
                    'recorded_at' => null,
                    'submitted_by' => null,
                    'submitted_at' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
        }
    }
}
