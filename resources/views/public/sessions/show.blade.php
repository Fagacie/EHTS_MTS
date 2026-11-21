<x-layouts.public :title="$session->title">
    @php
        $remaining = $session->remainingSeats();
        $occupied = $session->occupiedSeats();
        $occupancyPct = $session->capacity > 0 ? min(100, (int) round(($occupied / $session->capacity) * 100)) : 100;
    @endphp

    <!-- Header Banner -->
    <div class="border-b border-slate-200 bg-eht-navy-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-300" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <span>/</span>
                <a href="{{ route('sessions.index') }}" class="hover:text-white">Physical Sessions</a>
                @if ($session->module)
                    <span>/</span>
                    <a href="{{ route('catalogue.modules.show', $session->module) }}" class="hover:text-white">
                        {{ $session->module->code }}
                    </a>
                @endif
                <span>/</span>
                <span class="font-semibold text-eht-teal-200 tabular-nums-mono">{{ $session->session_code }}</span>
            </nav>

            <div class="mt-4 flex flex-wrap items-center gap-2.5">
                <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-semibold text-eht-teal-200 tabular-nums-mono">
                    {{ $session->session_code }}
                </span>
                @if ($session->module?->category)
                    <span class="rounded-full bg-eht-teal-600/25 px-3 py-1 text-xs font-semibold text-eht-teal-100">
                        {{ $session->module->category->name }}
                    </span>
                @endif
                <x-eht.status-badge :status="$session->status->value" :label="$session->status->label()" />
            </div>

            <h1 class="mt-3 text-2xl font-bold tracking-tight text-white sm:text-4xl">
                {{ $session->title }}
            </h1>

            @if ($session->module)
                <p class="mt-2 text-sm text-slate-300">
                    Module: <a href="{{ route('catalogue.modules.show', $session->module) }}" class="font-semibold text-eht-teal-200 underline hover:text-white">{{ $session->module->code }} — {{ $session->module->title }}</a>
                </p>
            @endif
        </div>
    </div>

    <!-- Main 8 / 4 Split -->
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-12">
            <!-- Left 8-Col Logistics, Syllabus & Instructors -->
            <div class="lg:col-span-8 space-y-8">
                <x-eht.card title="Schedule, Venue &amp; Delivery Logistics" subtitle="All times shown in Malaysia Standard Time (Asia/Kuala_Lumpur)">
                    <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Date &amp; Time (MYT)</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $session->formattedSchedule() }}</dd>
                            <dd class="mt-0.5 text-xs text-slate-500">Duration: {{ $session->formattedDuration() }} ({{ $session->duration_minutes }} minutes)</dd>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Registration Deadline</dt>
                            <dd class="mt-1 font-semibold text-slate-900 tabular-nums-mono">
                                {{ $session->registration_closes_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT
                            </dd>
                            <dd class="mt-0.5 text-xs text-slate-500">
                                Strict pre-session deadline enforced (D-029)
                            </dd>
                        </div>

                        <div class="sm:col-span-2 rounded-xl bg-slate-50 p-4 border border-slate-100">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Physical Venue</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $session->venue_name }}</dd>
                            <dd class="mt-0.5 text-xs text-slate-600">
                                {{ $session->venue_address }}, {{ $session->venue_city }}, {{ $session->venue_state }}, Malaysia
                            </dd>
                        </div>
                    </dl>

                    @if ($session->description)
                        <div class="mt-5 border-t border-slate-100 pt-4 text-sm leading-relaxed text-slate-700 whitespace-pre-line">
                            {{ $session->description }}
                        </div>
                    @endif
                </x-eht.card>

                <!-- Assigned Instructors -->
                <x-eht.card title="Assigned Session Instructors ({{ $session->instructors->count() }})" subtitle="Industry specialists leading this physical cohort">
                    @if ($session->instructors->isEmpty())
                        <p class="text-xs text-slate-500">Instructor assignments for this cohort will be announced shortly.</p>
                    @else
                        <div class="divide-y divide-slate-200">
                            @foreach ($session->instructors as $instructorUser)
                                @php
                                    $profile = $instructorUser->instructorProfile;
                                    $displayName = $profile?->display_name ?: $instructorUser->name;
                                @endphp
                                <div class="flex flex-wrap items-start justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($profile && $profile->is_public)
                                                <a href="{{ route('instructors.show', $profile) }}" class="text-sm font-bold text-slate-900 hover:text-eht-navy-700 underline">
                                                    {{ $displayName }}
                                                </a>
                                            @else
                                                <span class="text-sm font-bold text-slate-900">{{ $displayName }}</span>
                                            @endif
                                            <x-eht.status-badge status="info" :label="$instructorUser->pivot->role_label ?? 'Instructor'" />
                                        </div>
                                        @if ($profile?->professional_title)
                                            <p class="mt-0.5 text-xs font-medium text-eht-teal-700">{{ $profile->professional_title }}</p>
                                        @endif
                                        @if ($profile?->qualifications)
                                            <p class="mt-1 text-xs text-slate-500">{{ $profile->qualifications }}</p>
                                        @endif
                                    </div>

                                    @if ($profile && $profile->is_public)
                                        <x-eht.button href="{{ route('instructors.show', $profile) }}" variant="outline" size="sm">
                                            View Profile
                                        </x-eht.button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-eht.card>

                @if ($session->module)
                    <x-eht.card title="Linked Module Overview: {{ $session->module->code }}" subtitle="{{ $session->module->title }}">
                        <p class="text-sm leading-relaxed text-slate-700">
                            {{ $session->module->summary ?: $session->module->description }}
                        </p>
                        <div class="mt-4">
                            <x-eht.button href="{{ route('catalogue.modules.show', $session->module) }}" variant="outline" size="sm">
                                View Full Module Syllabus &amp; Materials
                            </x-eht.button>
                        </div>
                    </x-eht.card>
                @endif
            </div>

            <!-- Right 4-Col Sticky Booking Card -->
            <aside class="lg:col-span-4 space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="rounded-lg bg-eht-navy-50 px-2.5 py-1 text-xs font-semibold text-eht-navy-700">Physical Seat Booking</span>
                        <x-eht.status-badge :status="$session->status->value" :label="$session->status->label()" />
                    </div>

                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-slate-900 tabular-nums-mono">{{ $session->formattedPrice() }}</span>
                        <span class="text-xs text-slate-500">per participant (1 seat)</span>
                    </div>

                    <!-- Capacity Meter -->
                    <div class="mt-5 rounded-xl bg-slate-50 p-4 border border-slate-100">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">Available Capacity</span>
                            <span class="font-bold tabular-nums-mono {{ $remaining > 0 ? 'text-eht-emerald-700' : 'text-red-700' }}">
                                {{ $remaining }} / {{ $session->capacity }} seats open
                            </span>
                        </div>
                        <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-200">
                            <div
                                class="h-full rounded-full {{ $remaining === 0 ? 'bg-red-600' : ($remaining <= 5 ? 'bg-amber-500' : 'bg-eht-teal-600') }}"
                                style="width: {{ $occupancyPct }}%"
                            ></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500 tabular-nums-mono">
                            <span>Confirmed: {{ $session->confirmed_seats_count }}</span>
                            <span>15m Holds: {{ $session->reserved_seats_count }}</span>
                        </div>
                    </div>

                    <ul class="mt-4 space-y-2 text-xs text-slate-600">
                        <li class="flex items-center gap-2">
                            <span class="font-bold text-eht-emerald-600">&check;</span>
                            <span>15-minute seat reservation hold during checkout (D-019)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="font-bold text-eht-emerald-600">&check;</span>
                            <span>Eligible for QR-verifiable Certificate upon verified attendance (D-007)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="font-bold text-eht-emerald-600">&check;</span>
                            <span>Recording Policy: {{ $session->recording_access_policy->label() }}</span>
                        </li>
                    </ul>

                    <div class="mt-6">
                        @if (isset($existingBooking) && $existingBooking)
                            <div class="rounded-xl border border-eht-teal-200 bg-eht-teal-50/70 p-4 text-xs text-eht-navy-900">
                                <p class="font-bold">You have an active booking for this session:</p>
                                <p class="mt-1 tabular-nums-mono">Ref: {{ $existingBooking->booking_reference }} ({{ $existingBooking->status->label() }})</p>
                                <div class="mt-3">
                                    <x-eht.button href="{{ route('portal.bookings.show', $existingBooking) }}" variant="primary" size="sm" class="w-full">
                                        View My Booking
                                    </x-eht.button>
                                </div>
                            </div>
                        @elseif ($session->isOpenForBooking())
                            @auth
                                @if (auth()->user()->isCustomer())
                                    <x-eht.button href="{{ route('checkout.session.show', $session) }}" variant="primary" class="w-full">
                                        Proceed to Seat Booking ({{ $session->formattedPrice() }})
                                    </x-eht.button>
                                @else
                                    <p class="text-xs text-slate-600">
                                        Your account does not currently hold the Customer/Trainee role required to book training sessions.
                                    </p>
                                @endif
                            @else
                                <x-eht.button href="{{ route('login') }}" variant="primary" class="w-full">
                                    Sign In to Reserve Seat ({{ $session->formattedPrice() }})
                                </x-eht.button>
                            @endauth
                        @else
                            <x-eht.button type="button" variant="outline" class="w-full" disabled>
                                @if ($remaining === 0 || $session->status === \App\Enums\SessionStatus::Full)
                                    Session Full — No Seats Remaining
                                @else
                                    Registration Closed
                                @endif
                            </x-eht.button>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.public>
