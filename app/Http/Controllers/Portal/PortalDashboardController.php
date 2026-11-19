<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\CertificateStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderType;
use App\Enums\RecordingAccessPolicy;
use App\Enums\RecordingStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\Order;
use App\Services\Booking\SessionBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __invoke(Request $request, SessionBookingService $bookingService): View
    {
        $user = $request->user()->loadMissing(['roles', 'customerProfile']);
        $timezone = config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        // Reconcile any sessions where this user holds a pending booking
        $pendingSessionIds = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::PendingPayment->value)
            ->pluck('training_session_id')
            ->unique();

        foreach ($pendingSessionIds as $sessionId) {
            $bookingService->reconcileSessionCounters((int) $sessionId);
        }

        $activeHoldBooking = Booking::query()
            ->with('trainingSession:id,module_id,session_code,title,starts_at,ends_at,timezone,venue_name,venue_city')
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('reservation_expires_at', '>', now())
            ->latest('reserved_at')
            ->first();

        $activeEntitlementsCount = ModuleEntitlement::query()
            ->where('user_id', $user->id)
            ->where('status', EntitlementStatus::Active->value)
            ->count();

        $allUpcomingBookings = Booking::query()
            ->with([
                'trainingSession:id,module_id,session_code,title,starts_at,ends_at,timezone,venue_name,venue_city,venue_state,status',
                'trainingSession.module:id,title,code',
            ])
            ->join('training_sessions', 'training_sessions.id', '=', 'bookings.training_session_id')
            ->where('bookings.user_id', $user->id)
            ->where('bookings.status', BookingStatus::Confirmed->value)
            ->where('training_sessions.ends_at', '>', now())
            ->whereNotIn('training_sessions.status', [
                SessionStatus::Completed->value,
                SessionStatus::Cancelled->value,
            ])
            ->orderBy('training_sessions.starts_at')
            ->select('bookings.*')
            ->get();

        $upcomingBookingsCount = $allUpcomingBookings->count();
        $upcomingBookings = $allUpcomingBookings->take(4)->values();

        $completedSessionsCount = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::Confirmed->value)
            ->whereHas('trainingSession', fn ($query) => $query->where('status', SessionStatus::Completed->value))
            ->count();

        $confirmedBookingsCount = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::Confirmed->value)
            ->count();

        $issuedCertificates = Certificate::query()
            ->where('user_id', $user->id)
            ->where('status', CertificateStatus::Issued->value)
            ->orderByDesc('issued_at')
            ->get();

        $continueLearningUrl = null;
        if ($activeEntitlementsCount > 0) {
            if ($activeEntitlementsCount === 1) {
                $singleModule = ModuleEntitlement::query()
                    ->with('module:id,slug')
                    ->where('user_id', $user->id)
                    ->where('status', EntitlementStatus::Active->value)
                    ->first()?->module;

                $continueLearningUrl = $singleModule
                    ? route('portal.modules.show', $singleModule)
                    : route('portal.modules.index');
            } else {
                $continueLearningUrl = route('portal.modules.index');
            }
        } else {
            $hasAccessibleRecordings = Attendance::query()
                ->where('user_id', $user->id)
                ->where('attendance_status', AttendanceStatus::Present->value)
                ->where('review_status', AttendanceReviewStatus::Verified->value)
                ->whereHas('booking', fn ($query) => $query->where('status', BookingStatus::Confirmed->value))
                ->whereHas('trainingSession', fn ($query) => $query
                    ->where('recording_access_policy', RecordingAccessPolicy::AttendeesOnly->value)
                    ->whereHas('recordings', fn ($recordings) => $recordings->where('status', RecordingStatus::Published->value)))
                ->exists();

            if ($hasAccessibleRecordings) {
                $continueLearningUrl = route('portal.recordings.index');
            }
        }

        $recentBookings = Booking::query()
            ->with([
                'trainingSession:id,module_id,session_code,title,starts_at,ends_at,timezone,venue_name,venue_city',
                'trainingSession.module:id,title,code',
                'order:id,order_number,total_amount',
            ])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(4)
            ->get();

        $recentOrders = Order::query()
            ->with('items:id,order_id,order_type,item_type,item_code_snapshot,item_title_snapshot,line_total')
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(4)
            ->get();

        $recentActivities = $this->buildRecentActivities($recentBookings, $recentOrders);

        return view('portal.dashboard', [
            'user' => $user,
            'timezone' => $timezone,
            'publishedModulesCount' => Module::query()->published()->count(),
            'confirmedBookingsCount' => $confirmedBookingsCount,
            'upcomingBookingsCount' => $upcomingBookingsCount,
            'upcomingBookings' => $upcomingBookings,
            'completedSessionsCount' => $completedSessionsCount,
            'activeEntitlementsCount' => $activeEntitlementsCount,
            'issuedCertificates' => $issuedCertificates,
            'activeHoldBooking' => $activeHoldBooking,
            'recentBookings' => $recentBookings,
            'recentOrders' => $recentOrders,
            'recentActivities' => $recentActivities,
            'continueLearningUrl' => $continueLearningUrl,
        ]);
    }

    /**
     * Combine recent bookings and orders into a single chronological activity stream.
     *
     * @param  Collection<int, Booking>  $recentBookings
     * @param  Collection<int, Order>  $recentOrders
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRecentActivities(Collection $recentBookings, Collection $recentOrders): Collection
    {
        $bookingEntries = $recentBookings->map(function (Booking $booking): array {
            $detail = collect([
                $booking->trainingSession?->venue_city,
                $booking->order?->formattedTotal(),
            ])->filter()->implode(' · ');

            return [
                'kind' => 'Booking',
                'reference' => $booking->booking_reference,
                'title' => $booking->trainingSession?->title ?? 'Physical Training Session',
                'detail' => $detail !== '' ? $detail : 'Physical training reservation',
                'occurred_at' => $booking->created_at,
                'status_value' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'url' => route('portal.bookings.show', $booking),
            ];
        });

        $orderEntries = $recentOrders->map(function (Order $order): array {
            $firstTitle = $order->items->first()?->item_title_snapshot;
            $kindLabel = $order->order_type === OrderType::ModulePurchase ? 'Module Order' : 'Order';

            return [
                'kind' => $kindLabel,
                'reference' => $order->order_number,
                'title' => $firstTitle ?: $order->order_type->label(),
                'detail' => $order->formattedTotal(),
                'occurred_at' => $order->paid_at ?? $order->created_at,
                'status_value' => $order->status->value,
                'status_label' => $order->status->label(),
                'url' => route('portal.orders.show', $order),
            ];
        });

        return $bookingEntries
            ->concat($orderEntries)
            ->sortByDesc(fn (array $entry): int => $entry['occurred_at']?->getTimestamp() ?? 0)
            ->take(6)
            ->values();
    }
}
