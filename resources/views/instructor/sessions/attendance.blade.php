<x-layouts.instructor :title="'Attendance — '.$session->session_code" :breadcrumb="$session->session_code.' / Attendance'">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-eht-teal-700">Attendance Recording &amp; Submission (INS-04, FR-16, D-020)</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $session->session_code }} — {{ $session->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $session->formattedSchedule() }} · {{ $session->venue_name }}</p>
            </div>
            <x-eht.button href="{{ route('instructor.sessions.show', $session) }}" variant="outline" size="sm">
                &larr; Back to Session Roster
            </x-eht.button>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs leading-relaxed text-slate-600">
            <strong class="text-slate-900">Two-Step Attendance Governance (D-020):</strong>
            Mark each confirmed participant as <strong>Present</strong>, <strong>Absent</strong>, or <strong>Excused</strong> and save your draft. Once every participant on the roster has a marked status (no rows left as <em>Pending</em>), submit the sheet for Administrator Verification.
        </div>

        @if ($attendances->isEmpty())
            <x-eht.empty-state
                title="No confirmed participants on this roster"
                description="Attendance rows appear automatically once participants hold confirmed bookings for this session."
            />
        @else
            <x-eht.card title="Participant Attendance Sheet ({{ $attendances->count() }})" subtitle="Save draft changes or submit completed sheet for administrator verification">
                <form method="POST" action="{{ route('instructor.sessions.attendance.update', $session) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead>
                                <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="pb-3 pr-4">Booking Ref</th>
                                    <th class="pb-3 px-4">Participant</th>
                                    <th class="pb-3 px-4">Attendance Status</th>
                                    <th class="pb-3 px-4">Instructor Remarks</th>
                                    <th class="pb-3 pl-4">Review State</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($attendances as $attendance)
                                    <tr>
                                        <td class="py-3.5 pr-4 font-bold text-slate-900 tabular-nums-mono">
                                            {{ $attendance->booking?->booking_reference }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <p class="font-semibold text-slate-900">{{ $attendance->user?->name }}</p>
                                            <p class="text-xs text-slate-500 font-mono">{{ $attendance->user?->email }}</p>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <select
                                                name="records[{{ $attendance->id }}][attendance_status]"
                                                @disabled($isLockedVerified)
                                                class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                                            >
                                                @foreach (\App\Enums\AttendanceStatus::cases() as $statusOption)
                                                    <option value="{{ $statusOption->value }}" @selected($attendance->attendance_status === $statusOption)>
                                                        {{ $statusOption->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <input
                                                type="text"
                                                name="records[{{ $attendance->id }}][remarks]"
                                                value="{{ old("records.{$attendance->id}.remarks", $attendance->remarks) }}"
                                                @disabled($isLockedVerified)
                                                placeholder="Optional note (e.g. arrived on time)"
                                                class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-xs text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                                            >
                                        </td>
                                        <td class="py-3.5 pl-4 text-xs font-semibold text-slate-700">
                                            {{ $attendance->review_status->label() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (! $isLockedVerified)
                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-4">
                            <x-eht.button type="submit" variant="outline">
                                Save Attendance Draft
                            </x-eht.button>
                        </div>
                    @endif
                </form>

                @if (! $isLockedVerified)
                    <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs text-slate-600">
                            @if ($allMarked)
                                <span class="font-semibold text-eht-emerald-700">All participants marked.</span> You can now submit this attendance sheet for Administrator Verification.
                            @else
                                <span class="font-semibold text-amber-800">Action required before submission:</span> Save a non-pending attendance status for every participant first.
                            @endif
                        </div>

                        <form method="POST" action="{{ route('instructor.sessions.attendance.submit', $session) }}">
                            @csrf
                            <x-eht.button type="submit" variant="primary">
                                Submit Attendance for Verification
                            </x-eht.button>
                        </form>
                    </div>
                @endif
            </x-eht.card>
        @endif
    </div>
</x-layouts.instructor>
