<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, LayoutGrid, List, MapPin, RotateCcw, Search } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import SessionCard from '@/components/public/SessionCard.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SessionsIndexProps, UpcomingSessionItem } from '@/types/etms';

const props = defineProps<SessionsIndexProps>();

const q = ref(props.filters.q);
const category = ref(props.filters.category);
const availability = ref(props.filters.availability || 'all');
const viewMode = ref<'cards' | 'table'>('cards');

watch(
    () => props.filters,
    (next) => {
        q.value = next.q;
        category.value = next.category;
        availability.value = next.availability || 'all';
    },
);

const availabilityTabs = [
    { value: 'all', label: 'All upcoming' },
    { value: 'open_seats', label: 'Open seats' },
    { value: 'this_month', label: 'This month' },
];

const tableColumns: DataTableColumn[] = [
    { key: 'schedule', label: 'Date & Time', tabular: true },
    { key: 'title', label: 'Session' },
    { key: 'venue', label: 'Venue' },
    { key: 'seats', label: 'Seats', tabular: true },
    { key: 'priceLabel', label: 'Fee', align: 'right', tabular: true },
];

type SessionRow = UpcomingSessionItem & Record<string, unknown>;
const sessionRows = computed<SessionRow[]>(() => props.sessions as SessionRow[]);

function applyFilters(): void {
    const params: Record<string, string> = {};
    if (q.value.trim()) params.q = q.value.trim();
    if (category.value) params.category = category.value;
    if (availability.value && availability.value !== 'all') params.availability = availability.value;

    router.get(props.routes.sessions, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function setAvailability(val: string): void {
    availability.value = val;
    applyFilters();
}

function resetFilters(): void {
    q.value = '';
    category.value = '';
    availability.value = 'all';
    applyFilters();
}

function sessionTone(session: UpcomingSessionItem): StatusTone {
    if (!session.isOpenForBooking || session.remainingSeats <= 0) return 'danger';
    if (session.remainingSeats <= 5) return 'warning';
    return 'success';
}

const hasActiveFilters = () => Boolean(q.value.trim() || category.value || availability.value !== 'all');
</script>

<template>
    <PublicLayout>
        <Head title="Live Training Sessions" />

        <!-- Page Header & Filter Bar -->
        <section class="border-b border-line bg-sand-50/70">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div class="max-w-2xl">
                        <h1 class="type-page-title">Live training sessions</h1>
                        <p class="mt-2 text-sm leading-relaxed text-muted sm:text-base">
                            Scheduled field workshops and technical seminars across Malaysia with live seat availability.
                        </p>
                    </div>

                    <p class="tabular text-sm font-medium text-muted">
                        <span class="font-semibold text-navy-950">{{ totalUpcomingCount }}</span>
                        scheduled {{ totalUpcomingCount === 1 ? 'session' : 'sessions' }}
                    </p>
                </div>

                <!-- Search & Category Filter Form -->
                <form
                    class="mt-7 grid gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 md:grid-cols-12"
                    @submit.prevent="applyFilters"
                >
                    <div class="md:col-span-5">
                        <label for="sessions-q" class="sr-only">Search training sessions</label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted"
                                aria-hidden="true"
                            />
                            <input
                                id="sessions-q"
                                v-model="q"
                                type="text"
                                placeholder="Search title, session code, venue, or city..."
                                class="h-10.5 w-full rounded-xl border border-line-strong/80 bg-white pr-3.5 pl-10 text-sm text-ink placeholder:text-muted/75 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <label for="sessions-category" class="sr-only">Subject area</label>
                        <select
                            id="sessions-category"
                            v-model="category"
                            class="h-10.5 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            @change="applyFilters"
                        >
                            <option value="">All subjects</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.slug">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 md:col-span-3">
                        <AppButton type="submit" variant="primary" size="md" class="h-10.5 flex-1">
                            Filter
                        </AppButton>
                        <AppButton
                            v-if="hasActiveFilters()"
                            type="button"
                            variant="outline"
                            size="md"
                            icon-only
                            class="h-10.5 w-10.5"
                            aria-label="Reset filters"
                            @click="resetFilters"
                        >
                            <RotateCcw class="size-4" aria-hidden="true" />
                        </AppButton>
                    </div>
                </form>

                <!-- Availability Pills + View Switcher -->
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="tab in availabilityTabs"
                            :key="tab.value"
                            type="button"
                            class="cursor-pointer rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                            :class="
                                availability === tab.value
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-white text-muted ring-1 ring-line hover:bg-sand-100 hover:text-navy-950'
                            "
                            @click="setAvailability(tab.value)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <div class="inline-flex rounded-full bg-white p-1 shadow-xs ring-1 ring-line">
                        <button
                            type="button"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                            :class="viewMode === 'cards' ? 'bg-navy-950 text-white' : 'text-muted hover:text-navy-950'"
                            @click="viewMode = 'cards'"
                        >
                            <LayoutGrid class="size-3.5" aria-hidden="true" />
                            <span>Cards</span>
                        </button>
                        <button
                            type="button"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                            :class="viewMode === 'table' ? 'bg-navy-950 text-white' : 'text-muted hover:text-navy-950'"
                            @click="viewMode = 'table'"
                        >
                            <List class="size-3.5" aria-hidden="true" />
                            <span>Table</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Results -->
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <template v-if="sessions.length > 0">
                <div v-if="viewMode === 'cards'" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <SessionCard
                        v-for="session in sessions"
                        :key="session.id"
                        :session="session"
                    />
                </div>

                <DataTable
                    v-else
                    :columns="tableColumns"
                    :rows="sessionRows"
                    row-key="id"
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
                        <div class="mt-1 text-xs text-muted">
                            <span class="tabular-nums-mono">{{ row.sessionCode }}</span>
                            <span v-if="row.instructorNames"> &middot; {{ row.instructorNames }}</span>
                        </div>
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
            </template>

            <EmptyState
                v-else
                :icon="CalendarDays"
                title="No training sessions match your filter"
                description="Try broadening your availability window, clearing keywords, or browsing self-paced modules."
            >
                <template #action>
                    <div class="flex flex-wrap items-center justify-center gap-2.5">
                        <AppButton variant="outline" size="sm" pill @click="resetFilters">
                            Reset filters
                        </AppButton>
                        <AppButton :href="routes.catalogue" variant="primary" size="sm" pill>
                            Browse Modules
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <PublicPagination :pagination="pagination" item-label="sessions" />
        </section>
    </PublicLayout>
</template>
