<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    CalendarCheck,
    Clock,
    MapPin,
    RotateCcw,
    Search,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalBookingListItem, PortalBookingsIndexProps } from '@/types/etms';

const props = defineProps<PortalBookingsIndexProps>();

const { routes } = useRoutes();
const searchQuery = ref('');

type BookingRow = PortalBookingListItem & Record<string, unknown>;

const filteredBookings = computed<BookingRow[]>(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.bookings as BookingRow[];

    return props.bookings.filter(
        (b) =>
            b.bookingReference.toLowerCase().includes(q) ||
            b.sessionTitle.toLowerCase().includes(q) ||
            b.venueLabel.toLowerCase().includes(q) ||
            (b.sessionCode?.toLowerCase().includes(q) ?? false) ||
            (b.orderNumber?.toLowerCase().includes(q) ?? false),
    ) as BookingRow[];
});

const columns: DataTableColumn[] = [
    { key: 'session', label: 'Training Session' },
    { key: 'schedule', label: 'Schedule & Venue', tabular: true },
    { key: 'status', label: 'Status' },
    { key: 'amountLabel', label: 'Fee', align: 'right', tabular: true },
];

function statusTone(statusValue: string): StatusTone {
    if (['confirmed', 'completed'].includes(statusValue)) return 'success';
    if (['pending_payment', 'pending'].includes(statusValue)) return 'warning';
    if (['cancelled', 'expired', 'refunded'].includes(statusValue)) return 'danger';
    return 'primary';
}
</script>

