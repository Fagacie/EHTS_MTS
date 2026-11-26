<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle2, Users, Video } from 'lucide-vue-next';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import { useRoutes } from '@/composables/useRoutes';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorSessionsIndexProps } from '@/types/etms';

defineProps<InstructorSessionsIndexProps>();
const { routes } = useRoutes();

const filterOptions = [
    { label: 'All sessions', value: '' },
    { label: 'Published', value: 'published' },
    { label: 'In progress', value: 'in_progress' },
    { label: 'Completed', value: 'completed' },
];

function filterUrl(val: string): string {
    const base = routes.value.instructorSessions;
    return val ? `${base}?status=${encodeURIComponent(val)}` : base;
}

function statusTone(statusValue: string): 'success' | 'warning' | 'danger' | 'neutral' {
    if (statusValue === 'published' || statusValue === 'completed') return 'success';
    if (statusValue === 'full' || statusValue === 'registration_closed' || statusValue === 'in_progress') {
        return 'warning';
    }
    if (statusValue === 'cancelled') return 'danger';
    return 'neutral';
}
</script>

<template>
    <Head title="Assigned Sessions — Ecohydrotech Training" />

    <InstructorLayout breadcrumb="Assigned Sessions">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title text-ink">Assigned Sessions</h1>
                    <p class="mt-1 text-sm text-muted">
                        Physical training cohorts assigned to your instructor account.
                    </p>
                </div>

                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <Link
                        v-for="opt in filterOptions"
                        :key="opt.value"
                        :href="filterUrl(opt.value)"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="
                            statusFilter === opt.value || (!statusFilter && opt.value === '')
                                ? 'bg-navy-950 text-white'
                                : 'border border-line bg-surface text-muted hover:bg-sand-100 hover:text-ink'
                        "
                    >
                        {{ opt.label }}
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <section
                v-if="sessions.length === 0"
                class="rounded-2xl border border-line/85 bg-surface px-6 py-12 text-center shadow-card"
            >
                <CalendarDays class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                <p class="mt-2.5 text-sm font-semibold text-ink">No assigned sessions found</p>
                <p class="mx-auto mt-1 max-w-md text-xs text-muted">
                    You do not have any training sessions matching this status filter.
                </p>
                <div v-if="statusFilter" class="mt-4">
                    <Link
                        :href="routes.instructorSessions"
                        class="inline-flex h-9 items-center rounded-xl border border-line bg-surface px-4 text-xs font-medium text-ink transition hover:bg-sand-100"
                    >
                        Show all sessions
                    </Link>
                </div>
            </section>

            <!-- Sessions Table -->
            <section
                v-else
                class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card"
            >
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                <th class="px-5 py-3">Session</th>
                                <th class="px-4 py-3">Schedule &amp; Venue</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3 text-right">Seats</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/65">
                            <tr
                                v-for="session in sessions"
                                :key="session.id"
                                class="transition-colors hover:bg-sand-50/60"
                            >
                                <td class="px-5 py-4 align-top">
                                    <p class="font-semibold text-ink">
                                        <Link
                                            :href="session.showUrl"
                                            class="transition-colors hover:text-primary-600"
                                        >
                                            {{ session.title }}
                                        </Link>
                                    </p>
                                    <p class="tabular-nums-mono mt-0.5 text-xs text-muted">
                                        {{ session.sessionCode }}
                                        <span v-if="session.moduleCode"> · {{ session.moduleCode }}</span>
                                    </p>
                                </td>
                                <td class="px-4 py-4 align-top text-xs">
                                    <p class="font-medium text-ink">{{ session.scheduleLabel }}</p>
                                    <p class="mt-0.5 text-muted">
                                        {{ session.venueName }} ({{ session.venueCity }})
                                    </p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full bg-sand-100 px-2.5 py-0.5 text-xs font-medium text-ink">
                                        {{ session.roleLabel }}
                                    </span>
                                </td>
                                <td class="tabular px-4 py-4 text-right align-top">
                                    <p class="font-semibold text-ink">
                                        {{ session.confirmedParticipantsCount }} / {{ session.capacity }}
                                    </p>
                                    <p class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-muted">
                                        <Video class="size-3" aria-hidden="true" />
                                        {{ session.recordingsCount }} rec
                                    </p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <StatusBadge
                                        :tone="statusTone(session.statusValue)"
                                        :label="session.statusLabel"
                                    />
                                </td>
                                <td class="px-5 py-4 text-right align-top">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-1.5">
                                        <Link
                                            :href="session.showUrl"
                                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-surface px-2.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                                        >
                                            <Users class="size-3.5 text-muted" aria-hidden="true" />
                                            <span>View session</span>
                                        </Link>
                                        <Link
                                            :href="session.attendanceUrl"
                                            class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-navy-950 px-2.5 text-xs font-semibold text-white transition hover:bg-navy-900"
                                        >
                                            <CheckCircle2 class="size-3.5" aria-hidden="true" />
                                            <span>Attendance</span>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </InstructorLayout>
</template>
