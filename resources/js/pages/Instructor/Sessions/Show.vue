<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    Clock,
    FileCheck2,
    FileText,
    MapPin,
    ShieldCheck,
    Users,
    Video,
} from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorSessionShowProps } from '@/types/etms';

const props = defineProps<InstructorSessionShowProps>();
</script>

<template>
    <Head :title="`${session.sessionCode} — ${session.title} — Ecohydrotech Training`" />

    <InstructorLayout
        :breadcrumb="session.sessionCode"
        :title="session.title"
        :subtitle="`Module: ${session.moduleCode ?? ''} — ${session.moduleTitle ?? ''}`"
    >
        <div class="space-y-6">
            <!-- Header Card with Actions -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-[#F1EDFB] px-2.5 py-1 font-mono text-xs font-bold text-[#5942B5]">
                            {{ session.sessionCode }}
                        </span>
                        <PortalStatusBadge :status="session.statusValue" :label="session.statusLabel" />
                        <span class="rounded-full bg-[#EAE5F8] px-2.5 py-0.5 text-xs font-semibold text-[#5942B5]">
                            {{ session.roleLabel }}
                        </span>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#292536]">
                        {{ session.title }}
                    </h1>
                    <p class="mt-1 text-sm text-[#625D6D]">
                        Module: <strong class="text-[#292536]">{{ session.moduleCode }} — {{ session.moduleTitle }}</strong>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button as-child size="sm" class="h-9 rounded-xl bg-[#7057D9] px-4 text-xs font-semibold text-white shadow-none hover:bg-[#5942B5]">
                        <Link :href="session.attendanceUrl">
                            <CheckCircle2 class="mr-1.5 h-4 w-4" />
                            Record & Submit Attendance
                        </Link>
                    </Button>
                    <Button as-child variant="outline" size="sm" class="h-9 rounded-xl border-[#E5E1EB] px-3.5 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]">
                        <Link :href="session.completionUrl">
                            <FileText class="mr-1.5 h-4 w-4 text-[#7057D9]" />
                            Completion Report
                        </Link>
                    </Button>
                    <Button as-child variant="outline" size="sm" class="h-9 rounded-xl border-[#E5E1EB] px-3.5 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]">
                        <Link :href="session.recordingsUrl">
                            <Video class="mr-1.5 h-4 w-4 text-[#7057D9]" />
                            Recordings ({{ session.recordingsCount }})
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Main Layout Grid -->
            <div class="grid gap-6 lg:grid-cols-12">
                <!-- Main Content (Roster + Completion Notes) -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Confirmed Participant Roster Card (INS-03) -->
                    <div class="rounded-2xl border border-[#E5E1EB] bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-[#E5E1EB] p-5 sm:p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-[#292536]">
                                        Confirmed Participant Roster ({{ roster.length }} / {{ session.capacity }})
                                    </h2>
                                    <p class="mt-0.5 text-xs text-[#625D6D]">
                                        Participants holding confirmed bookings for this physical session (commercial payment details hidden per SRS Sec. 2.2).
                                    </p>
                                </div>
                                <span class="rounded-full bg-[#EAE5F8] px-2.5 py-0.5 font-mono text-xs font-bold text-[#5942B5]">
                                    {{ roster.length }} Enrolled
                                </span>
                            </div>
                        </div>

                        <div v-if="roster.length === 0" class="p-8 text-center">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-[#F7F6FA] text-[#625D6D]">
                                <Users class="h-5 w-5" />
                            </div>
                            <p class="mt-2 text-xs font-semibold text-[#625D6D]">
                                No confirmed participants are currently enrolled in this session.
                            </p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[#E5E1EB] text-left text-sm">
                                <thead class="bg-[#F7F6FA]">
                                    <tr class="text-[11px] font-bold uppercase tracking-wider text-[#625D6D]">
                                        <th class="py-3 pl-6 pr-4">Booking Ref</th>
                                        <th class="py-3 px-4">Participant</th>
                                        <th class="py-3 px-4">Organisation</th>
                                        <th class="py-3 px-4">Attendance</th>
                                        <th class="py-3 pl-4 pr-6">Verification</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E5E1EB] bg-white">
                                    <tr
                                        v-for="booking in roster"
                                        :key="booking.bookingId"
                                        class="transition hover:bg-[#F7F6FA]/50"
                                    >
                                        <td class="py-3.5 pl-6 pr-4 font-mono font-bold text-[#292536]">
                                            {{ booking.bookingReference }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <p class="font-semibold text-[#292536]">{{ booking.participantName }}</p>
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-[#625D6D]">
                                            {{ booking.organisation }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <PortalStatusBadge
                                                :status="booking.attendanceStatusValue"
                                                :label="booking.attendanceStatusLabel"
                                            />
                                        </td>
                                        <td class="py-3.5 pl-4 pr-6 text-xs font-semibold text-[#625D6D]">
                                            {{ booking.reviewStatusLabel }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Completion Notes Preview Card -->
                    <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#E5E1EB] pb-4">
                            <div>
                                <h2 class="text-base font-bold text-[#292536]">Instructor Completion Notes</h2>
                                <p class="mt-0.5 text-xs text-[#625D6D]">Delivery summary and operational remarks for administrators</p>
                            </div>
                            <Button as-child variant="outline" size="sm" class="h-8 rounded-xl border-[#E5E1EB] text-xs font-semibold">
                                <Link :href="session.completionUrl">
                                    {{ session.completionNotes ? 'Edit Notes' : 'Submit Report' }}
                                </Link>
                            </Button>
                        </div>

                        <div class="mt-4">
                            <div v-if="session.completionNotes" class="space-y-3">
                                <p class="whitespace-pre-line text-sm leading-relaxed text-[#292536]">
                                    {{ session.completionNotes }}
                                </p>
                                <p v-if="session.completedAtLabel" class="font-mono text-xs text-[#625D6D]">
                                    Recorded at {{ session.completedAtLabel }} MYT
                                </p>
                            </div>
                            <div v-else class="rounded-xl border border-dashed border-[#E5E1EB] bg-[#F7F6FA] p-6 text-center">
                                <FileText class="mx-auto h-6 w-6 text-[#625D6D]" />
                                <p class="mt-2 text-xs font-semibold text-[#625D6D]">
                                    No session completion report has been submitted yet.
                                </p>
                                <p class="mt-0.5 text-[11px] text-[#625D6D]">
                                    Record syllabus coverage and practical lab outcomes after delivering the cohort.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logistics Sidebar -->
                <div class="space-y-6 lg:col-span-4">
                    <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 shadow-sm">
                        <h2 class="text-base font-bold text-[#292536] border-b border-[#E5E1EB] pb-3">
                            Schedule & Venue Logistics
                        </h2>

                        <dl class="mt-4 space-y-4 text-xs">
                            <div>
                                <dt class="font-bold uppercase tracking-wider text-[#625D6D]">Date & Time (MYT)</dt>
                                <dd class="mt-1 text-sm font-semibold text-[#292536]">{{ session.scheduleLabel }}</dd>
                            </div>

                            <div>
                                <dt class="font-bold uppercase tracking-wider text-[#625D6D]">Duration</dt>
                                <dd class="mt-1 font-medium text-[#292536]">{{ session.durationLabel }}</dd>
                            </div>

                            <div>
                                <dt class="font-bold uppercase tracking-wider text-[#625D6D]">Venue & Location</dt>
                                <dd class="mt-1 font-semibold text-[#292536]">{{ session.venueName }}</dd>
                                <dd class="mt-0.5 text-[#625D6D]">{{ session.venueAddress }}, {{ session.venueCity }}, {{ session.venueState }}</dd>
                            </div>

                            <div>
                                <dt class="font-bold uppercase tracking-wider text-[#625D6D]">Recording Policy</dt>
                                <dd class="mt-1 font-medium text-[#292536]">{{ session.recordingAccessPolicyLabel }}</dd>
                            </div>

                            <div>
                                <dt class="font-bold uppercase tracking-wider text-[#625D6D]">Role in Cohort</dt>
                                <dd class="mt-1">
                                    <span class="rounded-full bg-[#EAE5F8] px-2.5 py-0.5 text-xs font-semibold text-[#5942B5]">
                                        {{ session.roleLabel }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>
