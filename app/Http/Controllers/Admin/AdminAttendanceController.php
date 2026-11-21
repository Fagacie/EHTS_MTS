<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Attendance\AttendanceReviewService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $reviewStatus = (string) $request->query('review_status', '');
        $attendanceStatus = (string) $request->query('attendance_status', '');

        $attendance = Attendance::query()
            ->with(['trainingSession:id,session_code,title', 'user:id,name,email'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query
                    ->whereHas('trainingSession', fn ($session) => $session
                        ->where('session_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->when(AttendanceReviewStatus::tryFrom($reviewStatus), fn ($query) => $query->where('review_status', $reviewStatus))
            ->when(AttendanceStatus::tryFrom($attendanceStatus), fn ($query) => $query->where('attendance_status', $attendanceStatus))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (Attendance $record): array => [
                'id' => $record->id,
                'session' => $record->trainingSession?->session_code,
                'sessionTitle' => $record->trainingSession?->title,
                'participant' => $record->user?->name,
                'email' => $record->user?->email,
                'attendanceStatus' => $record->attendance_status?->value,
                'attendanceLabel' => $record->attendance_status?->value ? ucfirst($record->attendance_status->value) : '—',
                'reviewStatus' => $record->review_status?->value,
                'reviewLabel' => $record->review_status?->label(),
                'submittedAt' => $record->submitted_at?->toIso8601String(),
                'verifiedAt' => $record->verified_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Attendance/Index', [
            'attendance' => $attendance,
            'filters' => [
                'q' => $search,
                'review_status' => $reviewStatus,
                'attendance_status' => $attendanceStatus,
            ],
            'reviewStatuses' => collect(AttendanceReviewStatus::cases())->map(fn (AttendanceReviewStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
            'attendanceStatuses' => collect(AttendanceStatus::cases())->map(fn (AttendanceStatus $status): array => [
                'value' => $status->value,
                'label' => ucfirst($status->value),
            ])->values()->all(),
        ]);
    }

    public function verify(Request $request, Attendance $attendance, AttendanceReviewService $service): RedirectResponse
    {
        $data = $request->validate(['verification_notes' => ['nullable', 'string', 'max:1000']]);

        try {
            $service->verify($attendance, $request->user(), $data['verification_notes'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['attendance' => $exception->getMessage()]);
        }

        return back()->with('success', 'Attendance verified.');
    }

    public function requestCorrection(Request $request, Attendance $attendance, AttendanceReviewService $service): RedirectResponse
    {
        $data = $request->validate(['verification_notes' => ['required', 'string', 'max:1000']]);

        try {
            $service->requestCorrection($attendance, $request->user(), $data['verification_notes']);
        } catch (DomainException $exception) {
            return back()->withErrors(['attendance' => $exception->getMessage()]);
        }

        return back()->with('success', 'Attendance returned for correction.');
    }
}
