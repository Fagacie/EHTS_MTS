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
        class="group flex flex-col overflow-hidden rounded-xl border border-[#272d38]/12 bg-white shadow-[0_1px_2px_rgba(39,45,56,0.04)] transition-colors duration-150 hover:border-[#272d38]/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
    >
        <ProgrammeBanner
            :cover-image-url="module.coverImageUrl"
            :category-slug="module.categorySlug"
            :title="module.title"
        />

        <div class="flex flex-1 flex-col justify-between p-6">
            <div>
                <p class="text-[14px] text-[#272d38]/65">
                    {{ module.categoryName }}
                </p>
                <h3 class="mt-1.5 line-clamp-2 text-[19px] font-bold leading-snug text-[#272d38]">
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
