<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Award, MapPin } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { InstructorShowProps, UpcomingSessionItem } from '@/types/etms';

const props = defineProps<InstructorShowProps>();

const columns: DataTableColumn[] = [
    { key: 'schedule', label: 'Date & Time', tabular: true },
    { key: 'title', label: 'Session' },
    { key: 'venue', label: 'Location' },
    { key: 'seats', label: 'Availability', tabular: true },
    { key: 'priceLabel', label: 'Fee', align: 'right', tabular: true },
];

type SessionRow = UpcomingSessionItem & Record<string, unknown>;
const sessionRows = computed<SessionRow[]>(() => props.upcomingSessions as SessionRow[]);

function sessionTone(session: UpcomingSessionItem): StatusTone {
    if (!session.isOpenForBooking || session.remainingSeats <= 0) return 'danger';
    if (session.remainingSeats <= 5) return 'warning';
    return 'success';
}

const initials = computed(() => {
    const parts = props.instructor.displayName.trim().split(/\s+/);
    return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '')).toUpperCase();
});
</script>

<template>
    <PublicLayout>
        <Head :title="instructor.displayName" />

        <section class="border-b border-line bg-sand-50/70">
            <div class="mx-auto max-w-7xl px-4 py-9 sm:px-6 sm:py-12 lg:px-8">
                <Link
                    :href="routes.instructors"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>All Instructors</span>
                </Link>

                <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">
                    <img
                        v-if="instructor.photoUrl"
                        :src="instructor.photoUrl"
                        :alt="instructor.displayName"
                        class="size-22 shrink-0 rounded-full object-cover shadow-card ring-4 ring-white sm:size-26"
                    />
                    <div
                        v-else
                        class="flex size-22 shrink-0 items-center justify-center rounded-full bg-navy-900 text-2xl font-bold text-white shadow-card ring-4 ring-white sm:size-26"
                    >
                        {{ initials }}
                    </div>

                    <div class="min-w-0">
                        <h1 class="type-page-title">{{ instructor.displayName }}</h1>
                        <p v-if="instructor.professionalTitle" class="mt-1 text-base font-medium text-muted">
                            {{ instructor.professionalTitle }}
                        </p>
                        <div
                            v-if="instructor.qualifications"
                            class="mt-3 inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-1 text-xs font-semibold text-navy-900 shadow-xs ring-1 ring-line"
                        >
                            <Award class="size-3.5 text-accent-600" aria-hidden="true" />
                            <span>{{ instructor.qualifications }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl space-y-14 px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <h2 class="type-section-title text-navy-950">Biography</h2>
                    <div
                        v-if="instructor.biography || instructor.shortBio"
                        class="mt-3.5 whitespace-pre-line text-sm leading-7 text-ink/90 sm:text-[15px]"
                    >
                        {{ instructor.biography || instructor.shortBio }}
                    </div>
                    <p v-else class="mt-3.5 text-sm text-muted">
                        Biography details are not yet available.
                    </p>
                </div>

                <aside v-if="instructor.specialisations.length > 0" class="lg:col-span-4">
                    <div class="rounded-2xl border border-line bg-sand-50 p-6">
                        <h2 class="text-sm font-semibold text-navy-950">Specialisations</h2>
                        <div class="mt-3.5 flex flex-wrap gap-2">
                            <span
                                v-for="spec in instructor.specialisations"
                                :key="spec"
                                class="rounded-lg bg-white px-3 py-1 text-xs font-medium text-navy-900 ring-1 ring-line"
                            >
                                {{ spec }}
                            </span>
                        </div>
                    </div>
                </aside>
            </div>

            <div>
                <h2 class="type-section-title text-navy-950">
                    Upcoming sessions led by {{ instructor.displayName }}
                </h2>

                <div class="mt-5">
                    <DataTable
                        :columns="columns"
                        :rows="sessionRows"
                        row-key="id"
                        empty-title="No upcoming sessions currently assigned"
                        empty-description="Check the live schedule for other upcoming sessions."
                    >
                        <template #cell-schedule="{ row }">
                            <div class="font-semibold text-navy-950">{{ row.dateRangeLabel }}</div>
                            <div class="mt-0.5 text-xs text-muted">{{ row.timeLabel }}</div>
                        </template>

                        <template #cell-title="{ row }">
                            <Link
                                :href="row.detailUrl"
                                class="font-semibold text-navy-950 transition-colors hover:text-primary-600"
                            >
                                {{ row.title }}
                            </Link>
                            <div class="tabular-nums-mono mt-1 text-xs text-muted">{{ row.sessionCode }}</div>
                        </template>

                        <template #cell-venue="{ row }">
                            <div class="inline-flex items-start gap-1.5 text-sm">
                                <MapPin class="mt-0.5 size-3.5 shrink-0 text-muted" aria-hidden="true" />
                                <span>
                                    {{ row.venueName }}
                                    <span v-if="row.venueLocation" class="block text-xs text-muted">{{ row.venueLocation }}</span>
                                </span>
                            </div>
                        </template>

                        <template #cell-seats="{ row }">
                            <StatusBadge :tone="sessionTone(row)" dot>
                                <template v-if="row.isOpenForBooking && row.remainingSeats > 0">
                                    {{ row.remainingSeats }} / {{ row.capacity }} open
                                </template>
                                <template v-else>
                                    {{ row.statusLabel }}
                                </template>
                            </StatusBadge>
                        </template>

                        <template #cell-priceLabel="{ row }">
                            <span class="font-semibold text-navy-950">{{ row.priceLabel }}</span>
                        </template>

                        <template #actions="{ row }">
                            <AppButton
                                :href="row.detailUrl"
                                :variant="row.isOpenForBooking ? 'primary' : 'outline'"
                                size="sm"
                                pill
                            >
                                {{ row.isOpenForBooking ? 'Reserve' : 'Details' }}
                            </AppButton>
                        </template>
                    </DataTable>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
