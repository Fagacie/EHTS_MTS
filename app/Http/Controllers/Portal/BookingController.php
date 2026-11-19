<?php

namespace App\Http\Controllers\Portal;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\SessionBookingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request, SessionBookingService $bookingService): View
    {
        $user = $request->user();

        // Sweep any expired pending holds on sessions this customer has booked
        $pendingSessionIds = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::PendingPayment->value)
            ->pluck('training_session_id')
            ->unique();

        foreach ($pendingSessionIds as $sessionId) {
            $bookingService->reconcileSessionCounters((int) $sessionId);
        }

        $statusFilter = trim((string) $request->query('status', ''));

        $bookingsQuery = Booking::query()
            ->with(['trainingSession.module', 'order', 'attendance'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($statusFilter !== '' && BookingStatus::tryFrom($statusFilter) !== null) {
            $bookingsQuery->where('status', $statusFilter);
        }

        return view('portal.bookings.index', [
            'bookings' => $bookingsQuery->paginate(10)->withQueryString(),
            'statusFilter' => $statusFilter,
            'statuses' => BookingStatus::cases(),
        ]);
    }

    public function show(Request $request, Booking $booking, SessionBookingService $bookingService): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status === BookingStatus::PendingPayment) {
            $bookingService->reconcileSessionCounters($booking->training_session_id);
            $booking->refresh();
        }

        $booking->load([
            'trainingSession.module.category',
            'trainingSession.instructors.instructorProfile',
            'order.items',
            'attendance',
        ]);

        return view('portal.bookings.show', [
            'booking' => $booking,
        ]);
    }
}
