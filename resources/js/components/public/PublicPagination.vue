<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
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
        class="mt-10 flex flex-col gap-4 border-t border-border pt-6 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-[13.5px] text-muted-foreground tabular-nums">
            Showing
            <span class="font-bold text-foreground">{{ pagination.from ?? 0 }}</span>
            to
            <span class="font-bold text-foreground">{{ pagination.to ?? 0 }}</span>
            of
            <span class="font-bold text-foreground">{{ pagination.total }}</span>
            {{ itemLabel ?? 'results' }}
        </p>

        <div class="flex flex-wrap items-center gap-1.5">
            <Link
                v-if="pagination.prevPageUrl"
                :href="pagination.prevPageUrl"
                preserve-scroll
                class="inline-flex h-9 items-center gap-1 rounded-lg border border-border bg-card px-3 text-[13px] font-semibold text-foreground transition-colors duration-150 hover:border-ring/50 hover:bg-secondary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/35"
            >
                <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                <span>Previous</span>
            </Link>
            <span
                v-else
                class="inline-flex h-9 items-center gap-1 rounded-lg border border-border bg-card px-3 text-[13px] text-muted-foreground/60"
            >
                <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                <span>Previous</span>
            </span>

            <template v-for="(link, index) in pagination.links" :key="`${index}-${link.label}`">
                <span
                    v-if="!link.url"
                    class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-[13px] text-muted-foreground"
                >
                    {{ link.label }}
                </span>
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :aria-current="link.active ? 'page' : undefined"
                    :class="
                        cn(
                            'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-[13px] font-semibold tabular-nums transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/35',
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-card text-foreground hover:border-ring/50 hover:bg-secondary/60',
                        )
                    "
                >
                    {{ link.label }}
                </Link>
            </template>

            <Link
                v-if="pagination.nextPageUrl"
                :href="pagination.nextPageUrl"
                preserve-scroll
                class="inline-flex h-9 items-center gap-1 rounded-lg border border-border bg-card px-3 text-[13px] font-semibold text-foreground transition-colors duration-150 hover:border-ring/50 hover:bg-secondary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/35"
            >
                <span>Next</span>
                <ChevronRight class="h-4 w-4" aria-hidden="true" />
            </Link>
            <span
                v-else
                class="inline-flex h-9 items-center gap-1 rounded-lg border border-border bg-card px-3 text-[13px] text-muted-foreground/60"
            >
                <span>Next</span>
                <ChevronRight class="h-4 w-4" aria-hidden="true" />
            </span>
        </div>
    </nav>
</template>
