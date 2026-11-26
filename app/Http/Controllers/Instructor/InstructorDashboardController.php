<?php

namespace App\Http\Controllers\Instructor;

use App\Enums\AttendanceReviewStatus;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SessionMaterial;
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
        $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');
        $now = now($tz);

        $assignedSessions = $user->assignedSessions()
            ->with(['module.category', 'instructors:id,name'])
            ->withCount([
                'bookings as confirmed_participants_count' => fn ($q) => $q->where('status', BookingStatus::Confirmed->value),
                'recordings',
                'materials',
            ])
            ->orderBy('starts_at')
            ->get();

        $assignedSessionIds = $assignedSessions->pluck('id')->all();

        $pendingAttendanceSessionIds = empty($assignedSessionIds)
            ? []
            : Attendance::query()
                ->whereIn('training_session_id', $assignedSessionIds)
                ->whereIn('review_status', [
                    AttendanceReviewStatus::Draft->value,
                    AttendanceReviewStatus::CorrectionRequested->value,
                ])
                ->distinct()
                ->pluck('training_session_id')
                ->all();

        $submittedRecordingsCount = empty($assignedSessionIds)
            ? 0
            : SessionRecording::query()
                ->whereIn('training_session_id', $assignedSessionIds)
                ->count();

        $submittedMaterialsCount = empty($assignedSessionIds)
            ? 0
            : SessionMaterial::query()
                ->whereIn('training_session_id', $assignedSessionIds)
                ->count();

        $upcomingSessionsCount = $assignedSessions->filter(
            fn (TrainingSession $s): bool => $s->status !== SessionStatus::Cancelled
                && $s->status !== SessionStatus::Completed
                && $s->starts_at?->timezone($tz)->gte($now->copy()->startOfDay())
        )->count();

        $completedSessionsCount = $assignedSessions->filter(
            fn (TrainingSession $s): bool => $s->status === SessionStatus::Completed
        )->count();

        $totalConfirmedParticipants = (int) $assignedSessions
            ->reject(fn (TrainingSession $s): bool => $s->status === SessionStatus::Cancelled)
            ->sum('confirmed_participants_count');

        $calendarSessions = $assignedSessions
            ->filter(fn (TrainingSession $s): bool => $s->starts_at !== null)
            ->map(static function (TrainingSession $s) use ($tz): array {
                $start = $s->starts_at->timezone($tz);
                $end = $s->ends_at?->timezone($tz);

                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'sessionCode' => $s->session_code,
                    'startDate' => $start->format('Y-m-d'),
                    'timeLabel' => $end ? $start->format('g:i A').' – '.$end->format('g:i A') : $start->format('g:i A'),
                    'venue' => collect([$s->venue_name, $s->venue_city])->filter()->implode(', ') ?: 'Online / TBA',
                    'instructors' => $s->instructors->pluck('name')->values()->all(),
                    'confirmedBookings' => (int) $s->confirmed_participants_count,
                    'capacity' => (int) $s->capacity,
                    'statusValue' => $s->status->value,
                    'status' => $s->status->label(),
                    'detailUrl' => route('instructor.sessions.show', $s),
                ];
            })
            ->values()
            ->all();

        $actionItems = [];
        foreach ($assignedSessions as $s) {
            if (in_array($s->id, $pendingAttendanceSessionIds, true)) {
                $actionItems[] = [
                    'id' => "att-{$s->id}",
                    'label' => 'Submit attendance',
                    'detail' => "{$s->session_code} — {$s->title}",
                    'tone' => 'warning',
                    'href' => route('instructor.sessions.attendance', $s),
                ];
            }
            if ($s->status === SessionStatus::Completed && empty($s->completion_notes)) {
                $actionItems[] = [
                    'id' => "comp-{$s->id}",
                    'label' => 'Add completion notes',
                    'detail' => "{$s->session_code} — {$s->title}",
                    'tone' => 'neutral',
                    'href' => route('instructor.sessions.completion', $s),
                ];
            }
        }

        return Inertia::render('Instructor/Dashboard', [
            'instructor' => [
                'displayName' => $user->instructorProfile?->display_name ?: $user->name,
                'email' => $user->email,
                'isPublic' => (bool) $user->instructorProfile?->is_public,
                'publicProfileUrl' => $user->instructorProfile?->is_public ? route('instructors.show', $user->instructorProfile) : null,
            ],
            'kpis' => [
                'assignedSessionsCount' => $assignedSessions->count(),
                'upcomingSessionsCount' => $upcomingSessionsCount,
                'completedSessionsCount' => $completedSessionsCount,
                'totalConfirmedParticipants' => $totalConfirmedParticipants,
                'pendingAttendanceCount' => count($pendingAttendanceSessionIds),
                'submittedRecordingsCount' => $submittedRecordingsCount,
                'submittedMaterialsCount' => $submittedMaterialsCount,
            ],
            'assignedSessions' => $assignedSessions->map(
                static fn (TrainingSession $session): array => InstructorPresenter::sessionCard($session, $user),
            )->values()->all(),
            'calendarSessions' => $calendarSessions,
            'todayYmd' => $now->format('Y-m-d'),
            'actionItems' => array_slice($actionItems, 0, 6),
        ]);
    }
}