<template>
    <Head title="Training Sessions" />

    <PortalLayout breadcrumb="Training Sessions">
        <div class="mx-auto max-w-[1440px] space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Training sessions</h1>
                    <p class="mt-1 text-sm text-muted">
                        Track upcoming session reservations, payment holds, and attendance records.
                    </p>
                </div>

                <AppButton :href="routes.sessions" variant="warm" size="sm" pill>
                    <span>Explore Sessions</span>
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </AppButton>
            </header>

            <!-- Modern Filter & Search Bar -->
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 lg:flex-row lg:items-center lg:justify-between">
                <!-- Status Pill Tabs -->
                <div
                    role="navigation"
                    aria-label="Filter sessions by status"
                    class="flex flex-wrap items-center gap-1.5"
                >
                    <Link
                        :href="routes.portalBookings"
                        preserve-scroll
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            statusFilter === ''
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                    >
                        All
                    </Link>

                    <Link
                        v-for="statusOption in statuses"
                        :key="statusOption.value"
                        :href="`${routes.portalBookings}?status=${encodeURIComponent(statusOption.value)}`"
                        preserve-scroll
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            statusFilter === statusOption.value
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                    >
                        {{ statusOption.label }}
                    </Link>
                </div>

                <!-- Inline Quick Search + Reset -->
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 lg:w-64">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Filter reference, title, venue..."
                            aria-label="Filter displayed sessions"
                            class="h-9 w-full rounded-xl border border-line-strong/80 bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>

                    <Link
                        v-if="statusFilter !== '' || searchQuery !== ''"
                        :href="routes.portalBookings"
                        preserve-scroll
                        class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-white px-3 text-xs font-semibold text-muted transition-colors hover:bg-sand-50 hover:text-navy-950"
                        @click="searchQuery = ''"
                    >
                        <RotateCcw class="size-3.5" aria-hidden="true" />
                        <span>Reset</span>
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="filteredBookings.length === 0"
                :icon="CalendarCheck"
                title="No training session bookings found"
                :description="
                    statusFilter !== '' || searchQuery !== ''
                        ? 'Try clearing your status filter or search term.'
                        : 'You have not reserved any instructor-led training sessions yet.'
                "
            >
                <template #action>
                    <div class="flex flex-wrap items-center justify-center gap-2.5">
                        <AppButton
                            v-if="statusFilter !== '' || searchQuery !== ''"
                            :href="routes.portalBookings"
                            variant="outline"
                            size="sm"
                            pill
                            @click="searchQuery = ''"
                        >
                            Reset filters
                        </AppButton>
                        <AppButton :href="routes.sessions" variant="warm" size="sm" pill>
                            Browse Training Sessions
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <template v-else>
                <!-- Mobile Cards (< md) -->
                <div class="space-y-3.5 md:hidden">
                    <div
                        v-for="booking in filteredBookings"
                        :key="booking.id"
                        class="rounded-2xl bg-white p-4 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="tabular-nums-mono text-xs font-semibold text-navy-900">
                                {{ booking.bookingReference }}
                            </span>
                            <StatusBadge :tone="statusTone(booking.statusValue)" dot>
                                {{ booking.statusLabel }}
                            </StatusBadge>
                        </div>

                        <Link
                            :href="booking.detailUrl"
                            class="mt-2.5 block text-base font-semibold text-navy-950 hover:text-primary-600"
                        >
                            {{ booking.sessionTitle }}
                        </Link>

                        <div class="mt-2.5 space-y-1.5 text-xs text-muted">
                            <p class="tabular flex items-center gap-1.5">
                                <Clock class="size-3.5 text-accent-600" aria-hidden="true" />
                                {{ booking.scheduleLabel }}
                            </p>
                            <p class="flex items-center gap-1.5">
                                <MapPin class="size-3.5 text-primary-600" aria-hidden="true" />
                                {{ booking.venueLabel }}
                            </p>
                        </div>

                        <p
                            v-if="booking.isHoldActive && booking.holdExpiresFullLabel"
                            class="tabular mt-3 rounded-xl bg-warning-50 px-3 py-2 text-xs font-semibold text-warning-700"
                        >
                            Hold expires {{ booking.holdExpiresFullLabel }}
                        </p>

                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-line pt-3">
                            <span class="tabular text-sm font-bold text-navy-950">
                                {{ booking.amountLabel }}
                            </span>
                            <AppButton
                                :href="booking.detailUrl"
                                :variant="booking.isHoldActive ? 'warm' : 'outline'"
                                size="sm"
                                pill
                            >
                                <span>{{ booking.isHoldActive ? 'Complete Payment' : 'View Details' }}</span>
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            </AppButton>
                        </div>
                    </div>
                </div>

                <!-- Desktop Modern DataTable -->
                <div class="hidden md:block">
                    <DataTable
                        :columns="columns"
                        :rows="filteredBookings"
                        row-key="id"
                    >
                        <template #cell-session="{ row }">
                            <Link
                                :href="row.detailUrl"
                                class="font-semibold text-navy-950 transition-colors hover:text-primary-600"
                            >
                                {{ row.sessionTitle }}
                            </Link>
                            <p v-if="row.moduleCode || row.sessionCode" class="tabular-nums-mono mt-0.5 text-xs text-muted">
                                {{ row.moduleCode }}<template v-if="row.moduleCode && row.sessionCode"> &middot; </template>{{ row.sessionCode }}
                            </p>
                        </template>

                        <template #cell-schedule="{ row }">
                            <div class="text-sm font-medium text-ink">{{ row.scheduleLabel }}</div>
                            <div class="mt-0.5 text-xs text-muted">{{ row.venueLabel }}</div>
                        </template>

                        <template #cell-status="{ row }">
                            <StatusBadge :tone="statusTone(row.statusValue)" dot>
                                {{ row.statusLabel }}
                            </StatusBadge>
                            <p
                                v-if="row.isHoldActive && row.holdExpiresTimeLabel"
                                class="tabular mt-1 text-[11px] font-semibold text-warning-700"
                            >
                                Expires {{ row.holdExpiresTimeLabel }}
                            </p>
                        </template>

                        <template #cell-amountLabel="{ row }">
                            <span class="font-semibold text-navy-950">{{ row.amountLabel }}</span>
                        </template>

                        <template #actions="{ row }">
                            <AppButton
                                v-if="row.isHoldActive"
                                :href="row.detailUrl"
                                variant="warm"
                                size="sm"
                                pill
                            >
                                <span>Complete Payment</span>
                                <ArrowRight class="size-3.5" aria-hidden="true" />
                            </AppButton>
                            <Link
                                v-else
                                :href="row.detailUrl"
                                class="group inline-flex items-center gap-1 rounded-full bg-sand-100 px-3.5 py-1.5 text-xs font-semibold text-navy-950 transition-all duration-150 hover:bg-navy-950 hover:text-white"
                            >
                                <span>View details</span>
                                <ArrowUpRight
                                    class="size-3.5 transition-transform duration-150 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                    aria-hidden="true"
                                />
                            </Link>
                        </template>
                    </DataTable>
                </div>

                <PublicPagination
                    :pagination="pagination"
                    item-label="sessions"
                />
            </template>
        </div>
    </PortalLayout>
</template>
