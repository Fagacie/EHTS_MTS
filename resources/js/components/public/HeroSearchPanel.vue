<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { CalendarDays, Layers, Search } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import type { CategorySummary } from '@/types/etms';

const props = defineProps<{
    categories: CategorySummary[];
    sessionsUrl: string;
    catalogueUrl: string;
}>();

const activeTab = ref<'sessions' | 'modules'>('sessions');
const keyword = ref('');
const category = ref('');
const availability = ref('all');
const sort = ref('newest');

function submitSearch(): void {
    if (activeTab.value === 'sessions') {
        const params: Record<string, string> = {};
        if (keyword.value.trim()) params.q = keyword.value.trim();
        if (category.value) params.category = category.value;
        if (availability.value !== 'all') params.availability = availability.value;

        router.get(props.sessionsUrl, params);
        return;
    }

    const params: Record<string, string> = {};
    if (keyword.value.trim()) params.q = keyword.value.trim();
    if (category.value) params.category = category.value;
    if (sort.value !== 'newest') params.sort = sort.value;

    router.get(props.catalogueUrl, params);
}
</script>

<template>
    <div class="relative z-20 mx-auto -mt-12 max-w-5xl px-3 sm:-mt-16 sm:px-6">
        <div class="rounded-2xl bg-white p-4 shadow-floating ring-1 ring-neutral-200/80 sm:p-5">
            <!-- Pill Tab Switcher -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div
                    role="tablist"
                    aria-label="Search training type"
                    class="inline-flex rounded-full bg-sand-100 p-1"
                >
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'sessions'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition-all duration-150 sm:text-sm"
                        :class="
                            activeTab === 'sessions'
                                ? 'bg-white text-navy-950 shadow-card'
                                : 'text-muted hover:text-navy-950'
                        "
                        @click="activeTab = 'sessions'"
                    >
                        <CalendarDays class="size-4 text-accent-600" aria-hidden="true" />
                        <span>Live Sessions</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === 'modules'"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition-all duration-150 sm:text-sm"
                        :class="
                            activeTab === 'modules'
                                ? 'bg-white text-navy-950 shadow-card'
                                : 'text-muted hover:text-navy-950'
                        "
                        @click="activeTab = 'modules'"
                    >
                        <Layers class="size-4 text-primary-600" aria-hidden="true" />
                        <span>Self-Paced Modules</span>
                    </button>
                </div>

                <p class="hidden text-xs font-medium text-muted sm:block">
                    {{
                        activeTab === 'sessions'
                            ? 'Filter instructor-led cohorts by discipline, city, or seat availability'
                            : 'Explore standalone digital modules with videos & technical manuals'
                    }}
                </p>
            </div>

            <!-- Inline Search Bar -->
            <form
                class="grid gap-3 md:grid-cols-12"
                @submit.prevent="submitSearch"
            >
                <div class="md:col-span-5">
                    <label for="hero-search-q" class="sr-only">Search keyword</label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            id="hero-search-q"
                            v-model="keyword"
                            type="text"
                            :placeholder="
                                activeTab === 'sessions'
                                    ? 'Search session title, code, or city...'
                                    : 'Search module title, code, or topic...'
                            "
                            class="h-11 w-full rounded-xl border border-line-strong/80 bg-white pr-3.5 pl-10 text-sm text-ink placeholder:text-muted/75 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label for="hero-search-category" class="sr-only">Discipline category</label>
                    <select
                        id="hero-search-category"
                        v-model="category"
                        class="h-11 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                    >
                        <option value="">All disciplines</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.slug"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <template v-if="activeTab === 'sessions'">
                        <label for="hero-search-availability" class="sr-only">Availability</label>
                        <select
                            id="hero-search-availability"
                            v-model="availability"
                            class="h-11 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        >
                            <option value="all">All dates</option>
                            <option value="open_seats">Open seats</option>
                            <option value="this_month">This month</option>
                        </select>
                    </template>
                    <template v-else>
                        <label for="hero-search-sort" class="sr-only">Sort order</label>
                        <select
                            id="hero-search-sort"
                            v-model="sort"
                            class="h-11 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        >
                            <option value="newest">Newest</option>
                            <option value="title_asc">Title A–Z</option>
                            <option value="price_asc">Lowest price</option>
                        </select>
                    </template>
                </div>

                <div class="md:col-span-2">
                    <AppButton type="submit" variant="warm" size="lg" class="h-11 w-full">
                        <span>Search</span>
                    </AppButton>
                </div>
            </form>
        </div>
    </div>
</template>
