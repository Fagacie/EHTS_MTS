<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    Filter,
    Users,
} from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useRoutes } from '@/composables/useRoutes';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorSessionsIndexProps } from '@/types/etms';

const props = defineProps<InstructorSessionsIndexProps>();
const { routes } = useRoutes();

const filterOptions = [
    { label: 'All Sessions', value: '' },
    { label: 'Published', value: 'published' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Completed', value: 'completed' },
];

function filterUrl(val: string): string {
    const base = routes.value.instructorSessions;
    return val ? `${base}?status=${encodeURIComponent(val)}` : base;
}
</script>

<template>
    <Head title="Assigned Sessions — Ecohydrotech Training" />

    <InstructorLayout
        breadcrumb="Assigned Sessions"
        title="My Assigned Training Sessions"
        subtitle="Physical cohorts assigned to your instructor account (INS-02). Commercial pricing and payment data are hidden per SRS Section 2.2."
    >
        <div class="space-y-6">
            <!-- Filter Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#E5E1EB] bg-white p-4 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-semibold text-[#625D6D]">
                    <Filter class="h-4 w-4 text-[#7057D9]" />
                    Filter by Status:
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <Link
                        v-for="opt in filterOptions"
                        :key="opt.value"
                        :href="filterUrl(opt.value)"
                        :class="[
                            'rounded-xl px-3 py-1.5 text-xs font-semibold transition',
                            (statusFilter === opt.value) || (!statusFilter && opt.value === '')
                                ? 'bg-[#7057D9] text-white shadow-none'
                                : 'border border-[#E5E1EB] bg-[#F7F6FA] text-[#625D6D] hover:bg-[#EAE5F8] hover:text-[#5942B5]'
                        ]"
                    >
                        {{ opt.label }}
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="sessions.length === 0"
                class="rounded-2xl border border-[#E5E1EB] bg-white p-12 text-center shadow-sm"
            >
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1EDFB] text-[#7057D9]">
                    <CalendarDays class="h-6 w-6" />
                </div>
                <h3 class="mt-3 text-base font-bold text-[#292536]">No assigned sessions found</h3>
                <p class="mx-auto mt-1 max-w-md text-xs text-[#625D6D]">
                    You do not currently have any physical sessions matching this status filter.
                </p>
                <div v-if="statusFilter" class="mt-4">
                    <Button as-child variant="outline" size="sm" class="rounded-xl border-[#E5E1EB] text-xs font-semibold">
                        <Link :href="routes.instructorSessions">
                            Clear Filter
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Sessions Table Card (Desktop) & Cards (Mobile) -->
            <div v-else class="rounded-2xl border border-[#E5E1EB] bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#E5E1EB] text-left text-sm">
                        <thead class="bg-[#F7F6FA]">
                            <tr class="text-[11px] font-bold uppercase tracking-wider text-[#625D6D]">
                                <th class="py-3.5 pl-6 pr-4">Session Code</th>
                                <th class="py-3.5 px-4">Session & Module</th>
                                <th class="py-3.5 px-4">Schedule & Venue</th>
                                <th class="py-3.5 px-4">Role</th>
                                <th class="py-3.5 px-4">Roster</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 pl-4 pr-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E1EB] bg-white">
                            <tr
                                v-for="session in sessions"
                                :key="session.id"
                                class="transition hover:bg-[#F7F6FA]/50"
                            >
                                <td class="py-4 pl-6 pr-4 align-top">
                                    <span class="rounded-md bg-[#F1EDFB] px-2.5 py-1 font-mono text-xs font-bold text-[#5942B5]">
                                        {{ session.sessionCode }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 align-top">
                                    <p class="font-bold text-[#292536]">
                                        <Link :href="session.showUrl" class="transition hover:text-[#7057D9]">
                                            {{ session.title }}
                                        </Link>
                                    </p>
                                    <p v-if="session.moduleCode" class="mt-0.5 font-mono text-xs font-semibold text-[#5942B5]">
                                        {{ session.moduleCode }} — {{ session.moduleTitle }}
                                    </p>
                                </td>
                                <td class="py-4 px-4 align-top text-xs text-[#625D6D]">
                                    <p class="font-medium text-[#292536]">{{ session.scheduleLabel }}</p>
                                    <p class="mt-0.5">{{ session.venueName }} ({{ session.venueCity }})</p>
                                </td>
                                <td class="py-4 px-4 align-top">
                                    <span class="rounded-full bg-[#EAE5F8] px-2.5 py-0.5 text-xs font-semibold text-[#5942B5]">
                                        {{ session.roleLabel }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 align-top font-mono font-bold text-[#292536] tabular-nums">
                                    {{ session.confirmedParticipantsCount }} / {{ session.capacity }}
                                </td>
                                <td class="py-4 px-4 align-top">
                                    <PortalStatusBadge :status="session.statusValue" :label="session.statusLabel" />
                                </td>
                                <td class="py-4 pl-4 pr-6 align-top text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Button as-child variant="outline" size="sm" class="h-8 rounded-xl border-[#E5E1EB] px-3 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]">
                                            <Link :href="session.showUrl">
                                                Roster
                                            </Link>
                                        </Button>
                                        <Button as-child size="sm" class="h-8 rounded-xl bg-[#7057D9] px-3 text-xs font-semibold text-white shadow-none hover:bg-[#5942B5]">
                                            <Link :href="session.attendanceUrl">
                                                Attendance
                                            </Link>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>
