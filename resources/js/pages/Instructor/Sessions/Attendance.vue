<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Clock,
    Lock,
    Save,
    Send,
    ShieldAlert,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorAttendanceProps } from '@/types/etms';

const props = defineProps<InstructorAttendanceProps>();

// Initialise reactive draft form
const formRecords: Record<number, { attendance_status: string; remarks: string }> = {};
props.attendances.forEach((record) => {
    formRecords[record.id] = {
        attendance_status: record.attendanceStatus,
        remarks: record.remarks || '',
    };
});

const draftForm = useForm({
    records: formRecords,
});

const submitForm = useForm({});

// Calculate live client-side unmarked count based on current form selections
const liveUnmarkedCount = computed(() => {
    return Object.values(draftForm.records).filter(
        (r) => r.attendance_status === 'pending'
    ).length;
});

const canSubmit = computed(() => {
    return (
        !props.isLockedVerified &&
        props.attendances.length > 0 &&
        liveUnmarkedCount.value === 0 &&
        props.allMarked
    );
});

function saveDraft() {
    draftForm.put(props.updateAttendanceUrl, {
        preserveScroll: true,
    });
}

function submitForVerification() {
    if (!canSubmit.value) return;
    submitForm.post(props.submitAttendanceUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Attendance — ${session.sessionCode} — Ecohydrotech Training`" />

    <InstructorLayout
        :breadcrumb="`${session.sessionCode} / Attendance`"
        :title="`${session.sessionCode} — ${session.title}`"
        :subtitle="`${session.scheduleLabel} · ${session.venueName}`"
    >
        <div class="space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-[#F1EDFB] px-2.5 py-1 font-mono text-xs font-bold text-[#5942B5]">
                            {{ session.sessionCode }}
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#7057D9]">
                            Attendance Governance (INS-04, FR-16, D-020)
                        </span>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#292536]">
                        {{ session.title }}
                    </h1>
                    <p class="mt-1 text-sm text-[#625D6D]">
                        {{ session.scheduleLabel }} · {{ session.venueName }} ({{ session.venueCity }})
                    </p>
                </div>

                <div>
                    <Button as-child variant="outline" size="sm" class="h-9 rounded-xl border-[#E5E1EB] px-3.5 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]">
                        <Link :href="session.showUrl">
                            <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />
                            Back to Session Roster
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Two-Step Attendance Governance Notice (D-020) -->
            <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 text-xs leading-relaxed text-[#625D6D] shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#EAE5F8] text-[#5942B5]">
                        <ShieldCheck class="h-4 w-4" />
                    </div>
                    <div>
                        <strong class="font-bold text-[#292536]">Two-Step Attendance Governance (D-020):</strong>
                        <p class="mt-0.5">
                            Mark each confirmed participant as <strong>Present</strong>, <strong>Absent</strong>, or <strong>Excused</strong> and save your draft. Once every participant on the roster has a marked status (no rows left as <em>Pending</em>), submit the sheet for Administrator Verification.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="attendances.length === 0"
                class="rounded-2xl border border-[#E5E1EB] bg-white p-12 text-center shadow-sm"
            >
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1EDFB] text-[#7057D9]">
                    <Users class="h-6 w-6" />
                </div>
                <h3 class="mt-3 text-base font-bold text-[#292536]">No confirmed participants on this roster</h3>
                <p class="mx-auto mt-1 max-w-md text-xs text-[#625D6D]">
                    Attendance rows appear automatically once participants hold confirmed bookings for this session.
                </p>
            </div>

            <!-- Attendance Sheet Card -->
            <div v-else class="rounded-2xl border border-[#E5E1EB] bg-white shadow-sm overflow-hidden">
                <div class="border-b border-[#E5E1EB] p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-bold text-[#292536]">
                                Participant Attendance Sheet ({{ attendances.length }})
                            </h2>
                            <p class="mt-0.5 text-xs text-[#625D6D]">
                                Save draft marks as the session progresses, then submit the completed roster for administrator verification.
                            </p>
                        </div>
                        <div v-if="isLockedVerified" class="flex items-center gap-1.5 rounded-full bg-[#E8F4ED] px-3 py-1 text-xs font-bold text-[#28734F]">
                            <Lock class="h-3.5 w-3.5" />
                            Roster Verified & Locked
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveDraft" class="p-5 sm:p-6 space-y-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#E5E1EB] text-left text-sm">
                            <thead class="bg-[#F7F6FA]">
                                <tr class="text-[11px] font-bold uppercase tracking-wider text-[#625D6D]">
                                    <th class="py-3 pl-6 pr-4">Booking Ref</th>
                                    <th class="py-3 px-4">Participant</th>
                                    <th class="py-3 px-4">Attendance Status (Tap Target &ge;44px)</th>
                                    <th class="py-3 px-4">Instructor Remarks</th>
                                    <th class="py-3 pl-4 pr-6">Review State</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E5E1EB] bg-white">
                                <tr
                                    v-for="record in attendances"
                                    :key="record.id"
                                    class="transition hover:bg-[#F7F6FA]/50"
                                >
                                    <td class="py-4 pl-6 pr-4 align-middle font-mono font-bold text-[#292536]">
                                        {{ record.bookingReference }}
                                    </td>
                                    <td class="py-4 px-4 align-middle">
                                        <p class="font-bold text-[#292536]">{{ record.participantName }}</p>
                                        <p class="text-xs text-[#625D6D]">{{ record.organisation }}</p>
                                    </td>
                                    <td class="py-4 px-4 align-middle">
                                        <select
                                            v-model="draftForm.records[record.id].attendance_status"
                                            :disabled="isLockedVerified || draftForm.processing"
                                            class="min-h-[44px] w-full max-w-[160px] rounded-xl border border-[#E5E1EB] bg-white px-3 py-2 text-xs font-bold text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9] disabled:bg-[#F7F6FA] disabled:text-[#625D6D]"
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="excused">Excused</option>
                                        </select>
                                    </td>
                                    <td class="py-4 px-4 align-middle">
                                        <input
                                            type="text"
                                            v-model="draftForm.records[record.id].remarks"
                                            :disabled="isLockedVerified || draftForm.processing"
                                            placeholder="Optional note (e.g. arrived on time, practical lab completed)"
                                            class="min-h-[44px] w-full rounded-xl border border-[#E5E1EB] bg-white px-3.5 py-2 text-xs text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9] disabled:bg-[#F7F6FA] disabled:text-[#625D6D]"
                                        />
                                    </td>
                                    <td class="py-4 pl-4 pr-6 align-middle">
                                        <PortalStatusBadge
                                            :status="record.reviewStatus"
                                            :label="record.reviewStatusLabel"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Draft Save Button -->
                    <div v-if="!isLockedVerified" class="flex flex-wrap items-center justify-end gap-3 border-t border-[#E5E1EB] pt-4">
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="draftForm.processing"
                            class="min-h-[44px] rounded-xl border-[#E5E1EB] px-5 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]"
                        >
                            <Save class="mr-1.5 h-4 w-4 text-[#7057D9]" />
                            {{ draftForm.processing ? 'Saving Draft...' : 'Save Attendance Draft' }}
                        </Button>
                    </div>
                </form>

                <!-- Final Verification Submission Card (INS-04, D-020) -->
                <div v-if="!isLockedVerified" class="border-t border-[#E5E1EB] bg-[#F7F6FA] p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="max-w-xl text-xs text-[#625D6D]">
                            <div v-if="allMarked && liveUnmarkedCount === 0" class="flex items-center gap-2 text-[#28734F]">
                                <CheckCircle2 class="h-4 w-4 shrink-0 text-[#28734F]" />
                                <div>
                                    <strong class="font-bold">All participants marked.</strong>
                                    You can now submit this attendance sheet for Administrator Verification.
                                </div>
                            </div>
                            <div v-else class="flex items-center gap-2 text-[#8B5A17]">
                                <AlertCircle class="h-4 w-4 shrink-0 text-[#8B5A17]" />
                                <div>
                                    <strong class="font-bold">Action required before submission:</strong>
                                    Save a non-pending attendance status (Present, Absent, or Excused) for every participant first
                                    <span v-if="liveUnmarkedCount > 0"> ({{ liveUnmarkedCount }} still pending)</span>.
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submitForVerification">
                            <Button
                                type="submit"
                                :disabled="!canSubmit || submitForm.processing"
                                class="min-h-[44px] rounded-xl bg-[#7057D9] px-6 text-xs font-bold text-white shadow-none hover:bg-[#5942B5] disabled:cursor-not-allowed disabled:bg-[#E5E1EB] disabled:text-[#625D6D]"
                            >
                                <Send class="mr-1.5 h-4 w-4" />
                                {{ submitForm.processing ? 'Submitting...' : 'Submit Attendance for Verification' }}
                            </Button>
                        </form>
                    </div>
                </div>

                <!-- Verified & Locked Notice -->
                <div v-else class="border-t border-[#E5E1EB] bg-[#E8F4ED]/40 p-5 text-center text-xs text-[#28734F]">
                    <ShieldCheck class="mx-auto h-5 w-5 text-[#28734F]" />
                    <p class="mt-1 font-bold">Attendance Records Verified & Locked</p>
                    <p class="mt-0.5 text-[#625D6D]">
                        This session's attendance roster has been approved by administrators. Certificates may now be issued.
                    </p>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>
