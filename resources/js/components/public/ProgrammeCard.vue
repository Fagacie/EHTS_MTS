<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import ProgrammeBanner from '@/components/public/ProgrammeBanner.vue';
import { formatDisplayFee } from '@/lib/utils';
import type { FeaturedModuleItem } from '@/types/etms';

defineProps<{
    module: FeaturedModuleItem;
}>();
</script>

<template>
    <Link
        :href="module.detailUrl"
        class="group flex flex-col overflow-hidden rounded-lg border border-[#252238]/12 bg-white shadow-[0_4px_18px_rgba(37,34,56,0.05)] transition-all duration-150 hover:-translate-y-1 hover:border-[#7057D9]/30 hover:shadow-[0_12px_28px_rgba(37,34,56,0.10)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/30"
    >
        <ProgrammeBanner
            :cover-image-url="module.coverImageUrl"
            :category-slug="module.categorySlug"
            :title="module.title"
        />

        <div class="flex flex-1 flex-col justify-between p-6">
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-[#159CAA]">
                    {{ module.categoryName }}
                </p>
                <h3 class="mt-2 line-clamp-2 text-[18px] font-extrabold leading-snug text-[#252238]">
                    {{ module.title }}
                </h3>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4 border-t border-[#272d38]/10 pt-4">
                <span class="text-[16px] font-bold text-[#272d38] tabular-nums">
                    {{
                        module.isAvailableIndependently && module.independentPriceLabel
                            ? formatDisplayFee(module.independentPriceLabel)
                            : 'Physical sessions'
                    }}
                </span>
                <span class="inline-flex items-center gap-1.5 text-[15px] font-bold text-[#272d38]">
                    <span>View</span>
                    <ArrowRight
                        class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                        aria-hidden="true"
                    />
                </span>
            </div>
        </div>
    </Link>
</template>
