<?php

namespace App\Http\Controllers\Portal;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Booking\SessionBookingService;
use App\Support\PortalPresenter;
use App\Support\PublicCataloguePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BookingController extends Controller
{
    public function index(Request $request, SessionBookingService $bookingService): InertiaResponse
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

        $paginator = $bookingsQuery->paginate(10)->withQueryString();

        return Inertia::render('Portal/Bookings/Index', [
            'bookings' => $paginator->getCollection()
                ->map(static fn (Booking $booking): array => PortalPresenter::bookingListItem($booking))
                ->values()
                ->all(),
            'pagination' => PublicCataloguePresenter::paginationMeta($paginator),
            'statusFilter' => $statusFilter,
            'statuses' => array_map(
                static fn (BookingStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                BookingStatus::cases(),
            ),
        ]);
    }

    public function show(Request $request, Booking $booking, SessionBookingService $bookingService): InertiaResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status === BookingStatus::PendingPayment) {
            $bookingService->reconcileSessionCounters($booking->training_session_id);
            $booking->refresh();
        }

        $booking->load([
            'trainingSession.module.category',
            'trainingSession.instructors.instructorProfile',
            'trainingSession.publishedMaterials',
            'order.items',
            'attendance',
        ]);

        return Inertia::render('Portal/Bookings/Show', [
            'booking' => PortalPresenter::bookingDetail($booking),
        ]);
    }
}
