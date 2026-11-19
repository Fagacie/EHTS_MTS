<x-layouts.portal title="Session Recordings" breadcrumb="Session Recordings">
    <div class="mx-auto max-w-6xl space-y-8">
        <x-portal.page-header
            title="Physical Session Recordings"
            description="Access session recordings after your attendance is confirmed as Present and verified."
        >
            <x-slot:actions>
                <x-portal.button href="{{ route('portal.modules.index') }}" variant="secondary" size="sm">
                    My Learning
                </x-portal.button>
                <x-portal.button href="{{ route('portal.bookings.index') }}" variant="secondary" size="sm">
                    My Bookings
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        @if ($bookingItems->isEmpty())
            <x-portal.empty-state
                title="No physical session bookings found"
                description="Register and attend a physical training session to unlock post-session recordings once verified."
            >
                <x-slot:action>
                    <x-portal.button href="{{ route('sessions.index') }}" variant="primary">
                        Browse Physical Sessions
                    </x-portal.button>
                </x-slot:action>
            </x-portal.empty-state>
        @else
            <div class="space-y-6">
                @foreach ($bookingItems as $item)
                    @php
                        /** @var \App\Models\Booking $booking */
                        $booking = $item['booking'];
                        /** @var \App\Models\TrainingSession|null $session */
                        $session = $item['session'];
                        $recordings = $item['recordings'];
                        $isEligible = $item['isEligible'];
                        $cleanDenialReason = trim((string) preg_replace(
                            '/\s*\((?:[A-Z]+-\d+|recording_access_policy\s*=\s*[^)]+)\)/',
                            '',
                            (string) ($item['denialReason'] ?? ''),
                        ));
                    @endphp

                    <x-portal.card
                        :title="($session?->session_code ?? 'Session').' — '.($session?->title ?? 'Physical Training Session')"
                        :subtitle="'Booking Ref: '.$booking->booking_reference.' · '.($session?->formattedSchedule() ?? 'Schedule TBA')"
                    >
                        @if (! $isEligible)
                            <x-portal.alert variant="warning" title="Recording Access Locked">
                                {{ $cleanDenialReason }}
                            </x-portal.alert>
                        @elseif ($recordings->isEmpty())
                            <p class="text-[15px] leading-relaxed text-portal-muted">
                                Your attendance is verified. Session recordings will appear here as soon as they are published.
                            </p>
                        @else
                            <div class="divide-y divide-portal-border">
                                @foreach ($recordings as $recording)
                                    <div class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0 space-y-1">
                                            <div class="flex flex-wrap items-center gap-2.5">
                                                <span class="text-[15px] font-semibold text-portal-text">{{ $recording->title }}</span>
                                                <x-portal.status-badge status="published" label="Available" />
                                            </div>
                                            @if ($recording->description)
                                                <p class="text-sm leading-relaxed text-portal-muted">{{ $recording->description }}</p>
                                            @endif
                                            <p class="text-[13px] text-portal-muted tabular-nums-mono">
                                                Duration: {{ $recording->formattedDuration() }} &middot; Status: {{ $recording->encoding_status->label() }}
                                            </p>
                                        </div>

                                        <div class="shrink-0">
                                            @if ($recording->encoding_status === \App\Enums\EncodingStatus::Ready)
                                                <x-portal.button href="{{ $recording->temporaryWatchUrl(30) }}" variant="primary" size="sm">
                                                    Watch Recording
                                                </x-portal.button>
                                            @else
                                                <x-portal.status-badge status="processing" :label="$recording->encoding_status->label()" />
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-portal.card>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.portal>
