<x-layouts.portal title="My Bookings">
    <div class="space-y-6">
        <x-portal.page-header
            title="My Bookings"
            description="Track upcoming physical training reservations, seat holds, and completed session records."
        >
            <x-slot:actions>
                <x-portal.button :href="route('sessions.index')" variant="primary">
                    Explore Sessions
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Status Filters --}}
        <x-portal.card :padding="false">
            <div class="flex flex-wrap items-center gap-2 px-5 py-4 sm:px-6" role="navigation" aria-label="Filter bookings by status">
                <span class="mr-1 text-sm font-medium text-portal-muted">Status:</span>

                <a
                    href="{{ route('portal.bookings.index') }}"
                    class="inline-flex min-h-9 items-center rounded-lg px-3.5 py-1.5 text-sm font-medium transition {{ $statusFilter === '' ? 'bg-portal-primary text-white shadow-xs' : 'bg-portal-bg text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text' }}"
                >
                    All Bookings
                </a>

                @foreach ($statuses as $statusOption)
                    <a
                        href="{{ route('portal.bookings.index', ['status' => $statusOption->value]) }}"
                        class="inline-flex min-h-9 items-center rounded-lg px-3.5 py-1.5 text-sm font-medium transition {{ $statusFilter === $statusOption->value ? 'bg-portal-primary text-white shadow-xs' : 'bg-portal-bg text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text' }}"
                    >
                        {{ $statusOption->label() }}
                    </a>
                @endforeach

                @if ($statusFilter !== '')
                    <a
                        href="{{ route('portal.bookings.index') }}"
                        class="ml-auto inline-flex min-h-9 items-center px-2 text-sm font-medium text-portal-primary hover:underline"
                    >
                        Reset filter
                    </a>
                @endif
            </div>
        </x-portal.card>

        @if ($bookings->isEmpty())
            <x-portal.empty-state
                title="No bookings found"
                :description="$statusFilter !== '' ? 'No bookings match the selected status filter.' : 'You have not booked any physical training sessions yet.'"
            >
                <x-slot:action>
                    @if ($statusFilter !== '')
                        <x-portal.button :href="route('portal.bookings.index')" variant="secondary">
                            View All Bookings
                        </x-portal.button>
                    @endif
                    <x-portal.button :href="route('sessions.index')" variant="primary">
                        Browse Scheduled Sessions
                    </x-portal.button>
                </x-slot:action>
            </x-portal.empty-state>
        @else
            {{-- Mobile Stacked Cards --}}
            <div class="space-y-4 md:hidden">
                @foreach ($bookings as $booking)
                    <x-portal.card :padding="false">
                        <div class="space-y-3.5 p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-mono text-sm font-semibold text-portal-primary">
                                    {{ $booking->booking_reference }}
                                </span>
                                <x-portal.status-badge :status="$booking->status" :label="$booking->status->label()" />
                            </div>

                            <div class="min-w-0">
                                <h2 class="text-base font-semibold text-portal-text">
                                    {{ $booking->trainingSession->title }}
                                </h2>
                                <p class="mt-1 text-sm text-portal-muted">
                                    {{ $booking->trainingSession->module?->code }}
                                    @if ($booking->trainingSession->module?->code && $booking->trainingSession->session_code)
                                        ·
                                    @endif
                                    {{ $booking->trainingSession->session_code }}
                                </p>
                            </div>

                            <dl class="space-y-2 border-t border-portal-border pt-3 text-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-portal-muted">Schedule</dt>
                                    <dd class="text-right font-medium text-portal-text">
                                        {{ $booking->trainingSession->formattedSchedule() }}
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-portal-muted">Venue</dt>
                                    <dd class="text-right text-portal-text">
                                        {{ $booking->trainingSession->venue_name ?: 'TBA' }}{{ $booking->trainingSession->venue_city ? ', '.$booking->trainingSession->venue_city : '' }}
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-portal-muted">Amount</dt>
                                    <dd class="text-right font-semibold tabular-nums text-portal-text">
                                        {{ $booking->order?->formattedTotal() ?? '—' }}
                                    </dd>
                                </div>
                            </dl>

                            @if ($booking->isHoldActive())
                                <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 ring-1 ring-inset ring-amber-600/20">
                                    Seat hold expires {{ $booking->reservation_expires_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT
                                </div>
                            @endif

                            <div class="pt-1">
                                <x-portal.button :href="route('portal.bookings.show', $booking)" variant="secondary" class="w-full justify-center">
                                    View Booking Details
                                </x-portal.button>
                            </div>
                        </div>
                    </x-portal.card>
                @endforeach
            </div>

            {{-- Desktop Table --}}
            <x-portal.table class="hidden md:block">
                <x-slot:head>
                    <tr>
                        <th>Booking Reference</th>
                        <th>Session</th>
                        <th>Schedule &amp; Venue</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </x-slot:head>

                @foreach ($bookings as $booking)
                    <tr>
                        <td>
                            <div class="font-mono text-sm font-semibold text-portal-primary">
                                {{ $booking->booking_reference }}
                            </div>
                            @if ($booking->order)
                                <div class="mt-0.5 text-[13px] text-portal-muted">
                                    Order {{ $booking->order->order_number }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="text-[15px] font-medium text-portal-text">
                                {{ $booking->trainingSession->title }}
                            </div>
                            <div class="mt-0.5 text-[13px] text-portal-muted">
                                {{ $booking->trainingSession->module?->code }}
                                @if ($booking->trainingSession->module?->code && $booking->trainingSession->session_code)
                                    ·
                                @endif
                                {{ $booking->trainingSession->session_code }}
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-portal-text">
                                {{ $booking->trainingSession->formattedSchedule() }}
                            </div>
                            <div class="mt-0.5 text-[13px] text-portal-muted">
                                {{ $booking->trainingSession->venue_name ?: 'TBA' }}{{ $booking->trainingSession->venue_city ? ', '.$booking->trainingSession->venue_city : '' }}
                            </div>
                        </td>
                        <td>
                            <span class="font-semibold tabular-nums text-portal-text">
                                {{ $booking->order?->formattedTotal() ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <x-portal.status-badge :status="$booking->status" :label="$booking->status->label()" />
                            @if ($booking->isHoldActive())
                                <div class="mt-1.5 text-[13px] font-medium text-amber-800">
                                    Expires {{ $booking->reservation_expires_at?->timezone('Asia/Kuala_Lumpur')->format('h:i A') }}
                                </div>
                            @endif
                        </td>
                        <td class="text-right">
                            <x-portal.button :href="route('portal.bookings.show', $booking)" variant="secondary" size="sm">
                                View Details
                            </x-portal.button>
                        </td>
                    </tr>
                @endforeach
            </x-portal.table>

            @if ($bookings->hasPages())
                <div class="pt-1">
                    {{ $bookings->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.portal>
