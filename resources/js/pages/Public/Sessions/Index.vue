<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import PublicPagination from '@/components/public/PublicPagination.vue';
import SessionsDataTable from '@/components/public/SessionsDataTable.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useRoutes } from '@/composables/useRoutes';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { cn, pluralize } from '@/lib/utils';
import type { SessionsIndexProps } from '@/types/etms';

const props = defineProps<SessionsIndexProps>();

const { routes } = useRoutes();

const search = ref(props.filters.q ?? '');
const availability = ref(props.filters.availability ?? 'all');
const isFiltering = ref(false);

let searchTimer: ReturnType<typeof setTimeout> | null = null;
let syncingFromProps = false;

watch(
    () => props.filters,
    (next) => {
        syncingFromProps = true;
        search.value = next.q ?? '';
        availability.value = next.availability ?? 'all';
        syncingFromProps = false;
    },
    { deep: true },
);

const availabilityTabs = [
    { value: 'all', label: 'All' },
    { value: 'this_month', label: 'This Month' },
    { value: 'open_seats', label: 'Open Seats' },
] as const;

const hasActiveFilters = computed(
    () => search.value.trim() !== '' || availability.value !== 'all',
);

function visitSessions(): void {
    const query: Record<string, string> = {};

    if (search.value.trim() !== '') {
        query.q = search.value.trim();
    }

    if (availability.value !== 'all') {
        query.availability = availability.value;
    }

    router.get(routes.value.sessions, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => {
            isFiltering.value = true;
        },
        onFinish: () => {
            isFiltering.value = false;
        },
    });
}

watch(search, () => {
    if (syncingFromProps) {
        return;
    }

    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
        visitSessions();
    }, 280);
});

onBeforeUnmount(() => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }
});

function setAvailability(nextAvailability: string | number): void {
    const resolved = String(nextAvailability);
    if (availability.value === resolved) {
        return;
    }

    availability.value = resolved;
    visitSessions();
}

function clearSearch(): void {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    search.value = '';
    visitSessions();
}

function clearFilters(): void {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    syncingFromProps = true;
    search.value = '';
    availability.value = 'all';
    syncingFromProps = false;

    visitSessions();
}
</script>

<template>
    <Head title="Upcoming Physical Sessions" />

    <PublicLayout>
        <section class="border-b border-[#272d38]/10 bg-white py-12 lg:py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <h1 class="text-3xl font-bold text-[#272d38] sm:text-4xl">
                            Upcoming Physical Sessions
                        </h1>
                        <p class="mt-3 text-[16px] leading-relaxed text-[#272d38]/70">
                            Scheduled instructor-led technical workshops across Malaysia. All session times in Malaysia Time (MYT).
                        </p>
                    </div>

                    <div class="shrink-0 sm:text-right">
                        <p class="text-2xl font-bold text-[#272d38] tabular-nums">
                            {{ pagination.total }}
                        </p>
                        <p class="text-[14px] text-[#272d38]/65 tabular-nums">
                            {{ pluralize(pagination.total, 'upcoming session') }}
                            <template v-if="hasActiveFilters">
                                of {{ totalUpcomingCount }}
                            </template>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-12 lg:py-16" aria-label="Upcoming training sessions">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 border-b border-[#272d38]/10 pb-6">
                    <div class="relative">
                        <label for="sessions-search-input" class="sr-only">
                            Search physical sessions
                        </label>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#272d38]/50"
                            aria-hidden="true"
                        />
                        <Input
                            id="sessions-search-input"
                            v-model="search"
                            type="search"
                            placeholder="Search by session title, code, venue, or city..."
                            class="pr-9 pl-10"
                        />
                        <button
                            v-if="search !== ''"
                            type="button"
                            aria-label="Clear search"
                            class="absolute top-1/2 right-2.5 -translate-y-1/2 rounded-md p-1 text-[#272d38]/50 transition-colors hover:text-[#272d38] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            @click="clearSearch"
                        >
                            <X class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
                        <Tabs
                            :model-value="availability"
                            @update:model-value="setAvailability"
                        >
                            <TabsList aria-label="Filter sessions by timing and seat availability">
                                <TabsTrigger
                                    v-for="tab in availabilityTabs"
                                    :key="tab.value"
                                    :value="tab.value"
                                >
                                    {{ tab.label }}
                                </TabsTrigger>
                            </TabsList>
                        </Tabs>

                        <div class="flex items-center gap-4">
                            <span
                                v-if="isFiltering"
                                class="text-[14px] text-[#272d38]/65"
                                aria-live="polite"
                            >
                                Updating...
                            </span>
                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                class="rounded-md text-[15px] font-bold text-[#272d38] transition-colors hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                                @click="clearFilters"
                            >
                                Clear filters
                            </button>
                        </div>
                    </div>
                </div>

                <SessionsDataTable
                    v-if="sessions.length > 0"
                    :sessions="sessions"
                    :class="cn('mt-6 transition-opacity duration-150', isFiltering && 'opacity-65')"
                />

                <Card
                    v-else
                    class="mt-8 px-6 py-14 text-center"
                >
                    <h2 class="text-[19px] font-bold text-[#272d38]">
                        No upcoming sessions match your filter
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-[16px] text-[#272d38]/65">
                        Clear active filters to view all scheduled workshops, or explore our self-paced training catalogue.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="outline"
                            @click="clearFilters"
                        >
                            Clear filters
                        </Button>
                        <Button as-child>
                            <Link :href="routes.catalogue">Browse Standalone Modules</Link>
                        </Button>
                    </div>
                </Card>

                <PublicPagination
                    :pagination="pagination"
                    :item-label="pluralize(pagination.total, 'session')"
                />
            </div>
        </section>
    </PublicLayout>
</template>
