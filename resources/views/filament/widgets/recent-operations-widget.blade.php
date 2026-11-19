<x-filament-widgets::widget>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Operational Activity</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Live bookings, Billplz payment transactions (MYR), and upcoming physical training sessions ({{ $pendingHolds }} active 15-minute hold(s)).
                </p>
            </div>
            <a href="{{ \App\Filament\Pages\ReportsPage::getUrl() }}" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">
                Open Full Reports &amp; CSV Exports
            </a>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Recent Bookings -->
            <x-filament::section heading="Recent Bookings">
                <div class="divide-y divide-gray-200 dark:divide-white/10 text-xs">
                    @forelse ($recentBookings as $booking)
                        <div class="py-2.5 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-mono font-semibold text-gray-900 dark:text-white truncate">{{ $booking->booking_reference }}</p>
                                <p class="text-gray-600 dark:text-gray-300 truncate">{{ $booking->user?->name ?? 'Trainee' }}</p>
                                <p class="text-gray-400 truncate">{{ $booking->trainingSession?->title ?? 'Session' }}</p>
                            </div>
                            <x-filament::badge :color="$booking->status->badgeColor()">
                                {{ $booking->status->label() }}
                            </x-filament::badge>
                        </div>
                    @empty
                        <p class="py-4 text-center text-gray-400">No bookings recorded yet.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <!-- Recent Payments -->
            <x-filament::section heading="Recent Payments (MYR)">
                <div class="divide-y divide-gray-200 dark:divide-white/10 text-xs">
                    @forelse ($recentPayments as $payment)
                        <div class="py-2.5 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-mono font-semibold text-gray-900 dark:text-white truncate">{{ $payment->order?->order_number ?? $payment->payment_reference }}</p>
                                <p class="text-gray-600 dark:text-gray-300 truncate">{{ $payment->user?->name ?? 'Customer' }}</p>
                                <p class="font-semibold text-gray-700 dark:text-gray-200">MYR {{ number_format((float) $payment->amount, 2) }}</p>
                            </div>
                            <x-filament::badge :color="$payment->status->badgeColor()">
                                {{ $payment->status->label() }}
                            </x-filament::badge>
                        </div>
                    @empty
                        <p class="py-4 text-center text-gray-400">No payment records yet.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <!-- Upcoming Sessions -->
            <x-filament::section heading="Upcoming Sessions">
                <div class="divide-y divide-gray-200 dark:divide-white/10 text-xs">
                    @forelse ($upcomingSessions as $session)
                        <div class="py-2.5 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $session->title }}</p>
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{ $session->starts_at?->timezone($tz)->format('d M Y, h:i A') }} MYT
                                </p>
                                <p class="text-gray-400 truncate">{{ $session->venue_city }}</p>
                            </div>
                            <span class="shrink-0 rounded bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                {{ $session->confirmed_seats_count }}/{{ $session->capacity }}
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-gray-400">No upcoming published sessions.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-widgets::widget>
