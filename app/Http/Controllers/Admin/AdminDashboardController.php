<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceReviewStatus;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\TrainingSession;
use App\Services\Report\ReportsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request, ReportsService $reports): Response
    {
        $from = $request->string('from')->trim()->toString();
        $to = $request->string('to')->trim()->toString();
        $report = $reports->generate($from ?: null, $to ?: null);

        $range = $reports->resolveDateRange($from ?: null, $to ?: null);
        $start = $range['start_utc'];
        $end = $range['end_utc'];
        $timezone = config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $monthlyStart = $start?->copy()->timezone($timezone)->startOfMonth()
            ?? now($timezone)->subMonths(11)->startOfMonth();
        $monthlyEnd = $end?->copy()->timezone($timezone)->endOfMonth()
            ?? now($timezone)->endOfMonth();

        $revenue = [];
        $refunds = [];
        $monthCursor = $monthlyStart->copy();
        while ($monthCursor->lte($monthlyEnd)) {
            $monthStartUtc = $monthCursor->copy()->startOfMonth()->utc();
            $monthEndUtc = $monthCursor->copy()->endOfMonth()->utc();

            $monthlyRevenue = (float) \App\Models\Order::query()
                ->whereIn('status', ['paid', 'partially_refunded', 'refunded'])
                ->whereBetween('paid_at', [$monthStartUtc, $monthEndUtc])
                ->sum('total_amount');

            $monthlyRefund = (float) Refund::query()
                ->where('status', RefundStatus::Completed->value)
                ->whereBetween('processed_at', [$monthStartUtc, $monthEndUtc])
                ->sum('amount');

            $revenue[] = round(max(0.0, $monthlyRevenue - $monthlyRefund), 2);
            $refunds[] = round($monthlyRefund, 2);
            $monthCursor->addMonth();
        }

        $monthLabels = [];
        $monthCursor = $monthlyStart->copy();
        while ($monthCursor->lte($monthlyEnd)) {
            $monthLabels[] = $monthCursor->format('M Y');
            $monthCursor->addMonth();
        }

        $sessionPerformance = [
            'upcoming' => TrainingSession::query()
                ->when($start, fn ($q) => $q->where('starts_at', '>=', $start))
                ->when($end, fn ($q) => $q->where('starts_at', '<=', $end))
                ->whereIn('status', [SessionStatus::Published->value, SessionStatus::Full->value, SessionStatus::RegistrationClosed->value])
                ->count(),
            'completed' => TrainingSession::query()
                ->when($start, fn ($q) => $q->where('starts_at', '>=', $start))
                ->when($end, fn ($q) => $q->where('starts_at', '<=', $end))
                ->where('status', SessionStatus::Completed->value)
                ->count(),
            'cancelled' => TrainingSession::query()
                ->when($start, fn ($q) => $q->where('starts_at', '>=', $start))
                ->when($end, fn ($q) => $q->where('starts_at', '<=', $end))
                ->where('status', SessionStatus::Cancelled->value)
                ->count(),
        ];

        $bookingDistribution = [
            ['label' => 'Confirmed', 'value' => $report['bookings']['confirmed']],
            ['label' => 'Pending', 'value' => $report['bookings']['pending']],
            ['label' => 'Cancelled', 'value' => $report['bookings']['cancelled']],
            ['label' => 'Refunded', 'value' => $report['bookings']['refunded']],
        ];

        $upcomingSessions = TrainingSession::query()
            ->with(['module:id,code', 'instructors:id,name'])
            ->withCount([
                'bookings as confirmed_bookings_count' => fn ($query) => $query->where(
                    'status',
                    BookingStatus::Confirmed->value,
                ),
            ])
            ->whereIn('status', [
                SessionStatus::Published->value,
                SessionStatus::Full->value,
                SessionStatus::RegistrationClosed->value,
            ])
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->map(static fn (TrainingSession $session): array => [
                'id' => $session->id,
                'title' => $session->title,
                'sessionCode' => $session->session_code,
                'startsAt' => $session->starts_at?->toIso8601String(),
                'venue' => implode(', ', array_filter([
                    $session->venue_name,
                    $session->venue_city,
                ])),
                'instructors' => $session->instructors->pluck('name')->values()->all(),
                'confirmedBookings' => (int) $session->confirmed_bookings_count,
                'capacity' => (int) $session->capacity,
                'status' => $session->status instanceof SessionStatus
                    ? $session->status->label()
                    : (string) $session->status,
                'occupancyPercent' => $session->capacity > 0
                    ? (int) round(($session->confirmed_bookings_count / $session->capacity) * 100)
                    : 0,
                'detailUrl' => route('admin-vue.sessions.show', $session),
            ])
            ->values()
            ->all();

        $capacitySessions = TrainingSession::query()
            ->withCount([
                'bookings as confirmed_bookings_count' => fn ($query) => $query->where('status', BookingStatus::Confirmed->value),
            ])
            ->whereIn('status', [SessionStatus::Published->value, SessionStatus::Full->value, SessionStatus::RegistrationClosed->value])
            ->where('starts_at', '>', now())
            ->orderByDesc('confirmed_seats_count')
            ->limit(5)
            ->get()
            ->map(static fn (TrainingSession $session): array => [
                'id' => $session->id,
                'title' => $session->title,
                'registered' => (int) $session->confirmed_bookings_count,
                'capacity' => (int) $session->capacity,
                'percent' => $session->capacity > 0
                    ? (int) round(($session->confirmed_bookings_count / $session->capacity) * 100)
                    : 0,
                'status' => $session->status instanceof SessionStatus ? $session->status->label() : (string) $session->status,
                'detailUrl' => route('admin-vue.sessions.show', $session),
            ])
            ->values()
            ->all();

        $pendingAttendance = Attendance::query()
            ->where('review_status', AttendanceReviewStatus::Submitted->value)
            ->count();

        $pendingHolds = Booking::query()
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('reservation_expires_at', '>', now())
            ->count();

        $recentActivity = AuditLog::query()
            ->with('actor:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(static function (AuditLog $log): array {
                $labels = [
                    'session.created' => 'Training session created',
                    'session.published' => 'Training session published',
                    'session.cancelled' => 'Training session cancelled',
                    'session.completed' => 'Training session completed',
                    'booking.confirmed' => 'Booking confirmed',
                    'payment.successful' => 'Payment successful',
                    'refund.completed' => 'Refund completed',
                    'certificate.issued' => 'Certificate issued',
                ];

                return [
                    'event' => $log->event,
                    'title' => $labels[$log->event] ?? str_replace('.', ' ', ucfirst($log->event)),
                    'actor' => $log->actor?->name ?? 'System',
                    'createdAt' => $log->created_at?->toIso8601String(),
                    'reason' => $log->reason,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Admin/Dashboard', [
            'report' => $report,
            'filters' => [
                'from' => $from ?: null,
                'to' => $to ?: null,
            ],
            'upcomingSessions' => $upcomingSessions,
            'capacitySessions' => $capacitySessions,
            'recentActivity' => $recentActivity,
            'revenueTrend' => [
                'labels' => $monthLabels,
                'revenue' => $revenue,
                'refunds' => $refunds,
            ],
            'sessionPerformance' => $sessionPerformance,
            'bookingDistribution' => $bookingDistribution,
            'pendingActions' => [
                'attendanceReviews' => $pendingAttendance,
                'pendingRefunds' => $report['refunds']['pending_count'],
                'pendingPayments' => $report['revenue']['pending_payments'],
                'activeSeatHolds' => $pendingHolds,
            ],
        ]);
    }
}
