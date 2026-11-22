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
import { useRoutes } from '@/composables/useRoutes';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { cn, pluralize } from '@/lib/utils';
import type { CatalogueIndexProps } from '@/types/etms';

const props = defineProps<CatalogueIndexProps>();

const { routes } = useRoutes();

const search = ref(props.filters.q ?? '');
const sort = ref(props.filters.sort ?? 'newest');
const isFiltering = ref(false);

let searchTimer: ReturnType<typeof setTimeout> | null = null;
let syncingFromProps = false;

watch(
    () => props.filters,
    (next) => {
        syncingFromProps = true;
        search.value = next.q ?? '';
        sort.value = next.sort ?? 'newest';
        syncingFromProps = false;
    },
    { deep: true },
);

const hasActiveFilters = computed(
    () => search.value.trim() !== '' || sort.value !== 'newest',
);

function visitCatalogue(): void {
    const query: Record<string, string> = {};

    if (search.value.trim() !== '') {
        query.q = search.value.trim();
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
    sort.value = 'newest';
    syncingFromProps = false;

    visitCatalogue();
}
</script>

<template>
    <Head title="Standalone Learning Modules" />

    <PublicLayout>
        <section class="border-b border-[#252238]/10 bg-[#F7F8FC] py-14 lg:py-20">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <p class="mb-3 text-xs font-bold uppercase tracking-[0.12em] text-[#159CAA]">Self-paced digital learning</p>
                        <h1 class="text-4xl font-extrabold text-[#252238] sm:text-5xl">
                            Standalone Learning Modules
                        </h1>
                        <p class="mt-3 text-[16px] leading-relaxed text-[#272d38]/70">
                            Independent digital learning modules with downloadable workbooks, technical references, and video lessons you can purchase without attending a physical session.
                        </p>
                    </div>

                    <div class="shrink-0 sm:text-right">
                        <p class="text-2xl font-bold text-[#272d38] tabular-nums">
                            {{ pagination.total }}
                        </p>
                        <p class="text-[14px] text-[#272d38]/65 tabular-nums">
                            {{ pluralize(pagination.total, 'module') }}
                            <template v-if="hasActiveFilters">
                                of {{ totalPublishedCount }}
                            </template>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-12 lg:py-16" aria-label="Standalone learning modules">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 rounded-lg border border-[#252238]/10 bg-[#F7F8FC] p-4">
                    <div class="grid gap-3 md:grid-cols-12 md:items-center">
                        <div class="relative md:col-span-8">
                            <label for="catalogue-search-input" class="sr-only">
                                Search standalone learning modules
                            </label>
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#272d38]/50"
                                aria-hidden="true"
                            />
                            <Input
                                id="catalogue-search-input"
                                v-model="search"
                                type="search"
                                placeholder="Search standalone modules by title, code, or topic..."
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
                            <label for="catalogue-sort-select" class="sr-only">
                                Sort standalone modules
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

                    <div v-if="isFiltering || hasActiveFilters" class="flex items-center justify-end gap-4 pt-1">
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
                        No matching standalone learning modules
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-[16px] text-[#272d38]/65">
                        No published standalone modules match your current search or filter selection.
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
                    :item-label="pluralize(pagination.total, 'module')"
                />
            </div>
        </section>
    </PublicLayout>
</template>
