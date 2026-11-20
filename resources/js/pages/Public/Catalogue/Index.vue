<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import ProgrammeCard from '@/components/public/ProgrammeCard.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useRoutes } from '@/composables/useRoutes';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { cn, pluralize } from '@/lib/utils';
import type { CatalogueIndexProps } from '@/types/etms';

const props = defineProps<CatalogueIndexProps>();

const { routes } = useRoutes();

const search = ref(props.filters.q ?? '');
const category = ref(props.filters.category ?? '');
const availability = ref(props.filters.availability ?? 'all');
const sort = ref(props.filters.sort ?? 'newest');
const isFiltering = ref(false);

let searchTimer: ReturnType<typeof setTimeout> | null = null;
let syncingFromProps = false;

watch(
    () => props.filters,
    (next) => {
        syncingFromProps = true;
        search.value = next.q ?? '';
        category.value = next.category ?? '';
        availability.value = next.availability ?? 'all';
        sort.value = next.sort ?? 'newest';
        syncingFromProps = false;
    },
    { deep: true },
);

const availabilityOptions = [
    { value: 'all', label: 'All' },
    { value: 'independent', label: 'Independent Modules' },
    { value: 'session_only', label: 'Physical Sessions' },
] as const;

const hasActiveFilters = computed(
    () =>
        search.value.trim() !== '' ||
        category.value !== '' ||
        availability.value !== 'all' ||
        sort.value !== 'newest',
);

const activeCategories = computed(() =>
    props.categories.filter((cat) => cat.publishedModulesCount > 0 || cat.slug === category.value),
);

function visitCatalogue(): void {
    const query: Record<string, string> = {};

    if (search.value.trim() !== '') {
        query.q = search.value.trim();
    }

    if (category.value !== '') {
        query.category = category.value;
    }

    if (availability.value !== 'all') {
        query.availability = availability.value;
    }

    if (sort.value !== 'newest') {
        query.sort = sort.value;
    }

    router.get(routes.value.catalogue, query, {
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
        visitCatalogue();
    }, 280);
});

onBeforeUnmount(() => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }
});

function setCategory(nextCategory: string): void {
    category.value = nextCategory;
    visitCatalogue();
}

function setAvailability(nextAvailability: string | number): void {
    const resolved = String(nextAvailability);
    if (availability.value === resolved) {
        return;
    }

    availability.value = resolved;
    visitCatalogue();
}

function setSort(nextSort: string): void {
    sort.value = nextSort;
    visitCatalogue();
}

function clearSearch(): void {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    search.value = '';
    visitCatalogue();
}

function clearFilters(): void {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    syncingFromProps = true;
    search.value = '';
    category.value = '';
    availability.value = 'all';
    sort.value = 'newest';
    syncingFromProps = false;

    visitCatalogue();
}
</script>

<template>
    <Head title="Training Catalogue" />

    <PublicLayout>
        <section class="border-b border-[#272d38]/10 bg-white py-12 lg:py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <h1 class="text-3xl font-bold text-[#272d38] sm:text-4xl">
                            Training Catalogue
                        </h1>
                        <p class="mt-3 text-[16px] leading-relaxed text-[#272d38]/70">
                            Technical courses in hydrology, flood modelling, and water engineering for in-person cohorts and independent study.
                        </p>
                    </div>

                    <div class="shrink-0 sm:text-right">
                        <p class="text-2xl font-bold text-[#272d38] tabular-nums">
                            {{ pagination.total }}
                        </p>
                        <p class="text-[14px] text-[#272d38]/65 tabular-nums">
                            {{ pluralize(pagination.total, 'programme') }}
                            <template v-if="hasActiveFilters">
                                of {{ totalPublishedCount }}
                            </template>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-12 lg:py-16" aria-label="Training programmes">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 border-b border-[#272d38]/10 pb-6">
                    <div class="grid gap-3 md:grid-cols-12 md:items-center">
                        <div class="relative md:col-span-5">
                            <label for="catalogue-search-input" class="sr-only">
                                Search training programmes
                            </label>
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#272d38]/50"
                                aria-hidden="true"
                            />
                            <Input
                                id="catalogue-search-input"
                                v-model="search"
                                type="search"
                                placeholder="Search programmes or topics..."
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

                        <div class="md:col-span-4">
                            <label for="catalogue-category-select" class="sr-only">
                                Filter by category
                            </label>
                            <NativeSelect
                                id="catalogue-category-select"
                                :model-value="category"
                                @update:model-value="setCategory"
                            >
                                <option value="">All Categories</option>
                                <option
                                    v-for="cat in activeCategories"
                                    :key="cat.slug"
                                    :value="cat.slug"
                                >
                                    {{ cat.name }} ({{ cat.publishedModulesCount }})
                                </option>
                            </NativeSelect>
                        </div>

                        <div class="md:col-span-3">
                            <label for="catalogue-sort-select" class="sr-only">
                                Sort programmes
                            </label>
                            <NativeSelect
                                id="catalogue-sort-select"
                                :model-value="sort"
                                @update:model-value="setSort"
                            >
                                <option value="newest">Newest First</option>
                                <option value="title_asc">Title (A–Z)</option>
                                <option value="price_asc">Lowest Price</option>
                            </NativeSelect>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
                        <Tabs
                            :model-value="availability"
                            @update:model-value="setAvailability"
                        >
                            <TabsList aria-label="Filter by delivery format">
                                <TabsTrigger
                                    v-for="option in availabilityOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
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

                <div
                    v-if="modules.length > 0"
                    :class="
                        cn(
                            'mt-8 grid gap-6 transition-opacity duration-150 md:grid-cols-2 lg:grid-cols-3',
                            isFiltering && 'opacity-65',
                        )
                    "
                >
                    <ProgrammeCard
                        v-for="mod in modules"
                        :key="mod.id"
                        :module="mod"
                    />
                </div>

                <Card
                    v-else
                    class="mt-8 px-6 py-14 text-center"
                >
                    <h2 class="text-[19px] font-bold text-[#272d38]">
                        No matching training programmes
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-[16px] text-[#272d38]/65">
                        No published programmes match your current search or filter selection.
                    </p>
                    <div class="mt-6">
                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="outline"
                            @click="clearFilters"
                        >
                            Clear filters
                        </Button>
                    </div>
                </Card>

                <PublicPagination
                    :pagination="pagination"
                    :item-label="pluralize(pagination.total, 'programme')"
                />
            </div>
        </section>
    </PublicLayout>
</template>
