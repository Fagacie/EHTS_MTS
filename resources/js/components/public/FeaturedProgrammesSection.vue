<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import ProgrammeCard from '@/components/public/ProgrammeCard.vue';
import { useRoutes } from '@/composables/useRoutes';
import type { FeaturedModuleItem } from '@/types/etms';

defineProps<{
    modules: FeaturedModuleItem[];
}>();

const { routes } = useRoutes();
</script>

<template>
    <section
        id="featured-programmes"
        class="border-b border-[#272d38]/10 bg-white py-16 lg:py-24"
        aria-labelledby="home-featured-title"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2
                        id="home-featured-title"
                        class="text-3xl font-bold text-[#272d38] sm:text-4xl"
                    >
                        Standalone Learning Modules
                    </h2>
                </div>

                <Link
                    :href="routes.catalogue"
                    class="group inline-flex items-center gap-1.5 rounded-md text-[15px] font-bold text-[#272d38] transition-colors duration-150 hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                >
                    <span>Explore standalone modules</span>
                    <ArrowRight
                        class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                        aria-hidden="true"
                    />
                </Link>
            </header>

            <div
                v-if="modules.length > 0"
                class="mt-9 grid gap-6 md:grid-cols-2 lg:grid-cols-3"
            >
                <ProgrammeCard
                    v-for="mod in modules"
                    :key="mod.id"
                    :module="mod"
                />
            </div>

            <div
                v-else
                class="mt-9 rounded-xl border border-[#272d38]/12 bg-white px-6 py-10 text-center text-[16px] text-[#272d38]/70"
            >
                Published standalone learning modules will appear here once released.
            </div>
        </div>
    </section>
</template>
