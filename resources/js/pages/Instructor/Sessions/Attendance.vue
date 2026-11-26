<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Lock,
    Save,
    Send,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorAttendanceProps } from '@/types/etms';

const props = defineProps<InstructorAttendanceProps>();

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

const liveUnmarkedCount = computed(() =>
    Object.values(draftForm.records).filter((r) => r.attendance_status === 'pending').length,
);

const canSubmit = computed(
    () =>
        !props.isLockedVerified &&
        props.attendances.length > 0 &&
        liveUnmarkedCount.value === 0 &&
        props.allMarked,
);

function markAllPresent(): void {
    if (props.isLockedVerified) return;
    Object.keys(draftForm.records).forEach((key) => {
        draftForm.records[Number(key)].attendance_status = 'present';
    });
}

function saveDraft(): void {
    draftForm.put(props.updateAttendanceUrl, {
        preserveScroll: true,
    });
}

function submitForVerification(): void {
    if (!canSubmit.value) return;
    submitForm.post(props.submitAttendanceUrl, {
        preserveScroll: true,
    });
}

function reviewTone(status: string): 'success' | 'warning' | 'danger' | 'neutral' {
    if (status === 'verified') return 'success';
    if (status === 'submitted' || status === 'draft') return 'warning';
    if (status === 'correction_requested') return 'danger';
    return 'neutral';
}
</script>

<template>
    <Head :title="`Attendance — ${session.sessionCode} — Ecohydrotech Training`" />

    <InstructorLayout :breadcrumb="`${session.sessionCode} / Attendance`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <Link
                        :href="session.showUrl"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-muted transition hover:text-ink"
                    >
                        <ArrowLeft class="size-3.5" aria-hidden="true" />
                        <span>Back to {{ session.sessionCode }}</span>
                    </Link>
                    <h1 class="type-page-title mt-1.5 text-ink">Attendance — {{ session.title }}</h1>
                    <p class="mt-1 text-sm text-muted">
                        {{ session.scheduleLabel }} · {{ session.venueName }} ({{ session.venueCity }})
                    </p>
                </div>

                <div v-if="isLockedVerified" class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1.5 text-xs font-semibold text-success-700">
                    <Lock class="size-3.5" aria-hidden="true" />
                    <span>Verified &amp; locked</span>
                </div>
            </div>

            <!-- Empty State -->
            <section
                v-if="attendances.length === 0"
                class="rounded-2xl border border-line/85 bg-surface px-6 py-12 text-center shadow-card"
            >
                <Users class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                <p class="mt-2.5 text-sm font-semibold text-ink">No confirmed participants for this session</p>
                <p class="mx-auto mt-1 max-w-md text-xs text-muted">
                    Attendance rows appear automatically once participants hold confirmed bookings for this session.
                </p>
            </section>

            <!-- Attendance Sheet -->
            <section
                v-else
                class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card"
            >
                <div class="flex flex-col gap-3 border-b border-line/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-ink">
                            Participant attendance sheet ({{ attendances.length }})
                        </h2>
                        <p class="mt-0.5 text-xs text-muted">
                            Mark every participant as Present, Absent, or Excused, save your draft, then submit for administrator verification.
                        </p>
                    </div>

                    <button
                        v-if="!isLockedVerified"
                        type="button"
                        class="inline-flex h-8 shrink-0 items-center rounded-lg border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                        @click="markAllPresent"
                    >
                        Mark all present
                    </button>
                </div>

                <form @submit.prevent="saveDraft">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Booking Ref</th>
                                    <th class="px-4 py-3">Participant</th>
                                    <th class="px-4 py-3">Attendance</th>
                                    <th class="px-4 py-3">Remarks</th>
                                    <th class="px-5 py-3">Review</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr
                                    v-for="record in attendances"
                                    :key="record.id"
                                    class="hover:bg-sand-50/60"
                                >
                                    <td class="tabular-nums-mono px-5 py-3.5 text-xs font-semibold text-ink">
                                        {{ record.bookingReference }}
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <p class="font-medium text-ink">{{ record.participantName }}</p>
                                        <p class="text-xs text-muted">{{ record.organisation }}</p>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <select
                                            v-model="draftForm.records[record.id].attendance_status"
                                            :disabled="isLockedVerified || draftForm.processing"
                                            class="h-10 w-full min-w-[130px] max-w-[160px] rounded-xl border border-line bg-surface px-3 text-sm font-medium text-ink focus:border-primary-600 focus:outline-none disabled:bg-sand-50 disabled:text-muted"
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="excused">Excused</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <input
                                            v-model="draftForm.records[record.id].remarks"
                                            type="text"
                                            :disabled="isLockedVerified || draftForm.processing"
                                            placeholder="Optional note..."
                                            class="h-10 w-full min-w-[200px] rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none disabled:bg-sand-50 disabled:text-muted"
                                        />
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <StatusBadge
                                            :tone="reviewTone(record.reviewStatus)"
                                            :label="record.reviewStatusLabel"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Actions -->
                    <div
                        v-if="!isLockedVerified"
                        class="flex flex-col gap-4 border-t border-line/80 bg-sand-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="text-xs">
                            <p v-if="allMarked && liveUnmarkedCount === 0" class="flex items-center gap-1.5 font-medium text-success-700">
                                <CheckCircle2 class="size-4 shrink-0" aria-hidden="true" />
                                <span>All participants marked. Ready to submit for administrator verification.</span>
                            </p>
                            <p v-else class="flex items-center gap-1.5 text-warning-700">
                                <AlertCircle class="size-4 shrink-0" aria-hidden="true" />
                                <span>
                                    Save a non-pending status for every participant before submitting
                                    <span v-if="liveUnmarkedCount > 0"> ({{ liveUnmarkedCount }} pending)</span>.
                                </span>
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="submit"
                                :disabled="draftForm.processing"
                                class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100 disabled:opacity-50"
                            >
                                <Save class="size-4 text-muted" aria-hidden="true" />
                                <span>{{ draftForm.processing ? 'Saving…' : 'Save draft' }}</span>
                            </button>

                            <button
                                type="button"
                                :disabled="!canSubmit || submitForm.processing"
                                class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-45"
                                @click="submitForVerification"
                            >
                                <Send class="size-4" aria-hidden="true" />
                                <span>{{ submitForm.processing ? 'Submitting…' : 'Submit for verification' }}</span>
                            </button>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex items-center justify-center gap-2 border-t border-line/80 bg-success-50/60 px-5 py-4 text-xs font-medium text-success-700"
                    >
                        <ShieldCheck class="size-4 shrink-0" aria-hidden="true" />
                        <span>Attendance has been verified by an administrator and locked for certificate issuance.</span>
                    </div>
                </form>
            </section>
        </div>
    </InstructorLayout>
</template>
