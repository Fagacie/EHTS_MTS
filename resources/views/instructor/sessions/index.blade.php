<x-layouts.instructor title="Assigned Sessions" breadcrumb="Assigned Sessions">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">My Assigned Training Sessions</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Physical cohorts assigned to your instructor account (INS-02). Commercial pricing and payment data are hidden per SRS Section 2.2.
                </p>
            </div>
        </div>

        @if ($sessions->isEmpty())
            <x-eht.empty-state
                title="No assigned sessions found"
                description="You do not currently have any physical sessions matching this filter."
            />
        @else
            <x-eht.card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead>
                            <tr class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="pb-3 pr-4">Session Code</th>
                                <th class="pb-3 px-4">Session &amp; Module</th>
                                <th class="pb-3 px-4">Schedule &amp; Venue</th>
                                <th class="pb-3 px-4">Role</th>
                                <th class="pb-3 px-4">Roster</th>
                                <th class="pb-3 px-4">Status</th>
                                <th class="pb-3 pl-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($sessions as $session)
                                <tr>
                                    <td class="py-4 pr-4 align-top font-bold text-slate-900 tabular-nums-mono">
                                        {{ $session->session_code }}
                                    </td>
                                    <td class="py-4 px-4 align-top">
                                        <p class="font-semibold text-slate-900">{{ $session->title }}</p>
                                        <p class="mt-0.5 text-xs text-eht-teal-700 tabular-nums-mono">
                                            {{ $session->module?->code }} — {{ $session->module?->title }}
                                        </p>
                                    </td>
                                    <td class="py-4 px-4 align-top text-xs text-slate-600">
                                        <p class="font-medium text-slate-800">{{ $session->formattedSchedule() }}</p>
                                        <p class="mt-0.5">{{ $session->venue_name }} ({{ $session->venue_city }})</p>
                                    </td>
                                    <td class="py-4 px-4 align-top">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                            {{ $session->pivot->role_label ?? 'Instructor' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 align-top font-semibold text-slate-900 tabular-nums-mono">
                                        {{ $session->confirmed_participants_count }} / {{ $session->capacity }}
                                    </td>
                                    <td class="py-4 px-4 align-top">
                                        <x-eht.status-badge :status="$session->status->value" :label="$session->status->label()" />
                                    </td>
                                    <td class="py-4 pl-4 align-top text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <x-eht.button href="{{ route('instructor.sessions.show', $session) }}" variant="outline" size="sm">
                                                Roster
                                            </x-eht.button>
                                            <x-eht.button href="{{ route('instructor.sessions.attendance', $session) }}" variant="primary" size="sm">
                                                Attendance
                                            </x-eht.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-eht.card>
        @endif
    </div>
</x-layouts.instructor>
