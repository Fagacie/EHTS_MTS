<?php

namespace App\Http\Controllers\Instructor;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EncodingStatus;
use App\Enums\RecordingStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\InstructorPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InstructorSessionController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $statusFilter = trim((string) $request->query('status', ''));

        $sessionsQuery = $user->assignedSessions()
            ->with(['module.category'])
            ->withCount([
                'bookings as confirmed_participants_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed->value),
                'recordings',
            ])
            ->orderBy('starts_at');

        if ($statusFilter !== '') {
            $sessionsQuery->where('training_sessions.status', $statusFilter);
        }

        $sessions = $sessionsQuery->get();

        return Inertia::render('Instructor/Sessions/Index', [
            'sessions' => $sessions->map(
                static fn (TrainingSession $session): array => InstructorPresenter::sessionCard($session, $user),
            )->values()->all(),
            'statusFilter' => $statusFilter,
        ]);
    }

    public function show(Request $request, TrainingSession $session): InertiaResponse
    {
        $this->ensureAssigned($request->user(), $session);
        $this->syncConfirmedRosterAttendance($session);

        $session->load([
            'module.category',
            'instructors.instructorProfile',
            'confirmedBookings.user.customerProfile',
            'confirmedBookings.attendance',
            'recordings',
        ]);

        return Inertia::render('Instructor/Sessions/Show', [
            'session' => InstructorPresenter::sessionCard($session, $request->user()),
            'roster' => $session->confirmedBookings->map(
                static fn (Booking $booking): array => InstructorPresenter::rosterParticipant($booking),
            )->values()->all(),
        ]);
    }

    public function attendance(Request $request, TrainingSession $session): InertiaResponse
    {
        $this->ensureAssigned($request->user(), $session);
        $this->syncConfirmedRosterAttendance($session);

        $session->load([
            'module.category',
            'confirmedBookings.user.customerProfile',
            'confirmedBookings.attendance',
        ]);

        $attendances = Attendance::query()
            ->with(['user.customerProfile', 'booking'])
            ->where('training_session_id', $session->id)
            ->orderBy('id')
            ->get();

        $allMarked = $attendances->isNotEmpty()
            && $attendances->every(fn (Attendance $a) => $a->attendance_status !== AttendanceStatus::Pending);

        $isLockedVerified = $attendances->isNotEmpty()
            && $attendances->every(fn (Attendance $a) => $a->review_status === AttendanceReviewStatus::Verified);

        return Inertia::render('Instructor/Sessions/Attendance', [
            'session' => InstructorPresenter::sessionCard($session, $request->user()),
            'attendances' => $attendances->map(
                static fn (Attendance $attendance): array => InstructorPresenter::attendanceRecord($attendance),
            )->values()->all(),
            'allMarked' => $allMarked,
            'isLockedVerified' => $isLockedVerified,
            'updateAttendanceUrl' => route('instructor.sessions.attendance.update', $session),
            'submitAttendanceUrl' => route('instructor.sessions.attendance.submit', $session),
        ]);
    }

    public function updateAttendance(Request $request, TrainingSession $session): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAssigned($user, $session);
        $this->syncConfirmedRosterAttendance($session);

        $validated = $request->validate([
            'records' => ['required', 'array'],
            'records.*.attendance_status' => ['required', 'in:pending,present,absent,excused'],
            'records.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $now = now();

        DB::transaction(function () use ($session, $user, $validated, $now): void {
            foreach ($validated['records'] as $attendanceId => $row) {
                /** @var Attendance|null $attendance */
                $attendance = Attendance::query()
                    ->where('training_session_id', $session->id)
                    ->whereKey((int) $attendanceId)
                    ->first();

                if (! $attendance || $attendance->review_status === AttendanceReviewStatus::Verified) {
                    continue;
                }

                $newStatus = AttendanceStatus::from($row['attendance_status']);

                $payload = [
                    'attendance_status' => $newStatus,
                    'remarks' => $row['remarks'] ?? null,
                ];

                if ($newStatus !== AttendanceStatus::Pending) {
                    $payload['recorded_by'] = $user->id;
                    $payload['recorded_at'] = $now;
                }

                $attendance->update($payload);
            }

            AuditLog::record(
                event: 'attendance.recorded',
                auditableType: TrainingSession::class,
                auditableId: $session->id,
                oldValues: null,
                newValues: ['updated_rows' => count($validated['records'])],
                reason: 'Instructor saved draft attendance marks.',
                actor: $user,
            );
        });

        return redirect()
            ->route('instructor.sessions.attendance', $session)
            ->with('status', 'Attendance draft saved successfully.');
    }

    public function submitAttendance(Request $request, TrainingSession $session): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAssigned($user, $session);
        $this->syncConfirmedRosterAttendance($session);

        $attendances = Attendance::query()
            ->where('training_session_id', $session->id)
            ->get();

        if ($attendances->isEmpty()) {
            return redirect()
                ->route('instructor.sessions.attendance', $session)
                ->with('error', 'Cannot submit attendance because there are no confirmed participants on the roster.');
        }

        $unmarkedCount = $attendances
            ->filter(fn (Attendance $a) => $a->attendance_status === AttendanceStatus::Pending)
            ->count();

        if ($unmarkedCount > 0) {
            return redirect()
                ->route('instructor.sessions.attendance', $session)
                ->with('error', "Every participant must be marked (Present, Absent, or Excused) before submitting attendance for administrator verification ({$unmarkedCount} still pending).");
        }

        $now = now();

        DB::transaction(function () use ($session, $user, $attendances, $now): void {
            foreach ($attendances as $attendance) {
                if ($attendance->review_status === AttendanceReviewStatus::Verified) {
                    continue;
                }

                $attendance->update([
                    'recorded_by' => $attendance->recorded_by ?? $user->id,
                    'recorded_at' => $attendance->recorded_at ?? $now,
                    'review_status' => AttendanceReviewStatus::Submitted,
                    'submitted_by' => $user->id,
                    'submitted_at' => $now,
                ]);
            }

            AuditLog::record(
                event: 'attendance.submitted',
                auditableType: TrainingSession::class,
                auditableId: $session->id,
                oldValues: null,
                newValues: [
                    'participants_count' => $attendances->count(),
                    'submitted_at' => $now->toIso8601String(),
                ],
                reason: 'Instructor submitted session attendance for administrator verification.',
                actor: $user,
            );
        });

        return redirect()
            ->route('instructor.sessions.attendance', $session)
            ->with('status', 'Session attendance submitted for administrator verification.');
    }

    public function completion(Request $request, TrainingSession $session): InertiaResponse
    {
        $this->ensureAssigned($request->user(), $session);
        $session->load('module.category');

        return Inertia::render('Instructor/Sessions/Completion', [
            'session' => InstructorPresenter::sessionCard($session, $request->user()),
            'completionNotes' => $session->completion_notes,
            'submitUrl' => route('instructor.sessions.completion.store', $session),
        ]);
    }

    public function storeCompletion(Request $request, TrainingSession $session): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAssigned($user, $session);

        $validated = $request->validate([
            'completion_notes' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $session->update([
            'completion_notes' => $validated['completion_notes'],
            'completion_submitted_by' => $user->id,
            'completion_submitted_at' => now(),
            'completed_at' => $session->completed_at ?? now(),
            'completed_by' => $session->completed_by ?? $user->id,
            'updated_by' => $user->id,
        ]);

        AuditLog::record(
            event: 'session.completion_submitted',
            auditableType: TrainingSession::class,
            auditableId: $session->id,
            oldValues: null,
            newValues: [
                'completed_at' => $session->completed_at?->toIso8601String(),
            ],
            reason: 'Instructor submitted session completion report.',
            actor: $user,
        );

        return redirect()
            ->route('instructor.sessions.show', $session)
            ->with('status', 'Session completion report saved and submitted.');
    }

    public function recordings(Request $request, TrainingSession $session): InertiaResponse
    {
        $this->ensureAssigned($request->user(), $session);
        $session->load(['module.category', 'recordings']);

        return Inertia::render('Instructor/Sessions/Recordings', [
            'session' => InstructorPresenter::sessionCard($session, $request->user()),
            'recordings' => $session->recordings->map(
                static fn (SessionRecording $recording): array => InstructorPresenter::recordingItem($recording),
            )->values()->all(),
            'submitUrl' => route('instructor.sessions.recordings.store', $session),
        ]);
    }

    public function storeRecording(Request $request, TrainingSession $session): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAssigned($user, $session);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'video_file' => [
                'required_without:provider_video_id',
                'nullable',
                'file',
                'mimes:mp4,mov,mkv,webm',
                'max:'.((int) config('etms.max_upload_kb', 2097152)),
            ],
            'provider_video_id' => ['nullable', 'string', 'max:150'],
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
        ]);

        $nextSortOrder = ((int) $session->recordings()->max('sort_order')) + 1;
        $uploaded = $request->file('video_file');
        $sourceKey = null;
        $originalFilename = null;
        $fileSizeBytes = null;
        $mimeType = null;

        if ($uploaded !== null) {
            $sourceKey = $uploaded->store("sources/session-{$session->id}", 'recordings');
            $originalFilename = $uploaded->getClientOriginalName();
            $fileSizeBytes = $uploaded->getSize();
            $mimeType = $uploaded->getClientMimeType();
        }

        $streamId = ! empty($validated['provider_video_id'])
            ? (string) $validated['provider_video_id']
            : 'rec-'.strtolower($session->session_code).'-'.strtolower(Str::random(6));

        $recording = SessionRecording::query()->create([
            'training_session_id' => $session->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'provider' => 'local_hls',
            'provider_library_id' => 'local',
            'provider_video_id' => $streamId,
            'storage_disk' => 'recordings',
            'source_storage_key' => $sourceKey,
            'original_filename' => $originalFilename,
            'file_size_bytes' => $fileSizeBytes,
            'mime_type' => $mimeType,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'encoding_status' => $sourceKey !== null ? EncodingStatus::Pending : EncodingStatus::Ready,
            'status' => RecordingStatus::PendingReview,
            'sort_order' => $nextSortOrder,
            'submitted_by' => $user->id,
            'submitted_at' => now(),
        ]);

        if ($sourceKey !== null) {
            ProcessVideoToHlsJob::dispatch(SessionRecording::class, $recording->id);
        }

        AuditLog::record(
            event: 'recording.submitted',
            auditableType: SessionRecording::class,
            auditableId: $recording->id,
            oldValues: null,
            newValues: [
                'training_session_id' => $session->id,
                'title' => $recording->title,
                'provider_video_id' => $recording->provider_video_id,
                'source_storage_key' => $sourceKey,
                'status' => RecordingStatus::PendingReview->value,
            ],
            reason: 'Instructor submitted self-hosted session recording for HLS processing and administrator review.',
            actor: $user,
        );

        return redirect()
            ->route('instructor.sessions.recordings', $session)
            ->with('status', 'Session recording uploaded to server storage and submitted for administrator review.');
    }

    protected function ensureAssigned(User $user, TrainingSession $session): void
    {
        $isAssigned = $session->instructors()
            ->where('users.id', $user->id)
            ->exists();

        abort_unless($isAssigned, 403, 'You are not assigned as an instructor for this training session.');
    }

    protected function syncConfirmedRosterAttendance(TrainingSession $session): void
    {
        $confirmedBookings = $session->bookings()
            ->where('status', BookingStatus::Confirmed->value)
            ->get();

        foreach ($confirmedBookings as $booking) {
            Attendance::query()->firstOrCreate(
                [
                    'training_session_id' => $session->id,
                    'booking_id' => $booking->id,
                ],
                [
                    'user_id' => $booking->user_id,
                    'attendance_status' => AttendanceStatus::Pending,
                    'review_status' => AttendanceReviewStatus::Draft,
                ],
            );
        }
    }
}
