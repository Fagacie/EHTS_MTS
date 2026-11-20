<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import SessionsDataTable from '@/components/public/SessionsDataTable.vue';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useRoutes } from '@/composables/useRoutes';
import type { UpcomingSessionItem } from '@/types/etms';

const props = defineProps<{
    sessions: UpcomingSessionItem[];
}>();

type SessionFilter = 'all' | 'this_month' | 'open_seats';

const activeFilter = ref<SessionFilter>('all');
const { routes } = useRoutes();

function setFilter(value: string | number): void {
    if (value === 'this_month' || value === 'open_seats') {
        activeFilter.value = value;
        return;
    }

    activeFilter.value = 'all';
}

const tabs: { key: SessionFilter; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'this_month', label: 'This Month' },
    { key: 'open_seats', label: 'Open Seats' },
];

const filteredSessions = computed(() => {
    if (activeFilter.value === 'this_month') {
        return props.sessions.filter((session) => session.isThisMonth);
    }

    if (activeFilter.value === 'open_seats') {
        return props.sessions.filter(
            (session) => session.isOpenForBooking && session.remainingSeats > 0,
        );
    }

    return props.sessions;
});
</script>

<template>
    <section
        id="upcoming-sessions"
        class="border-b border-[#272d38]/10 bg-white py-16 lg:py-24"
        aria-labelledby="home-sessions-title"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <header>
                <div class="max-w-2xl">
                    <h2
                        id="home-sessions-title"
                        class="text-3xl font-bold text-[#272d38] sm:text-4xl"
                    >
                        Upcoming Physical Sessions
                    </h2>
                    <p class="mt-2.5 text-[16px] leading-relaxed text-[#272d38]/70">
                        Scheduled Malaysian cohorts open for booking (Malaysia Time, MYT).
                    </p>
                </div>

                <div class="mt-7 flex flex-wrap items-center justify-between gap-4">
                    <Tabs
                        :model-value="activeFilter"
                        @update:model-value="setFilter"
                    >
                        <TabsList aria-label="Filter upcoming physical sessions">
                            <TabsTrigger
                                v-for="tab in tabs"
                                :key="tab.key"
                                :value="tab.key"
                            >
                                {{ tab.label }}
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>

                    <Link
                        :href="routes.sessions"
                        class="group inline-flex items-center gap-1.5 rounded-md text-[15px] font-bold text-[#272d38] transition-colors duration-150 hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                    >
                        <span>All sessions</span>
                        <ArrowRight
                            class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </header>

            <SessionsDataTable
                v-if="filteredSessions.length > 0"
                :sessions="filteredSessions"
                class="mt-6"
            />

            <div
                v-else
                class="mt-8 rounded-xl border border-[#272d38]/12 bg-white px-6 py-10 text-center text-[16px] text-[#272d38]/70"
            >
                <p v-if="sessions.length > 0">
                    No upcoming sessions match this filter.
                    <button
                        type="button"
                        class="ml-1 font-bold text-[#272d38] underline underline-offset-4 hover:opacity-80"
                        @click="activeFilter = 'all'"
                    >
                        Show all sessions
                    </button>
                </p>
                <p v-else>
                    No upcoming physical training cohorts are scheduled yet. Explore our
                    <Link
                        :href="routes.catalogueIndependent"
                        class="font-bold text-[#272d38] underline underline-offset-4 hover:opacity-80"
                    >
                        independent learning modules
                    </Link>
                    or check back soon.
                </p>
            </div>
        </div>
    </section>
</template>
