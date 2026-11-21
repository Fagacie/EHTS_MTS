<x-layouts.portal title="Booking {{ $booking->booking_reference }}">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <a
                href="{{ route('portal.bookings.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-portal-muted transition hover:text-portal-primary"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                <span>Back to My Bookings</span>
            </a>

            <x-portal.status-badge :status="$booking->status" :label="$booking->status->label()" />
        </div>

        <x-portal.page-header
            :title="$booking->trainingSession->title"
            :description="'Booking reference '.$booking->booking_reference"
        >
            <x-slot:badges>
                @if ($booking->trainingSession->module?->code)
                    <span class="inline-flex items-center rounded-md bg-portal-primary-soft px-2.5 py-1 text-[13px] font-semibold text-portal-primary">
                        {{ $booking->trainingSession->module->code }}
                    </span>
                @endif
                @if ($booking->trainingSession->session_code)
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-[13px] font-medium text-portal-text">
                        {{ $booking->trainingSession->session_code }}
                    </span>
                @endif
            </x-slot:badges>

            <x-slot:actions>
                @if ($booking->status === \App\Enums\BookingStatus::Confirmed && $booking->trainingSession->module)
                    <x-portal.button :href="route('portal.modules.show', $booking->trainingSession->module)" variant="primary">
                        Open Module Materials
                    </x-portal.button>
                @endif
                @if ($booking->order)
                    <x-portal.button :href="route('portal.orders.show', $booking->order)" variant="secondary">
                        View Order
                    </x-portal.button>
                @endif
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Actionable Status Notices --}}
        @if ($booking->isHoldActive())
            <x-portal.alert
                variant="warning"
                title="Complete Payment Before {{ $booking->reservation_expires_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT"
            >
                <p>
                    Your seat is temporarily held while payment is pending. If payment is not completed within the hold window, the reservation is released automatically.
                </p>
                @if ($booking->order)
                    <x-slot:action>
                        <form method="POST" action="{{ route('portal.orders.pay', $booking->order) }}">
                            @csrf
                            <x-portal.button type="submit" variant="primary" size="sm">
                                Pay Online Now
                            </x-portal.button>
                        </form>
                    </x-slot:action>
                @endif
            </x-portal.alert>
        @elseif ($booking->status === \App\Enums\BookingStatus::Expired)
            <x-portal.alert variant="danger" title="Seat Reservation Expired">
                <p>
                    This temporary hold expired before payment was completed and your seat was released.
                </p>
                @if ($booking->trainingSession->isOpenForBooking())
                    <x-slot:action>
                        <x-portal.button :href="route('checkout.session.show', $booking->trainingSession)" variant="primary" size="sm">
                            Book Seat Again
                        </x-portal.button>
                    </x-slot:action>
                @endif
            </x-portal.alert>
        @elseif ($booking->status === \App\Enums\BookingStatus::Cancelled)
            <x-portal.alert variant="danger" title="Booking Cancelled">
                <p>{{ $booking->cancellation_reason ?: 'This session booking was cancelled.' }}</p>
            </x-portal.alert>
        @elseif ($booking->status === \App\Enums\BookingStatus::Refunded)
            <x-portal.alert variant="info" title="Booking Refunded">
                <p>This booking has been refunded. Check your linked order record for payment and refund tracking details.</p>
            </x-portal.alert>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-portal.card title="Training Session Details">
                    <dl class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-portal-muted">Schedule</dt>
                            <dd class="mt-1 text-[15px] font-medium text-portal-text">
                                {{ $booking->trainingSession->formattedSchedule() }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-portal-muted">Duration</dt>
                            <dd class="mt-1 text-[15px] text-portal-text">
                                {{ $booking->trainingSession->formattedDuration() }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-portal-muted">Venue</dt>
                            <dd class="mt-1 text-[15px] font-medium text-portal-text">
                                {{ $booking->trainingSession->venue_name ?: 'To be confirmed' }}
                            </dd>
                            @if ($booking->trainingSession->venue_address || $booking->trainingSession->venue_city || $booking->trainingSession->venue_state)
                                <dd class="mt-0.5 text-sm text-portal-muted">
                                    {{ collect([
                                        $booking->trainingSession->venue_address,
                                        $booking->trainingSession->venue_city,
                                        $booking->trainingSession->venue_state,
                                    ])->filter()->implode(', ') }}
                                </dd>
                            @endif
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-portal-muted">Assigned Instructors</dt>
                            <dd class="mt-1 text-[15px] text-portal-text">
                                @if ($booking->trainingSession->instructors->isNotEmpty())
                                    <ul class="space-y-1">
                                        @foreach ($booking->trainingSession->instructors as $instructor)
                                            <li>
                                                <span class="font-medium">{{ $instructor->name }}</span>
                                                @if ($instructor->instructorProfile?->title)
                                                    <span class="text-sm text-portal-muted"> — {{ $instructor->instructorProfile->title }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    To be assigned
                                @endif
                            </dd>
                        </div>
                    </dl>

                </x-portal.card>

                {{-- Attendance & Session Completion Status --}}
                <x-portal.card title="Attendance Status">
                    @if ($booking->attendance)
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-portal-muted">Recorded Attendance</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                                    <x-portal.status-badge
                                        :status="$booking->attendance->attendance_status"
                                        :label="$booking->attendance->attendance_status->label()"
                                    />
                                    <x-portal.status-badge
                                        :status="$booking->attendance->review_status"
                                        :label="$booking->attendance->review_status->label()"
                                    />
                                </div>
                            </div>

                            @if ($booking->attendance->remarks)
                                <p class="text-sm text-portal-muted">
                                    {{ $booking->attendance->remarks }}
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-[15px] text-portal-muted">
                            Attendance has not been recorded for this session yet.
                        </p>
                    @endif
                </x-portal.card>
            </div>

            {{-- Linked Order & Policy Summary --}}
            <div class="space-y-6">
                <x-portal.card title="Booking Summary">
                    @if ($booking->order)
                        <dl class="space-y-3.5 text-[15px]">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-portal-muted">Order Number</dt>
                                <dd class="font-mono text-sm font-semibold text-portal-primary">
                                    {{ $booking->order->order_number }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-portal-muted">Payment Status</dt>
                                <dd>
                                    <x-portal.status-badge :status="$booking->order->status" :label="$booking->order->status->label()" />
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-portal-muted">Registration Type</dt>
                                <dd class="font-medium text-portal-text">
                                </dd>
                            </div>
                            @if ($booking->order->company_name)
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-portal-muted">Organisation</dt>
                                    <dd class="text-right font-medium text-portal-text">
                                        {{ $booking->order->company_name }}
                                    </dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3 border-t border-portal-border pt-3.5">
                                <dt class="font-semibold text-portal-text">Total Fee</dt>
                                <dd class="text-base font-semibold tabular-nums text-portal-text">
                                    {{ $booking->order->formattedTotal() }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-5">
                            <x-portal.button :href="route('portal.orders.show', $booking->order)" variant="secondary" class="w-full justify-center">
                                View Full Order &amp; Receipt
                            </x-portal.button>
                        </div>
                    @endif
                </x-portal.card>

                <x-portal.card title="Cancellation &amp; Refund Policy">
                    <p class="text-[14px] leading-relaxed text-portal-muted">
                        Cancellations and refund requests are handled directly by Ecohydrotech administration. Please contact our support team with your booking reference if your schedule changes.
                    </p>
                </x-portal.card>
            </div>
        </div>
    </div>
</x-layouts.portal>
