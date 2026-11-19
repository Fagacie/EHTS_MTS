<x-layouts.instructor title="Instructor Dashboard" breadcrumb="Dashboard">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Instructor Workspace, {{ $user->instructorProfile?->display_name ?: $user->name }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Assigned physical sessions, participant rosters, attendance submission, completion notes &amp; recording submission (INS-01).
                </p>
            </div>
            <div class="flex items-center gap-2">
                @foreach ($user->roles as $role)
                    <x-eht.status-badge :status="$role->slug" :label="$role->name" />
                @endforeach
                @if ($user->instructorProfile?->is_public)
                    <x-eht.status-badge status="published" label="Public Directory Active" />
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-eht.stat-card label="Assigned Sessions" value="{{ $assignedSessions->count() }}" meta="Physical cohorts assigned to you" accent="navy" />
            <x-eht.stat-card label="Attendance Pending" value="{{ $pendingAttendanceCount ?? 0 }}" meta="Sessions awaiting attendance submission" accent="amber" />
            <x-eht.stat-card label="Submitted Recordings" value="{{ $submittedRecordingsCount ?? 0 }}" meta="Session recordings submitted" accent="teal" />
        </div>

        @if ($assignedSessions->isEmpty())
            <x-eht.empty-state
                title="No assigned physical sessions yet"
                description="When administrators schedule physical training sessions and assign you as an instructor, your rosters, attendance sheets, and recording uploads appear here."
            />
        @else
            <x-eht.card title="My Assigned Training Sessions" subtitle="Select a session to inspect the participant roster, mark attendance, submit completion notes, or upload recording metadata">
                <div class="divide-y divide-slate-200">
                    @foreach ($assignedSessions as $session)
                        <div class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-800 tabular-nums-mono">
                                        {{ $session->session_code }}
                                    </span>
                                    <x-eht.status-badge :status="$session->status->value" :label="$session->status->label()" />
                                    <span class="rounded-full bg-eht-teal-50 px-2.5 py-0.5 text-xs font-semibold text-eht-teal-800">
                                        {{ $session->pivot->role_label ?? 'Instructor' }}
                                    </span>
                                </div>
                                <h2 class="mt-1.5 text-base font-bold text-slate-900">
                                    <a href="{{ route('instructor.sessions.show', $session) }}" class="hover:text-eht-navy-700">
                                        {{ $session->title }}
                                    </a>
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-600">
                                    {{ $session->formattedSchedule() }} · {{ $session->venue_name }} ({{ $session->venue_city }})
                                </p>
                                <p class="mt-1 text-xs text-slate-500 tabular-nums-mono">
                                    Confirmed Participants: <strong>{{ $session->confirmed_participants_count }}</strong> / {{ $session->capacity }}
                                    · Recordings: <strong>{{ $session->recordings_count }}</strong>
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-eht.button href="{{ route('instructor.sessions.show', $session) }}" variant="outline" size="sm">
                                    Roster &amp; Details
                                </x-eht.button>
                                <x-eht.button href="{{ route('instructor.sessions.attendance', $session) }}" variant="primary" size="sm">
                                    Attendance
                                </x-eht.button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-eht.card>
        @endif
    </div>
</x-layouts.instructor>
