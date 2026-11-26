<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BookOpen, RotateCcw, Search } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import CourseCard from '@/components/public/CourseCard.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { CatalogueIndexProps } from '@/types/etms';

const props = defineProps<CatalogueIndexProps>();

const q = ref(props.filters.q);
const category = ref(props.filters.category);
const sort = ref(props.filters.sort || 'newest');

watch(
    () => props.filters,
    (next) => {
        q.value = next.q;
        category.value = next.category;
        sort.value = next.sort || 'newest';
    },
);

function applyFilters(): void {
    const params: Record<string, string> = {};
    if (q.value.trim()) params.q = q.value.trim();
    if (category.value) params.category = category.value;
    if (sort.value && sort.value !== 'newest') params.sort = sort.value;

    router.get(props.routes.catalogue, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function selectCategory(slug: string): void {
    category.value = slug;
    applyFilters();
}

function resetFilters(): void {
    q.value = '';
    category.value = '';
    sort.value = 'newest';
    applyFilters();
}

const hasActiveFilters = () => Boolean(q.value.trim() || category.value || sort.value !== 'newest');
</script>

<template>
    <PublicLayout>
        <Head title="Training Modules" />

        <!-- Page Header -->
        <section class="border-b border-line bg-sand-50/70">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div class="max-w-2xl">
                        <h1 class="type-page-title">Training modules</h1>
                        <p class="mt-2 text-sm leading-relaxed text-muted sm:text-base">
                            Self-paced digital modules with downloadable technical manuals, worksheets, and video lessons.
                        </p>
                    </div>
                    <p class="tabular text-sm font-medium text-muted">
                        <span class="font-semibold text-navy-950">{{ pagination.total }}</span>
                        {{ pagination.total === 1 ? 'module' : 'modules' }} available
                    </p>
                </div>

                <!-- Filter Bar -->
                <form
                    class="mt-7 grid gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 md:grid-cols-12"
                    @submit.prevent="applyFilters"
                >
                    <div class="md:col-span-6">
                        <label for="catalogue-q" class="sr-only">Search training modules</label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted"
                                aria-hidden="true"
                            />
                            <input
                                id="catalogue-q"
                                v-model="q"
                                type="text"
                                placeholder="Search by module code, title, or topic..."
                                class="h-10.5 w-full rounded-xl border border-line-strong/80 bg-white pr-3.5 pl-10 text-sm text-ink placeholder:text-muted/75 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label for="catalogue-sort" class="sr-only">Sort modules</label>
                        <select
                            id="catalogue-sort"
                            v-model="sort"
                            class="h-10.5 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            @change="applyFilters"
                        >
                            <option value="newest">Newest published</option>
                            <option value="title_asc">Title (A–Z)</option>
                            <option value="price_asc">Price (Low to High)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 md:col-span-3">
                        <AppButton type="submit" variant="primary" size="md" class="h-10.5 flex-1">
                            Search
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

                <!-- Subject Filter Pills -->
                <div v-if="categories.length > 0" class="mt-4 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="cursor-pointer rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            category === ''
                                ? 'bg-navy-950 text-white'
                                : 'bg-white text-muted ring-1 ring-line hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectCategory('')"
                    >
                        All subjects
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        class="cursor-pointer rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            category === cat.slug
                                ? 'bg-navy-950 text-white'
                                : 'bg-white text-muted ring-1 ring-line hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectCategory(cat.slug)"
                    >
                        {{ cat.name }}
                        <span class="tabular ml-1 opacity-70">({{ cat.publishedModulesCount }})</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Results Grid -->
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-if="modules.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <CourseCard
                    v-for="mod in modules"
                    :key="mod.id"
                    :module="mod"
                />
            </div>

            <EmptyState
                v-else
                :icon="BookOpen"
                title="No training modules matched your filter"
                description="Try clearing your search keyword or switching to another subject area."
            >
                <template #action>
                    <AppButton variant="outline" size="sm" pill @click="resetFilters">
                        Clear filters
                    </AppButton>
                </template>
            </EmptyState>

            <PublicPagination :pagination="pagination" item-label="modules" />
        </section>
    </PublicLayout>
</template>
