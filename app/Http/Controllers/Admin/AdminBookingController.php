<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Booking\SessionBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminBookingController extends Controller
{
    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);
        $booking->load([
            'user:id,name,email',
            'trainingSession:id,session_code,title,starts_at,venue_name,venue_city',
            'order:id,order_number,order_type,status,total_amount,paid_at',
            'order.payments:id,order_id,status,amount,paid_at',
            'order.refunds:id,order_id,status,amount,reason',
            'attendance:id,booking_id,attendance_status,review_status,submitted_at,verified_at,verification_notes',
        ]);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'reference' => $booking->booking_reference,
                'status' => $booking->status instanceof BookingStatus ? $booking->status->label() : (string) $booking->status,
                'statusValue' => $booking->status instanceof BookingStatus ? $booking->status->value : (string) $booking->status,
                'registeredAt' => $booking->created_at?->toIso8601String(),
                'cancellationReason' => $booking->cancellation_reason,
                'participant' => ['name' => $booking->user?->name ?? 'Unknown', 'email' => $booking->user?->email],
                'session' => $booking->trainingSession ? [
                    'title' => $booking->trainingSession->title,
                    'code' => $booking->trainingSession->session_code,
                    'startsAt' => $booking->trainingSession->starts_at?->toIso8601String(),
                    'venue' => implode(', ', array_filter([$booking->trainingSession->venue_name, $booking->trainingSession->venue_city])),
                    'detailUrl' => route('admin-vue.sessions.show', $booking->trainingSession),
                ] : null,
                'order' => $booking->order ? [
                    'number' => $booking->order->order_number,
                    'type' => $booking->order->order_type?->value,
                    'status' => $booking->order->status?->label(),
                    'total' => number_format((float) $booking->order->total_amount, 2, '.', ''),
                    'paidAt' => $booking->order->paid_at?->toIso8601String(),
                    'payments' => $booking->order->payments->map(fn ($payment): array => [
                        'id' => $payment->id,
                        'status' => $payment->status?->label(),
                        'amount' => number_format((float) $payment->amount, 2, '.', ''),
                        'paidAt' => $payment->paid_at?->toIso8601String(),
                    ])->values()->all(),
                    'refunds' => $booking->order->refunds->map(fn ($refund): array => [
                        'id' => $refund->id,
                        'status' => $refund->status?->label(),
                        'amount' => number_format((float) $refund->amount, 2, '.', ''),
                        'reason' => $refund->reason,
                    ])->values()->all(),
                ] : null,
                'attendance' => $booking->attendance ? [
                    'attendanceStatus' => $booking->attendance->attendance_status?->label(),
                    'reviewStatus' => $booking->attendance->review_status?->label(),
                    'submittedAt' => $booking->attendance->submitted_at?->toIso8601String(),
                    'verifiedAt' => $booking->attendance->verified_at?->toIso8601String(),
                    'notes' => $booking->attendance->verification_notes,
                ] : null,
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $sessionId = $request->integer('session');

        $bookings = Booking::query()
            ->with([
                'user:id,name,email',
                'trainingSession:id,session_code,title',
                'attendance:id,booking_id,attendance_status,review_status',
            ])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('booking_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('trainingSession', fn ($session) => $session
                        ->where('session_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%"));
            }))
            ->when($status !== '' && BookingStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->when($sessionId, fn ($query) => $query->where('training_session_id', $sessionId))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (Booking $booking): array => [
                'id' => $booking->id,
                'reference' => $booking->booking_reference,
                'participant' => [
                    'name' => $booking->user?->name ?? 'Unknown',
                    'email' => $booking->user?->email,
                ],
                'session' => [
                    'code' => $booking->trainingSession?->session_code,
                    'title' => $booking->trainingSession?->title,
                ],
                'status' => $booking->status instanceof BookingStatus
                    ? $booking->status->label()
                    : (string) $booking->status,
                'statusValue' => $booking->status instanceof BookingStatus
                    ? $booking->status->value
                    : (string) $booking->status,
                'statusValue' => $booking->status instanceof BookingStatus
                    ? $booking->status->value
                    : (string) $booking->status,
                'attendance' => $booking->attendance ? [
                    'status' => $booking->attendance->attendance_status?->label(),
                    'reviewStatus' => $booking->attendance->review_status?->label(),
                ] : null,
                'createdAt' => $booking->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'session' => $sessionId ? (string) $sessionId : '',
            ],
            'statuses' => collect(BookingStatus::cases())
                ->map(static fn (BookingStatus $bookingStatus): array => [
                    'value' => $bookingStatus->value,
                    'label' => $bookingStatus->label(),
                ])
                ->values()
                ->all(),
            'sessions' => TrainingSession::query()
                ->orderByDesc('starts_at')
                ->limit(100)
                ->get(['id', 'session_code', 'title'])
                ->map(static fn (TrainingSession $session): array => [
                    'value' => (string) $session->id,
                    'label' => "{$session->session_code} — {$session->title}",
                ])
                ->values()
                ->all(),
        ]);
    }

    public function cancel(Request $request, Booking $booking, SessionBookingService $bookingService): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        abort_unless(in_array($booking->status, [BookingStatus::PendingPayment, BookingStatus::Confirmed], true), 422, 'This booking cannot be cancelled from its current status.');

        /** @var User $admin */
        $admin = Auth::user();
        $bookingService->cancelBookingByAdmin($booking, $admin, $validated['reason']);

        return back()->with('status', 'Booking cancelled and seat counts updated.');
    }
}
