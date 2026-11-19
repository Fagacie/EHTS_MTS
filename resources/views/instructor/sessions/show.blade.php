<x-layouts.instructor :title="$session->session_code.' — '.$session->title" :breadcrumb="$session->session_code">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-slate-200 px-2.5 py-1 text-xs font-bold text-slate-900 tabular-nums-mono">
                        {{ $session->session_code }}
                    </span>
                    <x-eht.status-badge :status="$session->status->value" :label="$session->status->label()" />
                </div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $session->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Module: <strong>{{ $session->module?->code }} — {{ $session->module?->title }}</strong>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-eht.button href="{{ route('instructor.sessions.attendance', $session) }}" variant="primary" size="sm">
                    Record &amp; Submit Attendance
                </x-eht.button>
                <x-eht.button href="{{ route('instructor.sessions.completion', $session) }}" variant="outline" size="sm">
                    Completion Report
                </x-eht.button>
                <x-eht.button href="{{ route('instructor.sessions.recordings', $session) }}" variant="outline" size="sm">
                    Session Recordings ({{ $session->recordings->count() }})
                </x-eht.button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-8 space-y-6">
                <!-- Confirmed Participant Roster (INS-03) -->
                <x-eht.card title="Confirmed Participant Roster ({{ $session->confirmedBookings->count() }} / {{ $session->capacity }})" subtitle="Participants holding confirmed bookings for this physical session (commercial payment details hidden per SRS Sec. 2.2)">
                    @if ($session->confirmedBookings->isEmpty())
                        <p class="text-xs text-slate-500">No confirmed participants are currently enrolled in this session.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead>
                                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <th class="pb-3 pr-4">Booking Ref</th>
                                        <th class="pb-3 px-4">Participant</th>
                                        <th class="pb-3 px-4">Organisation</th>
                                        <th class="pb-3 px-4">Attendance</th>
                                        <th class="pb-3 pl-4">Verification</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($session->confirmedBookings as $booking)
                                        <tr>
                                            <td class="py-3.5 pr-4 font-bold text-slate-900 tabular-nums-mono">
                                                {{ $booking->booking_reference }}
                                            </td>
                                            <td class="py-3.5 px-4">
                                                <p class="font-semibold text-slate-900">{{ $booking->user?->name }}</p>
                                                <p class="text-xs text-slate-500 font-mono">{{ $booking->user?->email }}</p>
                                            </td>
                                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                                {{ $booking->user?->customerProfile?->company_name ?: 'Individual' }}
                                            </td>
                                            <td class="py-3.5 px-4">
                                                @if ($booking->attendance)
                                                    <x-eht.status-badge
                                                        :status="$booking->attendance->attendance_status->value"
                                                        :label="$booking->attendance->attendance_status->label()"
                                                    />
                                                @else
                                                    <x-eht.status-badge status="pending" label="Pending" />
                                                @endif
                                            </td>
                                            <td class="py-3.5 pl-4 text-xs font-medium text-slate-700">
                                                {{ $booking->attendance?->review_status->label() ?? 'Draft' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-eht.card>

                <!-- Completion Notes Preview -->
                <x-eht.card title="Instructor Completion Notes" subtitle="Delivery summary and operational remarks for administrators">
                    @if ($session->completion_notes)
                        <p class="text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ $session->completion_notes }}</p>
                        @if ($session->completed_at)
                            <p class="mt-2 text-xs text-slate-400 tabular-nums-mono">
                                Recorded at {{ $session->completed_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT
                            </p>
                        @endif
                    @else
                        <p class="text-xs text-slate-500">No session completion report has been submitted yet.</p>
                    @endif
                </x-eht.card>
            </div>

            <aside class="lg:col-span-4 space-y-6">
                <x-eht.card title="Schedule &amp; Venue Logistics">
                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="font-semibold uppercase tracking-wider text-slate-500">Date &amp; Time (MYT)</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $session->formattedSchedule() }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wider text-slate-500">Duration</dt>
                            <dd class="mt-1 font-medium text-slate-800">{{ $session->formattedDuration() }} ({{ $session->duration_minutes }} min)</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wider text-slate-500">Venue</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $session->venue_name }}</dd>
                            <dd class="mt-0.5 text-slate-600">{{ $session->venue_address }}, {{ $session->venue_city }}, {{ $session->venue_state }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wider text-slate-500">Recording Policy</dt>
                            <dd class="mt-1 font-medium text-slate-800">{{ $session->recording_access_policy->label() }}</dd>
                        </div>
                    </dl>
                </x-eht.card>
            </aside>
        </div>
    </div>
</x-layouts.instructor>
