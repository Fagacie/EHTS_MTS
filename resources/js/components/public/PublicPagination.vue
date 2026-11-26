<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import type { PaginationMeta } from '@/types/etms';

defineProps<{
    pagination: PaginationMeta;
    itemLabel?: string;
}>();
</script>

<template>
    <nav
        v-if="pagination.lastPage > 1"
        aria-label="Pagination"
        class="mt-10 flex flex-col gap-4 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="tabular text-sm text-muted">
            Showing
            <span class="font-semibold text-navy-950">{{ pagination.from ?? 0 }}</span>
            to
            <span class="font-semibold text-navy-950">{{ pagination.to ?? 0 }}</span>
            of
            <span class="font-semibold text-navy-950">{{ pagination.total }}</span>
            {{ itemLabel ?? 'results' }}
        </p>

        <div class="flex flex-wrap items-center gap-1.5">
            <Link
                v-if="pagination.prevPageUrl"
                :href="pagination.prevPageUrl"
                preserve-scroll
                class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-white px-3 text-xs font-semibold text-ink transition-colors duration-150 hover:border-line-strong hover:bg-sand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                <ChevronLeft class="size-4" aria-hidden="true" />
                <span>Previous</span>
            </Link>
            <span
                v-else
                class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-sand-50 px-3 text-xs font-medium text-muted/60"
            >
                <ChevronLeft class="size-4" aria-hidden="true" />
                <span>Previous</span>
            </span>

            <template v-for="(link, index) in pagination.links" :key="`${index}-${link.label}`">
                <span
                    v-if="!link.url"
                    class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-xs text-muted"
                >
                    {{ link.label }}
                </span>
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :aria-current="link.active ? 'page' : undefined"
                    class="tabular inline-flex h-9 min-w-9 items-center justify-center rounded-xl border px-3 text-xs font-semibold transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                    :class="
                        link.active
                            ? 'border-navy-950 bg-navy-950 text-white'
                            : 'border-line bg-white text-ink hover:border-line-strong hover:bg-sand-50'
                    "
                >
                    {{ link.label }}
                </Link>
            </template>

            <Link
                v-if="pagination.nextPageUrl"
                :href="pagination.nextPageUrl"
                preserve-scroll
                class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-white px-3 text-xs font-semibold text-ink transition-colors duration-150 hover:border-line-strong hover:bg-sand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                <span>Next</span>
                <ChevronRight class="size-4" aria-hidden="true" />
            </Link>
            <span
                v-else
                class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-sand-50 px-3 text-xs font-medium text-muted/60"
            >
                <span>Next</span>
                <ChevronRight class="size-4" aria-hidden="true" />
            </span>
        </div>
    </nav>
</template>
