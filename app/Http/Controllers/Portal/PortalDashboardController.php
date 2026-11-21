<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\CertificateStatus;
use App\Enums\EntitlementStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\RecordingAccessPolicy;
use App\Enums\RecordingStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\ModuleEntitlement;
use App\Models\Order;
use App\Services\Booking\SessionBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PortalDashboardController extends Controller
{
    public function __invoke(Request $request, SessionBookingService $bookingService): Response
    {
        $user = $request->user()->loadMissing('roles');
        $timezone = (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');

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

        $activeModulesCount = ModuleEntitlement::query()
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

        $issuedCertificates = Certificate::query()
            ->where('user_id', $user->id)
            ->where('status', CertificateStatus::Issued->value)
            ->orderByDesc('issued_at')
            ->get();

        $certificatesCount = $issuedCertificates->count();

        $pendingPaymentsCount = Order::query()
            ->where('user_id', $user->id)
            ->where('status', OrderStatus::PendingPayment->value)
            ->count();

        $continueLearningUrl = $this->resolveContinueLearningUrl($user->id, $activeModulesCount);

        $recentBookings = Booking::query()
            ->with([
                'trainingSession:id,module_id,session_code,title,starts_at,ends_at,timezone,venue_name,venue_city',
                'trainingSession.module:id,title,code',
                'order:id,order_number,total_amount',
            ])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(6)
            ->get();

        $recentOrders = Order::query()
            ->with('items:id,order_id,order_type,item_type,item_code_snapshot,item_title_snapshot,line_total')
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(6)
            ->get();

        $nextBooking = $allUpcomingBookings->first();
        $nextStart = $nextBooking?->trainingSession?->starts_at?->copy()->timezone($timezone);
        $daysUntilNextSession = $nextStart
            ? max(0, (int) now($timezone)->startOfDay()->diffInDays($nextStart->copy()->startOfDay(), false))
            : null;

        $summarySegments = array_values(array_filter([
            $activeModulesCount > 0 ? "{$activeModulesCount} ".Str::plural('active module', $activeModulesCount) : null,
            $upcomingBookingsCount > 0 ? "{$upcomingBookingsCount} ".Str::plural('upcoming session', $upcomingBookingsCount) : null,
            $completedSessionsCount > 0 ? "{$completedSessionsCount} ".Str::plural('completed session', $completedSessionsCount) : null,
            $certificatesCount > 0 ? "{$certificatesCount} ".Str::plural('certificate', $certificatesCount).' earned' : null,
        ]));

        $summaryLine = count($summarySegments) > 0
            ? implode(' · ', $summarySegments)
            : 'Your training modules, upcoming sessions, and certificates.';

        $monthlyActivity = $this->buildMonthlyActivity($user->id, $timezone);

        $portfolioMix = [
            ['key' => 'modules', 'label' => 'Active Modules', 'count' => $activeModulesCount],
            ['key' => 'upcoming', 'label' => 'Upcoming Bookings', 'count' => $upcomingBookingsCount],
            ['key' => 'completed', 'label' => 'Completed Sessions', 'count' => $completedSessionsCount],
            ['key' => 'certificates', 'label' => 'Certificates Earned', 'count' => $certificatesCount],
        ];

        $userInitials = collect(preg_split('/\s+/', trim($user->name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: 'U';

        $calendarEvents = $this->buildCalendarEvents($user->id, $timezone);
        $nextSessionTz = $nextBooking?->trainingSession?->timezone ?: $timezone;
        $nextStartForCalendar = $nextBooking?->trainingSession?->starts_at?->copy()->timezone($nextSessionTz);
        $calendarInitialDate = $nextStartForCalendar?->format('Y-m-d')
            ?? now($timezone)->format('Y-m-d');

        return Inertia::render('Portal/Dashboard', [
            'stats' => [
                'activeModulesCount' => $activeModulesCount,
                'upcomingBookingsCount' => $upcomingBookingsCount,
                'completedSessionsCount' => $completedSessionsCount,
                'certificatesCount' => $certificatesCount,
                'pendingPaymentsCount' => $pendingPaymentsCount,
                'daysUntilNextSession' => $daysUntilNextSession,
                'nextSessionTitle' => $nextBooking?->trainingSession?->title,
                'summaryLine' => $summaryLine,
            ],
            'monthlyActivity' => $monthlyActivity,
            'portfolioMix' => $portfolioMix,
            'activeHoldBooking' => $activeHoldBooking ? [
                'bookingReference' => $activeHoldBooking->booking_reference,
                'sessionTitle' => $activeHoldBooking->trainingSession?->title ?? 'Physical Training Session',
                'expiresAtLabel' => $activeHoldBooking->reservation_expires_at
                    ? $activeHoldBooking->reservation_expires_at->copy()->timezone($timezone)->format('d M Y, g:i A').' MYT'
                    : null,
                'detailUrl' => route('portal.bookings.show', $activeHoldBooking),
            ] : null,
            'upcomingBookings' => $upcomingBookings->map(function (Booking $booking) use ($timezone): array {
                $session = $booking->trainingSession;
                $sessionTz = $session?->timezone ?: $timezone;
                $start = $session?->starts_at?->copy()->timezone($sessionTz);
                $end = $session?->ends_at?->copy()->timezone($sessionTz);
                $isMultiDay = $start && $end && ! $start->isSameDay($end);

                $dateRangeLabel = match (true) {
                    ! $start => 'Schedule TBA',
                    ! $isMultiDay || ! $end => $start->format('j M Y'),
                    $start->isSameMonth($end) => "{$start->format('j')}\u{2013}{$end->format('j M Y')}",
                    $start->isSameYear($end) => "{$start->format('j M')}\u{2013}{$end->format('j M Y')}",
                    default => "{$start->format('j M Y')}\u{2013}{$end->format('j M Y')}",
                };

                $timeRangeLabel = match (true) {
                    ! $start => 'Schedule TBA',
                    $end && ! $isMultiDay => "{$start->format('g:i A')} \u{2013} {$end->format('g:i A')} MYT",
                    default => "{$start->format('g:i A')} MYT",
                };

                $venueFull = collect([
                    $session?->venue_name,
                    collect([$session?->venue_city, $session?->venue_state])->filter()->implode(', '),
                ])->filter()->implode(', ');

                return [
                    'id' => $booking->id,
                    'bookingReference' => $booking->booking_reference,
                    'sessionTitle' => $session?->title ?? 'Physical Training Session',
                    'moduleTitle' => $session?->module?->title,
                    'dayNumber' => $start ? $start->format('d') : '—',
                    'monthShort' => $start ? strtoupper($start->format('M')) : 'TBA',
                    'dateRangeLabel' => $dateRangeLabel,
                    'timeRangeLabel' => $timeRangeLabel,
                    'venueName' => $session?->venue_name ?? 'Venue TBA',
                    'venueCity' => $session?->venue_city,
                    'venueFull' => $venueFull !== '' ? $venueFull : 'Venue TBA',
                    'statusValue' => $booking->status->value,
                    'statusLabel' => $booking->status->label(),
                    'detailUrl' => route('portal.bookings.show', $booking),
                ];
            })->values()->all(),
            'recentActivity' => $this->buildRecentActivity($recentBookings, $recentOrders, $timezone),
            'calendarEvents' => $calendarEvents,
            'calendarInitialDate' => $calendarInitialDate,
            'certificates' => $issuedCertificates->take(6)->map(fn (Certificate $certificate): array => [
                'id' => $certificate->id,
                'certificateNumber' => $certificate->certificate_number,
                'title' => $certificate->training_title_snapshot,
                'recordTypeLabel' => $certificate->recordTypeLabel(),
                'issuedDateLabel' => $certificate->issued_at?->copy()->timezone($timezone)->format('d M Y') ?? '—',
                'viewUrl' => route('portal.certificates.show', $certificate),
                'downloadUrl' => route('certificates.download', $certificate),
            ])->values()->all(),
            'portalMeta' => [
                'userName' => $user->name,
                'userInitials' => $userInitials,
                'unreadNotificationsCount' => $user->unreadNotifications()->count(),
                'continueLearningUrl' => $continueLearningUrl,
                'availablePortals' => array_map(
                    static fn (array $portal): array => [
                        'slug' => $portal['slug'],
                        'label' => $portal['label'],
                        'description' => $portal['description'],
                        'url' => $portal['url'],
                    ],
                    $user->availablePortals(),
                ),
            ],
        ]);
    }

    private function resolveContinueLearningUrl(int $userId, int $activeModulesCount): ?string
    {
        if ($activeModulesCount > 0) {
            if ($activeModulesCount === 1) {
                $singleModule = ModuleEntitlement::query()
                    ->with('module:id,slug')
                    ->where('user_id', $userId)
                    ->where('status', EntitlementStatus::Active->value)
                    ->first()?->module;

                return $singleModule
                    ? route('portal.modules.show', $singleModule)
                    : route('portal.modules.index');
            }

            return route('portal.modules.index');
        }

        $hasAccessibleRecordings = Attendance::query()
            ->where('user_id', $userId)
            ->where('attendance_status', AttendanceStatus::Present->value)
            ->where('review_status', AttendanceReviewStatus::Verified->value)
            ->whereHas('booking', fn ($query) => $query->where('status', BookingStatus::Confirmed->value))
            ->whereHas('trainingSession', fn ($query) => $query
                ->where('status', SessionStatus::Completed->value)
                ->where('recording_access_policy', RecordingAccessPolicy::AttendeesOnly->value)
                ->whereHas('recordings', fn ($recordings) => $recordings->where('status', RecordingStatus::Published->value)))
            ->exists();

        return $hasAccessibleRecordings ? route('portal.recordings.index') : null;
    }

    /**
     * Build rolling 6-month training activity and paid spend series in display timezone.
     *
     * @return list<array<string, mixed>>
     */
    private function buildMonthlyActivity(int $userId, string $timezone): array
    {
        $nowTz = now($timezone);
        $buckets = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $monthStart = $nowTz->copy()->startOfMonth()->subMonths($offset);
            $key = $monthStart->format('Y-m');
            $buckets[$key] = [
                'monthKey' => $key,
                'monthLabel' => $monthStart->format('M'),
                'fullMonthLabel' => $monthStart->format('F Y'),
                'bookingsCount' => 0,
                'ordersCount' => 0,
                'paidAmountMyr' => 0.0,
                'trainingHours' => 0.0,
            ];
        }

        $windowStartUtc = $nowTz->copy()->startOfMonth()->subMonths(5)->utc();

        $bookings = Booking::query()
            ->with('trainingSession:id,duration_minutes')
            ->where('user_id', $userId)
            ->where(function ($query) use ($windowStartUtc): void {
                $query->where('created_at', '>=', $windowStartUtc)
                    ->orWhere('confirmed_at', '>=', $windowStartUtc);
            })
            ->get();

        foreach ($bookings as $booking) {
            $createdKey = $booking->created_at?->copy()->timezone($timezone)->format('Y-m');
            if ($createdKey !== null && isset($buckets[$createdKey])) {
                $buckets[$createdKey]['bookingsCount']++;
            }

            if ($booking->status === BookingStatus::Confirmed) {
                $effective = ($booking->confirmed_at ?? $booking->created_at)?->copy()->timezone($timezone)->format('Y-m');
                if ($effective !== null && isset($buckets[$effective])) {
                    $hours = ((int) ($booking->trainingSession?->duration_minutes ?? 0)) / 60;
                    $buckets[$effective]['trainingHours'] = round($buckets[$effective]['trainingHours'] + $hours, 1);
                }
            }
        }

        $paidOrders = Order::query()
            ->where('user_id', $userId)
            ->where('status', OrderStatus::Paid->value)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$windowStartUtc->toDateTimeString()])
            ->get();

        foreach ($paidOrders as $order) {
            $effective = ($order->paid_at ?? $order->created_at)?->copy()->timezone($timezone)->format('Y-m');
            if ($effective !== null && isset($buckets[$effective])) {
                $buckets[$effective]['ordersCount']++;
                $buckets[$effective]['paidAmountMyr'] = round(
                    $buckets[$effective]['paidAmountMyr'] + (float) $order->total_amount,
                    2,
                );
            }
        }

        return array_values($buckets);
    }

    /**
     * Combine recent bookings and orders into a single chronological activity feed.
     *
     * @param  Collection<int, Booking>  $recentBookings
     * @param  Collection<int, Order>  $recentOrders
     * @return list<array<string, mixed>>
     */
    private function buildRecentActivity(Collection $recentBookings, Collection $recentOrders, string $timezone): array
    {
        $bookingEntries = $recentBookings->map(function (Booking $booking) use ($timezone): array {
            $occurredAt = $booking->created_at?->copy()->timezone($timezone);
            $venueCity = $booking->trainingSession?->venue_city;

            return [
                'id' => "booking-{$booking->id}",
                'type' => 'booking',
                'kindLabel' => 'Booking',
                'reference' => $booking->booking_reference,
                'title' => $booking->trainingSession?->title ?? 'Physical Training Session',
                'detail' => $venueCity ?: 'Physical training',
                'amountLabel' => $booking->order?->formattedTotal() ?? '—',
                'dateLabel' => $occurredAt ? $occurredAt->format('d M Y, g:i A') : '—',
                'shortDateLabel' => $occurredAt ? $occurredAt->format('d M Y') : '—',
                'sortTs' => $booking->created_at?->getTimestamp() ?? 0,
                'statusValue' => $booking->status->value,
                'statusLabel' => $booking->status->label(),
                'actionUrl' => route('portal.bookings.show', $booking),
            ];
        });

        $orderEntries = $recentOrders->map(function (Order $order) use ($timezone): array {
            $firstTitle = $order->items->first()?->item_title_snapshot;
            $occurredAt = ($order->paid_at ?? $order->created_at)?->copy()->timezone($timezone);
            $isModule = $order->order_type === OrderType::ModulePurchase;

            return [
                'id' => "order-{$order->id}",
                'type' => 'order',
                'kindLabel' => $isModule ? 'Module Order' : 'Order',
                'reference' => $order->order_number,
                'title' => $firstTitle ?: $order->order_type->label(),
                'detail' => $isModule ? 'Independent module' : 'Session booking order',
                'amountLabel' => $order->formattedTotal(),
                'dateLabel' => $occurredAt ? $occurredAt->format('d M Y, g:i A') : '—',
                'shortDateLabel' => $occurredAt ? $occurredAt->format('d M Y') : '—',
                'sortTs' => ($order->paid_at ?? $order->created_at)?->getTimestamp() ?? 0,
                'statusValue' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'actionUrl' => route('portal.orders.show', $order),
            ];
        });

        return $bookingEntries
            ->concat($orderEntries)
            ->sortByDesc('sortTs')
            ->take(10)
            ->map(function (array $entry): array {
                unset($entry['sortTs']);

                return $entry;
            })
            ->values()
            ->all();
    }

    /**
     * Build calendar event entries from confirmed and active-hold session bookings.
     *
     * @return list<array<string, mixed>>
     */
    private function buildCalendarEvents(int $userId, string $timezone): array
    {
        $windowStartUtc = now()->subMonths(6);
        $windowEndUtc = now()->addMonths(12);

        $bookings = Booking::query()
            ->with('trainingSession:id,session_code,title,starts_at,ends_at,timezone,venue_name,venue_city,status')
            ->join('training_sessions', 'training_sessions.id', '=', 'bookings.training_session_id')
            ->where('bookings.user_id', $userId)
            ->where(function ($query): void {
                $query->where('bookings.status', BookingStatus::Confirmed->value)
                    ->orWhere(function ($holdQuery): void {
                        $holdQuery->where('bookings.status', BookingStatus::PendingPayment->value)
                            ->where('bookings.reservation_expires_at', '>', now());
                    });
            })
            ->where('training_sessions.status', '!=', SessionStatus::Cancelled->value)
            ->whereBetween('training_sessions.starts_at', [$windowStartUtc, $windowEndUtc])
            ->orderBy('training_sessions.starts_at')
            ->select('bookings.*')
            ->get();

        return $bookings
            ->filter(static fn (Booking $booking): bool => $booking->trainingSession?->starts_at !== null)
            ->map(function (Booking $booking) use ($timezone): array {
                $session = $booking->trainingSession;
                $sessionTz = $session?->timezone ?: $timezone;
                $start = $session->starts_at->copy()->timezone($sessionTz);
                $end = $session->ends_at?->copy()->timezone($sessionTz) ?? $start;

                return [
                    'id' => $booking->id,
                    'bookingReference' => $booking->booking_reference,
                    'sessionTitle' => $session->title,
                    'startDate' => $start->format('Y-m-d'),
                    'endDate' => $end->lt($start) ? $start->format('Y-m-d') : $end->format('Y-m-d'),
                    'dateLabel' => $start->format('j F Y'),
                    'timeLabel' => $start->format('g:i A').' MYT',
                    'venueName' => $session->venue_city ?: ($session->venue_name ?: 'Venue TBA'),
                    'statusValue' => $booking->status->value,
                    'statusLabel' => $booking->status->label(),
                    'detailUrl' => route('portal.bookings.show', $booking),
                ];
            })
            ->values()
            ->all();
    }
}
