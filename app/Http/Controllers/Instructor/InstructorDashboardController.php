<?php

namespace App\Http\Controllers\Instructor;

use App\Enums\AttendanceReviewStatus;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Support\InstructorPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InstructorDashboardController extends Controller
{
    public function __invoke(Request $request): InertiaResponse
    {
        $user = $request->user()->load(['roles', 'instructorProfile']);

        $assignedSessions = $user->assignedSessions()
            ->with(['module.category'])
            ->withCount([
                'bookings as confirmed_participants_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed->value),
                'recordings',
            ])
            ->orderBy('starts_at')
            ->get();

        $assignedSessionIds = $assignedSessions->pluck('id')->all();

        $pendingAttendanceCount = empty($assignedSessionIds)
            ? 0
            : Attendance::query()
                ->whereIn('training_session_id', $assignedSessionIds)
                ->whereIn('review_status', [
                    AttendanceReviewStatus::Draft->value,
                    AttendanceReviewStatus::CorrectionRequested->value,
                ])
                ->distinct('training_session_id')
                ->count('training_session_id');

        $submittedRecordingsCount = empty($assignedSessionIds)
            ? 0
            : SessionRecording::query()
                ->whereIn('training_session_id', $assignedSessionIds)
                ->count();

        return Inertia::render('Instructor/Dashboard', [
            'instructor' => [
                'displayName' => $user->instructorProfile?->display_name ?: $user->name,
                'email' => $user->email,
                'isPublic' => (bool) $user->instructorProfile?->is_public,
                'publicProfileUrl' => $user->instructorProfile?->is_public ? route('instructors.show', $user->instructorProfile) : null,
            ],
            'kpis' => [
                'assignedSessionsCount' => $assignedSessions->count(),
                'pendingAttendanceCount' => $pendingAttendanceCount,
                'submittedRecordingsCount' => $submittedRecordingsCount,
            ],
            'assignedSessions' => $assignedSessions->map(
                static fn (TrainingSession $session): array => InstructorPresenter::sessionCard($session, $user),
            )->values()->all(),
        ]);
    }
}
