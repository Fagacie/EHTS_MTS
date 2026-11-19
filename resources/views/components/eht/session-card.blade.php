@props([
    'session',
])

@php
    $remaining = $session->remainingSeats();
    $occupied = $session->occupiedSeats();
    $occupancyPct = $session->capacity > 0 ? min(100, (int) round(($occupied / $session->capacity) * 100)) : 100;
@endphp

<article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:border-eht-teal-600/60 hover:shadow-md">
    <div>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 tabular-nums-mono">
                {{ $session->session_code }}
            </span>
            <x-eht.status-badge :status="$session->status->value" :label="$session->status->label()" />
        </div>

        @if ($session->module)
            <p class="mt-2.5 text-xs font-semibold text-eht-teal-700">
                {{ $session->module->code }} · {{ $session->module->title }}
            </p>
        @endif

        <h3 class="mt-1 text-base font-bold text-slate-900">
            <a href="{{ route('sessions.show', $session) }}" class="hover:text-eht-navy-700">
                {{ $session->title }}
            </a>
        </h3>

        <dl class="mt-3 space-y-1.5 text-xs text-slate-600">
            <div class="flex items-start gap-2">
                <span class="font-semibold text-slate-800 shrink-0">Schedule:</span>
                <span>{{ $session->formattedSchedule() }} ({{ $session->formattedDuration() }})</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="font-semibold text-slate-800 shrink-0">Venue:</span>
                <span>{{ $session->venue_name }}, {{ $session->venue_city }}, {{ $session->venue_state }}</span>
            </div>
            @if ($session->relationLoaded('instructors') && $session->instructors->isNotEmpty())
                <div class="flex items-start gap-2">
                    <span class="font-semibold text-slate-800 shrink-0">Instructors:</span>
                    <span>
                        {{ $session->instructors->map(fn ($i) => $i->instructorProfile?->display_name ?? $i->name)->join(', ') }}
                    </span>
                </div>
            @endif
        </dl>

        <!-- Seat Availability Meter -->
        <div class="mt-4 rounded-xl bg-slate-50 p-3 border border-slate-100">
            <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-700">Seat Availability</span>
                <span class="font-bold tabular-nums-mono {{ $remaining > 0 ? 'text-eht-emerald-700' : 'text-red-700' }}">
                    {{ $remaining }} of {{ $session->capacity }} seats remaining
                </span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                <div
                    class="h-full rounded-full {{ $remaining === 0 ? 'bg-red-600' : ($remaining <= 5 ? 'bg-amber-500' : 'bg-eht-teal-600') }}"
                    style="width: {{ $occupancyPct }}%"
                ></div>
            </div>
            <p class="mt-1.5 text-[11px] text-slate-500">
                Reg closes: <span class="font-medium text-slate-700">{{ $session->registration_closes_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT</span>
            </p>
        </div>
    </div>

    <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-slate-400">Session Fee</p>
            <p class="text-lg font-bold text-slate-900 tabular-nums-mono">{{ $session->formattedPrice() }}</p>
        </div>

        <div class="flex items-center gap-2">
            <x-eht.button href="{{ route('sessions.show', $session) }}" variant="outline" size="sm">
                Details
            </x-eht.button>
            @if ($session->isOpenForBooking())
                <x-eht.button href="{{ route('checkout.session.show', $session) }}" variant="primary" size="sm">
                    Book Seat
                </x-eht.button>
            @endif
        </div>
    </div>
</article>
