<?php

namespace App\Http\Controllers\Instructor;

use App\Enums\AttendanceReviewStatus;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SessionRecording;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorDashboardController extends Controller
{
    public function __invoke(Request $request): View
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

        return view('instructor.dashboard', [
            'user' => $user,
            'assignedSessions' => $assignedSessions,
            'pendingAttendanceCount' => $pendingAttendanceCount,
            'submittedRecordingsCount' => $submittedRecordingsCount,
        ]);
    }
}
