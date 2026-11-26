<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from 'lucide-vue-next';
import type { FeaturedModuleItem } from '@/types/etms';

const props = defineProps<{
    module: FeaturedModuleItem;
}>();

const metaLine = computed(() => {
    if (
        props.module.categoryName &&
        props.module.categorySlug !== 'standalone-module' &&
        props.module.categoryName.toLowerCase() !== 'standalone module'
    ) {
        return props.module.categoryName;
    }

    const parts: string[] = [];
    if (props.module.publishedVideosCount > 0) {
        parts.push(
            `${props.module.publishedVideosCount} ${props.module.publishedVideosCount === 1 ? 'video' : 'videos'}`,
        );
    }
    if (props.module.publishedMaterialsCount > 0) {
        parts.push(
            `${props.module.publishedMaterialsCount} ${props.module.publishedMaterialsCount === 1 ? 'manual' : 'manuals'}`,
        );
    }
    return parts.join(' · ') || null;
});
</script>

<template>
    <Link
        :href="module.detailUrl"
        class="group block rounded-2xl p-2 -m-2 transition-all duration-200 hover:bg-sand-50/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
    >
        <!-- Borderless Photo-First Media -->
        <div class="relative aspect-[16/10] overflow-hidden rounded-2xl bg-navy-900 shadow-card transition-shadow duration-200 group-hover:shadow-card-hover">
            <img
                v-if="module.coverImageUrl"
                :src="module.coverImageUrl"
                :alt="module.title"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.04]"
            />
            <div
                v-else
                class="flex h-full w-full flex-col justify-between bg-navy-900 p-5 text-white"
            >
                <span class="tabular-nums-mono text-xs font-medium text-white/60">
                    {{ module.code }}
                </span>
                <p class="line-clamp-2 text-base font-semibold leading-snug text-white">
                    {{ module.title }}
                </p>
            </div>
        </div>

        <!-- Minimal Editorial Metadata -->
        <div class="px-1 pt-3.5 pb-1">
            <h3 class="line-clamp-2 text-[16px] font-semibold leading-snug text-navy-950 transition-colors duration-150 group-hover:text-primary-600">
                {{ module.title }}
            </h3>

            <p v-if="metaLine" class="tabular mt-1 text-xs font-medium text-muted">
                {{ metaLine }}
            </p>

            <div class="mt-3 flex items-center justify-between gap-3">
                <span class="tabular text-sm font-bold text-navy-950">
                    {{ module.independentPriceLabel ?? 'Included in Cohort' }}
                </span>

                <span class="inline-flex items-center gap-1 text-xs font-semibold text-muted transition-colors duration-150 group-hover:text-coral-600">
                    <span class="relative after:absolute after:bottom-0 after:left-0 after:h-[1.5px] after:w-0 after:bg-coral-500 after:transition-all after:duration-200 group-hover:after:w-full">
                        View module
                    </span>
                    <ArrowUpRight
                        class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </span>
            </div>
        </div>
    </Link>
</template>
