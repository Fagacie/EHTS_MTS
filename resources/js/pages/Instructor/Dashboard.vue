<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarCheck,
    CalendarDays,
    CheckCircle2,
    Clock,
    ExternalLink,
    FileCheck2,
    Users,
    Video,
} from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorDashboardProps } from '@/types/etms';

const props = defineProps<InstructorDashboardProps>();
</script>

<template>
    <Head title="Instructor Dashboard — Ecohydrotech Training" />

    <InstructorLayout
        breadcrumb="Dashboard"
        title="Instructor Workspace"
        subtitle="Assigned physical sessions, participant rosters, attendance submission, completion notes & recording submission (INS-01)."
    >
        <div class="space-y-6">
            <!-- Header Greeting & Badges -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAE5F8] px-2.5 py-0.5 text-xs font-bold text-[#5942B5]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#7057D9]" />
                            Active Workspace
                        </span>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#292536]">
                        Instructor Workspace, {{ instructor.displayName }}
                    </h1>
                    <p class="mt-1 text-sm text-[#625D6D]">
                        {{ instructor.email }} · Assigned physical cohorts & academic delivery
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <PortalStatusBadge status="confirmed" label="Instructor" />
                    <template v-if="instructor.isPublic">
                        <PortalStatusBadge status="published" label="Public Directory Active" />
                        <a
                            v-if="instructor.publicProfileUrl"
                            :href="instructor.publicProfileUrl"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-[#5942B5] hover:underline"
                        >
                            View Public Profile
                            <ExternalLink class="h-3.5 w-3.5" />
                        </a>
                    </template>
                </div>
            </div>

            <!-- KPI Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 shadow-sm transition hover:border-[#7057D9]/40">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#625D6D]">Assigned Sessions</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAE5F8] text-[#5942B5]">
                            <CalendarDays class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold tracking-tight text-[#292536] tabular-nums">
                        {{ kpis.assignedSessionsCount }}
                    </div>
                    <p class="mt-1 text-xs text-[#625D6D]">Physical cohorts assigned to you</p>
                </div>

                <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 shadow-sm transition hover:border-[#7057D9]/40">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#625D6D]">Attendance Pending</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#FBF2E3] text-[#8B5A17]">
                            <Clock class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold tracking-tight text-[#292536] tabular-nums">
                        {{ kpis.pendingAttendanceCount }}
                    </div>
                    <p class="mt-1 text-xs text-[#625D6D]">Sessions awaiting verification</p>
                </div>

                <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 shadow-sm transition hover:border-[#7057D9]/40">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#625D6D]">Submitted Recordings</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#E8F4ED] text-[#28734F]">
                            <Video class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 text-3xl font-extrabold tracking-tight text-[#292536] tabular-nums">
                        {{ kpis.submittedRecordingsCount }}
                    </div>
                    <p class="mt-1 text-xs text-[#625D6D]">Session recordings uploaded</p>
                </div>
            </div>

            <!-- Assigned Sessions Section -->
            <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#E5E1EB] pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-[#292536]">My Assigned Training Sessions</h2>
                        <p class="mt-0.5 text-xs text-[#625D6D]">
                            Select a session to inspect participant roster, mark attendance, submit completion notes, or upload recording metadata.
                        </p>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="assignedSessions.length === 0"
                    class="py-12 text-center"
                >
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1EDFB] text-[#7057D9]">
                        <CalendarDays class="h-6 w-6" />
                    </div>
                    <h3 class="mt-3 text-base font-bold text-[#292536]">No assigned physical sessions yet</h3>
                    <p class="mx-auto mt-1 max-w-md text-xs text-[#625D6D]">
                        When administrators schedule physical training sessions and assign you as an instructor, your rosters, attendance sheets, and recording uploads will appear here.
                    </p>
                </div>

                <!-- Sessions List -->
                <div v-else class="divide-y divide-[#E5E1EB]">
                    <div
                        v-for="session in assignedSessions"
                        :key="session.id"
                        class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-4 last:pb-0"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-[#F1EDFB] px-2.5 py-0.5 font-mono text-xs font-bold text-[#5942B5]">
                                    {{ session.sessionCode }}
                                </span>
                                <PortalStatusBadge :status="session.statusValue" :label="session.statusLabel" />
                                <span class="rounded-full bg-[#EAE5F8] px-2.5 py-0.5 text-xs font-semibold text-[#5942B5]">
                                    {{ session.roleLabel }}
                                </span>
                            </div>

                            <h3 class="mt-1.5 text-base font-bold text-[#292536]">
                                <Link :href="session.showUrl" class="transition hover:text-[#7057D9]">
                                    {{ session.title }}
                                </Link>
                            </h3>

                            <p class="mt-0.5 text-xs text-[#625D6D]">
                                {{ session.scheduleLabel }} · {{ session.venueName }} ({{ session.venueCity }})
                            </p>

                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-[#625D6D]">
                                <span class="inline-flex items-center gap-1 font-mono">
                                    <Users class="h-3.5 w-3.5 text-[#5942B5]" />
                                    Confirmed: <strong class="text-[#292536]">{{ session.confirmedParticipantsCount }}</strong> / {{ session.capacity }}
                                </span>
                                <span class="inline-flex items-center gap-1 font-mono">
                                    <Video class="h-3.5 w-3.5 text-[#5942B5]" />
                                    Recordings: <strong class="text-[#292536]">{{ session.recordingsCount }}</strong>
                                </span>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <Button as-child variant="outline" size="sm" class="h-9 rounded-xl border-[#E5E1EB] px-3.5 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]">
                                <Link :href="session.showUrl">
                                    Roster & Details
                                </Link>
                            </Button>
                            <Button as-child size="sm" class="h-9 rounded-xl bg-[#7057D9] px-3.5 text-xs font-semibold text-white shadow-none hover:bg-[#5942B5]">
                                <Link :href="session.attendanceUrl">
                                    <CheckCircle2 class="mr-1.5 h-3.5 w-3.5" />
                                    Attendance
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>
