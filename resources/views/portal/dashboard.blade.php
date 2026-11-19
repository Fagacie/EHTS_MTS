@php
    $tz = $timezone ?? config('etms.display_timezone', 'Asia/Kuala_Lumpur');
    $certificatesCount = $issuedCertificates->count();

    $summarySegments = array_filter([
        $activeEntitlementsCount > 0
            ? $activeEntitlementsCount.' '.\Illuminate\Support\Str::plural('active module', $activeEntitlementsCount)
            : null,
        $upcomingBookingsCount > 0
            ? $upcomingBookingsCount.' '.\Illuminate\Support\Str::plural('upcoming session', $upcomingBookingsCount)
            : null,
        $completedSessionsCount > 0
            ? $completedSessionsCount.' '.\Illuminate\Support\Str::plural('completed session', $completedSessionsCount)
            : null,
        $certificatesCount > 0
            ? $certificatesCount.' '.\Illuminate\Support\Str::plural('certificate', $certificatesCount).' earned'
            : null,
    ]);

    $summaryLine = count($summarySegments) > 0
        ? implode(' · ', $summarySegments)
        : 'Your training modules, upcoming sessions, and certificates.';
@endphp

<x-layouts.portal title="Dashboard" breadcrumb="Dashboard">
    <div class="mx-auto max-w-7xl space-y-8">
        <!-- Welcome Area -->
        <x-portal.page-header
            :title="'Welcome back, '.$user->name"
            :description="$summaryLine"
        >
            <x-slot:actions>
                <x-portal.button
                    href="{{ route('catalogue.index') }}"
                    :variant="$continueLearningUrl ? 'secondary' : 'primary'"
                    size="sm"
                >
                    Browse Training
                </x-portal.button>

                @if ($continueLearningUrl)
                    <x-portal.button
                        href="{{ $continueLearningUrl }}"
                        variant="primary"
                        size="sm"
                    >
                        Continue Learning
                    </x-portal.button>
                @endif
            </x-slot:actions>
        </x-portal.page-header>

        <!-- Pending Reservation Hold Notice (Conditional) -->
        @if ($activeHoldBooking)
            <x-portal.alert variant="warning" :title="'Pending Seat Reservation ('.$activeHoldBooking->booking_reference.')'">
                {{ $activeHoldBooking->trainingSession?->title }}
                @if ($activeHoldBooking->reservation_expires_at)
                    &middot; Expires {{ $activeHoldBooking->reservation_expires_at->timezone($tz)->format('d M Y, h:i A') }} MYT
                @endif
                <x-slot:action>
                    <x-portal.button href="{{ route('portal.bookings.show', $activeHoldBooking) }}" variant="secondary" size="sm">
                        View Reservation
                    </x-portal.button>
                </x-slot:action>
            </x-portal.alert>
        @endif

        <!-- Compact Filament-Inspired Statistics Bar -->
        <section aria-label="Training Summary">
            <div class="grid grid-cols-1 gap-px overflow-hidden rounded-xl bg-portal-border shadow-xs ring-1 ring-gray-950/5 sm:grid-cols-2 lg:grid-cols-4">
                <a
                    href="{{ route('portal.modules.index') }}"
                    class="block bg-portal-surface px-5 py-4.5 transition-colors hover:bg-portal-bg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-portal-primary"
                >
                    <div class="flex items-center gap-2 text-sm font-medium text-portal-muted">
                        <svg class="h-4 w-4 shrink-0 text-portal-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                        </svg>
                        <span>Active Learning Modules</span>
                    </div>
                    <p class="mt-2 text-[1.75rem] leading-tight font-semibold tracking-tight text-portal-text tabular-nums">
                        {{ $activeEntitlementsCount }}
                    </p>
                </a>

                <a
                    href="{{ route('portal.bookings.index') }}"
                    class="block bg-portal-surface px-5 py-4.5 transition-colors hover:bg-portal-bg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-portal-primary"
                >
                    <div class="flex items-center gap-2 text-sm font-medium text-portal-muted">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 2v4" />
                            <path d="M16 2v4" />
                            <rect width="18" height="18" x="3" y="4" rx="2" />
                            <path d="M3 10h18" />
                        </svg>
                        <span>Upcoming Confirmed Bookings</span>
                    </div>
                    <p class="mt-2 text-[1.75rem] leading-tight font-semibold tracking-tight text-portal-text tabular-nums">
                        {{ $upcomingBookingsCount }}
                    </p>
                </a>

                <a
                    href="{{ route('portal.bookings.index', ['status' => 'confirmed']) }}"
                    class="block bg-portal-surface px-5 py-4.5 transition-colors hover:bg-portal-bg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-portal-primary"
                >
                    <div class="flex items-center gap-2 text-sm font-medium text-portal-muted">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <span>Completed Training Sessions</span>
                    </div>
                    <p class="mt-2 text-[1.75rem] leading-tight font-semibold tracking-tight text-portal-text tabular-nums">
                        {{ $completedSessionsCount }}
                    </p>
                </a>

                <a
                    href="{{ route('portal.certificates.index') }}"
                    class="block bg-portal-surface px-5 py-4.5 transition-colors hover:bg-portal-bg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-portal-primary"
                >
                    <div class="flex items-center gap-2 text-sm font-medium text-portal-muted">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="8" r="6" />
                            <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                        </svg>
                        <span>Certificates Earned</span>
                    </div>
                    <p class="mt-2 text-[1.75rem] leading-tight font-semibold tracking-tight text-portal-text tabular-nums">
                        {{ $certificatesCount }}
                    </p>
                </a>
            </div>
        </section>

        <!-- Primary Operational Section: Upcoming Training -->
        <section aria-labelledby="heading-upcoming-training">
            <div class="mb-3.5 flex items-center justify-between gap-3">
                <h2 id="heading-upcoming-training" class="text-lg font-semibold text-portal-text sm:text-xl">
                    Upcoming Training
                </h2>

                @if ($upcomingBookings->isNotEmpty())
                    <a href="{{ route('portal.bookings.index') }}" class="text-sm font-medium text-portal-primary hover:underline">
                        All bookings
                    </a>
                @endif
            </div>

            <div class="overflow-hidden rounded-xl bg-portal-surface shadow-xs ring-1 ring-gray-950/5">
                @if ($upcomingBookings->isEmpty())
                    <div class="px-6 py-9 text-center">
                        <p class="text-[15px] font-medium text-portal-text">No upcoming training sessions</p>
                        <p class="mt-1 text-sm text-portal-muted">Confirmed physical sessions on your schedule will appear here.</p>
                        <div class="mt-4">
                            <x-portal.button href="{{ route('catalogue.index') }}" variant="secondary" size="sm">
                                Browse Training
                            </x-portal.button>
                        </div>
                    </div>
                @else
                    <div class="divide-y divide-portal-border">
                        @foreach ($upcomingBookings as $booking)
                            @php
                                $session = $booking->trainingSession;
                                $sessionTz = $session?->timezone ?: $tz;
                                $start = $session?->starts_at?->copy()->timezone($sessionTz);
                                $end = $session?->ends_at?->copy()->timezone($sessionTz);
                                $venue = collect([$session?->venue_name, $session?->venue_city])
                                    ->filter()
                                    ->implode(', ');
                            @endphp

                            <div class="grid gap-3 px-5 py-4 transition-colors hover:bg-portal-bg/60 md:grid-cols-12 md:items-center md:gap-4">
                                <div class="min-w-0 md:col-span-5">
                                    <p class="text-[15px] font-semibold text-portal-text">
                                        {{ $session?->title ?? 'Physical Training Session' }}
                                    </p>
                                    <p class="mt-0.5 text-[13px] text-portal-muted tabular-nums-mono">
                                        {{ $booking->booking_reference }}
                                    </p>
                                </div>

                                <div class="text-sm text-portal-muted md:col-span-3">
                                    @if ($start)
                                        <p class="font-medium text-portal-text">{{ $start->format('d M Y') }}</p>
                                        <p class="mt-0.5 tabular-nums">{{ $start->format('h:i A') }}@if ($end) – {{ $end->format('h:i A') }}@endif MYT</p>
                                    @else
                                        <span>Schedule TBA</span>
                                    @endif
                                </div>

                                <div class="min-w-0 text-sm text-portal-muted md:col-span-2">
                                    <p class="truncate">{{ $venue !== '' ? $venue : 'Venue TBA' }}</p>
                                </div>

                                <div class="flex items-center justify-between gap-3 md:col-span-2 md:justify-end">
                                    <x-portal.status-badge
                                        :status="$booking->status->value"
                                        :label="$booking->status->label()"
                                    />
                                    <a
                                        href="{{ route('portal.bookings.show', $booking) }}"
                                        class="shrink-0 text-sm font-medium text-portal-primary hover:underline"
                                    >
                                        View Booking
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <!-- Secondary Sections: Recent Activity & Certificates -->
        <div class="grid gap-8 lg:grid-cols-12">
            <!-- Recent Activity -->
            <section class="lg:col-span-7" aria-labelledby="heading-recent-activity">
                <div class="mb-3.5 flex items-center justify-between gap-3">
                    <h2 id="heading-recent-activity" class="text-lg font-semibold text-portal-text sm:text-xl">
                        Recent Activity
                    </h2>
                    @if ($recentActivities->isNotEmpty())
                        <a href="{{ route('portal.orders.index') }}" class="text-sm font-medium text-portal-primary hover:underline">
                            Orders &amp; Payments
                        </a>
                    @endif
                </div>

                @if ($recentActivities->isEmpty())
                    <div class="rounded-xl bg-portal-surface px-6 py-9 text-center shadow-xs ring-1 ring-gray-950/5">
                        <p class="text-[15px] font-medium text-portal-text">No recent activity</p>
                        <p class="mt-1 text-sm text-portal-muted">Session bookings and module orders will appear here.</p>
                    </div>
                @else
                    <!-- Desktop/Tablet Table -->
                    <div class="hidden md:block">
                        <x-portal.table>
                            <x-slot:head>
                                <tr>
                                    <th>Date</th>
                                    <th>Record</th>
                                    <th>Detail</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </x-slot:head>

                            @foreach ($recentActivities as $activity)
                                <tr>
                                    <td class="whitespace-nowrap text-[13px] text-portal-muted tabular-nums">
                                        {{ $activity['occurred_at']?->timezone($tz)->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="min-w-44">
                                        <p class="text-[15px] font-medium text-portal-text">{{ $activity['title'] }}</p>
                                        <p class="mt-0.5 text-[13px] text-portal-muted tabular-nums-mono">
                                            {{ $activity['kind'] }} &middot; {{ $activity['reference'] }}
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap text-sm text-portal-muted tabular-nums-mono">
                                        {{ $activity['detail'] }}
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <x-portal.status-badge
                                            :status="$activity['status_value']"
                                            :label="$activity['status_label']"
                                        />
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <a
                                            href="{{ $activity['url'] }}"
                                            class="text-sm font-medium text-portal-primary hover:underline"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </x-portal.table>
                    </div>

                    <!-- Mobile Stacked Cards -->
                    <div class="divide-y divide-portal-border overflow-hidden rounded-xl bg-portal-surface shadow-xs ring-1 ring-gray-950/5 md:hidden">
                        @foreach ($recentActivities as $activity)
                            <div class="space-y-2 px-4 py-3.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[15px] font-medium text-portal-text">{{ $activity['title'] }}</p>
                                        <p class="mt-0.5 text-[13px] text-portal-muted tabular-nums-mono">
                                            {{ $activity['kind'] }} &middot; {{ $activity['reference'] }}
                                        </p>
                                    </div>
                                    <x-portal.status-badge
                                        :status="$activity['status_value']"
                                        :label="$activity['status_label']"
                                    />
                                </div>
                                <div class="flex items-center justify-between gap-2 text-sm text-portal-muted">
                                    <span>{{ $activity['detail'] }} &middot; {{ $activity['occurred_at']?->timezone($tz)->format('d M Y') }}</span>
                                    <a href="{{ $activity['url'] }}" class="font-medium text-portal-primary hover:underline">
                                        View
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <!-- My Certificates -->
            <section class="lg:col-span-5" aria-labelledby="heading-certificates">
                <div class="mb-3.5 flex items-center justify-between gap-3">
                    <h2 id="heading-certificates" class="text-lg font-semibold text-portal-text sm:text-xl">
                        My Certificates
                    </h2>
                    @if ($issuedCertificates->isNotEmpty())
                        <a href="{{ route('portal.certificates.index') }}" class="text-sm font-medium text-portal-primary hover:underline">
                            View all
                        </a>
                    @endif
                </div>

                <div class="divide-y divide-portal-border overflow-hidden rounded-xl bg-portal-surface shadow-xs ring-1 ring-gray-950/5">
                    @if ($issuedCertificates->isEmpty())
                        <div class="px-6 py-9 text-center">
                            <p class="text-[15px] font-medium text-portal-text">No certificates issued yet</p>
                            <p class="mt-1 text-sm text-portal-muted">Completed sessions and modules earn official certificates here.</p>
                            <div class="mt-4">
                                <a href="{{ route('portal.certificates.index') }}" class="text-sm font-medium text-portal-primary hover:underline">
                                    Open certificates
                                </a>
                            </div>
                        </div>
                    @else
                        @foreach ($issuedCertificates->take(4) as $certificate)
                            <div class="flex items-start justify-between gap-4 px-5 py-4 transition-colors hover:bg-portal-bg/60">
                                <div class="min-w-0">
                                    <p class="text-[15px] font-semibold text-portal-text">
                                        {{ $certificate->training_title_snapshot }}
                                    </p>
                                    <p class="mt-1 text-[13px] text-portal-muted tabular-nums-mono">
                                        {{ $certificate->certificate_number }} &middot; {{ $certificate->issued_at?->timezone($tz)->format('d M Y') }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-3 pt-0.5 text-sm font-medium">
                                    <a
                                        href="{{ route('portal.certificates.show', $certificate) }}"
                                        class="text-portal-muted hover:text-portal-text"
                                    >
                                        View
                                    </a>
                                    <a
                                        href="{{ route('certificates.download', $certificate) }}"
                                        class="text-portal-primary hover:underline"
                                    >
                                        Download PDF
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-layouts.portal>
