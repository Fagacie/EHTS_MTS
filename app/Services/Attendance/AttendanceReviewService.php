<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class AttendanceReviewService
{
    public function verify(Attendance $attendance, User $admin, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($attendance, $admin, $notes): Attendance {
            $record = Attendance::query()
                ->with('trainingSession')
                ->whereKey($attendance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($record->trainingSession?->status !== SessionStatus::Completed) {
                throw new DomainException('Attendance can only be verified after the session is officially completed.');
            }

            if ($record->review_status !== AttendanceReviewStatus::Submitted) {
                throw new DomainException('Only submitted attendance records can be verified.');
            }

            if ($record->attendance_status === AttendanceStatus::Pending) {
                throw new DomainException('Attendance must be marked before it can be verified.');
            }

            $record->update([
                'review_status' => AttendanceReviewStatus::Verified,
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'verification_notes' => $notes,
            ]);

            AuditLog::record(
                event: 'attendance.verified',
                auditableType: Attendance::class,
                auditableId: $record->id,
                oldValues: ['review_status' => AttendanceReviewStatus::Submitted->value],
                newValues: [
                    'review_status' => AttendanceReviewStatus::Verified->value,
                    'attendance_status' => $record->attendance_status->value,
                    'verified_by' => $admin->id,
                ],
                reason: $notes ?: 'Administrator verified submitted attendance.',
                actor: $admin,
            );

            return $record->fresh(['trainingSession', 'user', 'verifier']);
        });
    }

    public function requestCorrection(Attendance $attendance, User $admin, string $notes): Attendance
    {
        $notes = trim($notes);
        if ($notes === '') {
            throw new DomainException('Correction notes are required.');
        }

        return DB::transaction(function () use ($attendance, $admin, $notes): Attendance {
            $record = Attendance::query()
                ->whereKey($attendance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($record->review_status, [
                AttendanceReviewStatus::Submitted,
                AttendanceReviewStatus::Verified,
            ], true)) {
                throw new DomainException('Only submitted or verified attendance can be returned for correction.');
            }

            $oldStatus = $record->review_status->value;
            $record->update([
                'review_status' => AttendanceReviewStatus::CorrectionRequested,
                'verified_by' => null,
                'verified_at' => null,
                'verification_notes' => $notes,
            ]);

            AuditLog::record(
                event: 'attendance.correction_requested',
                auditableType: Attendance::class,
                auditableId: $record->id,
                oldValues: ['review_status' => $oldStatus],
                newValues: [
                    'review_status' => AttendanceReviewStatus::CorrectionRequested->value,
                    'verification_notes' => $notes,
                ],
                reason: $notes,
                actor: $admin,
            );

            return $record->fresh(['trainingSession', 'user']);
        });
    }
}
