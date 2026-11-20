<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { useRoutes } from '@/composables/useRoutes';
import type { HomepageAssets } from '@/types/etms';

defineProps<{
    assets: HomepageAssets;
}>();

const { routes } = useRoutes();
</script>

<template>
    <section
        class="border-b border-[#272d38]/10 bg-white py-16 lg:py-24"
        aria-labelledby="home-hero-title"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-14">
                <div class="lg:col-span-6">
                    <h1
                        id="home-hero-title"
                        class="text-4xl font-bold text-[#272d38] sm:text-5xl lg:text-[50px] lg:leading-[1.12] text-balance"
                    >
                        Technical training in water, environment, and engineering.
                    </h1>

                    <p class="mt-5 max-w-xl text-[17px] leading-relaxed text-[#272d38]/70">
                        Practical courses in hydrology, stormwater design, and ecohydrology for engineers and organisations across Malaysia. Enrol in scheduled instructor-led workshops or purchase independent modules for self-directed study.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Button as-child size="lg">
                            <Link :href="routes.sessions">Browse Physical Sessions</Link>
                        </Button>
                        <Button as-child variant="outline" size="lg">
                            <Link :href="routes.catalogueIndependent">Explore Independent Modules</Link>
                        </Button>
                    </div>
                </div>

                <div class="lg:col-span-6">
                    <div class="overflow-hidden rounded-xl border border-[#272d38]/12 bg-white shadow-[0_1px_2px_rgba(39,45,56,0.04)]">
                        <picture>
                            <source
                                v-if="assets.heroPhotoWebpUrl"
                                :srcset="assets.heroPhotoWebpUrl"
                                type="image/webp"
                            >
                            <img
                                :src="assets.heroPhotoUrl"
                                alt="Environmental technicians taking river flow velocity measurements in a Malaysian river"
                                width="1264"
                                height="848"
                                fetchpriority="high"
                                class="aspect-[16/10] w-full object-cover object-[62%_45%]"
                            >
                        </picture>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
