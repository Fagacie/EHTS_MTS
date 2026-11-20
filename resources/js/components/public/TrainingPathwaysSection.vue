<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Check } from 'lucide-vue-next';
import { Card } from '@/components/ui/card';
import { useRoutes } from '@/composables/useRoutes';
import { pluralize } from '@/lib/utils';
import type { HomepageStats } from '@/types/etms';

defineProps<{
    stats: HomepageStats;
}>();

const { routes } = useRoutes();

const physicalSpecs = [
    'Live workshops across Malaysia',
    'Verified attendance & QR certificates',
    'Post-session recordings for attendees',
];

const independentSpecs = [
    'Self-paced with lifetime portal access',
    'Workbooks and recorded lessons included',
    'Purchase individually, no seat booking needed',
];
</script>

<template>
    <section
        id="delivery-models"
        class="border-b border-[#272d38]/10 bg-white py-16 lg:py-24"
        aria-labelledby="home-pathways-title"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <header class="max-w-2xl">
                <h2
                    id="home-pathways-title"
                    class="text-3xl font-bold text-[#272d38] sm:text-4xl"
                >
                    Two ways to train with Ecohydrotech.
                </h2>
            </header>

            <div class="mt-9 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card
                    class="group flex flex-col justify-between p-8 transition-colors duration-150 hover:border-[#272d38]/30"
                >
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-[14px] font-bold text-[#272d38]/75">
                                In-Person Cohorts
                            </span>
                            <span class="text-[14px] text-[#272d38]/65 tabular-nums">
                                {{ stats.upcomingSessionsCount }}
                                {{ pluralize(stats.upcomingSessionsCount, 'upcoming session') }}
                            </span>
                        </div>

                        <h3 class="mt-4 text-[22px] font-bold text-[#272d38]">
                            Physical Hands-On Training
                        </h3>

                        <p class="mt-2.5 text-[16px] leading-relaxed text-[#272d38]/70">
                            Instructor-led practical engineering workshops hosted at technical venues across Malaysia.
                        </p>

                        <ul class="mt-6 space-y-2.5">
                            <li
                                v-for="spec in physicalSpecs"
                                :key="spec"
                                class="flex items-start gap-2.5 text-[15px] leading-snug text-[#272d38]"
                            >
                                <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                <span>{{ spec }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-8 border-t border-[#272d38]/10 pt-5">
                        <Link
                            :href="routes.sessions"
                            class="inline-flex items-center gap-1.5 rounded-md text-[15px] font-bold text-[#272d38] transition-colors duration-150 hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                        >
                            <span>Browse physical sessions</span>
                            <ArrowRight
                                class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                aria-hidden="true"
                            />
                        </Link>
                    </div>
                </Card>

                <Card
                    class="group flex flex-col justify-between p-8 transition-colors duration-150 hover:border-[#272d38]/30"
                >
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-[14px] font-bold text-[#272d38]/75">
                                Self-Paced Modules
                            </span>
                            <span class="text-[14px] text-[#272d38]/65 tabular-nums">
                                {{ stats.independentModulesCount }}
                                {{ pluralize(stats.independentModulesCount, 'module') }} available
                            </span>
                        </div>

                        <h3 class="mt-4 text-[22px] font-bold text-[#272d38]">
                            Independent Learning Modules
                        </h3>

                        <p class="mt-2.5 text-[16px] leading-relaxed text-[#272d38]/70">
                            Self-paced technical modules available for individual purchase with direct portal access.
                        </p>

                        <ul class="mt-6 space-y-2.5">
                            <li
                                v-for="spec in independentSpecs"
                                :key="spec"
                                class="flex items-start gap-2.5 text-[15px] leading-snug text-[#272d38]"
                            >
                                <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                <span>{{ spec }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-8 border-t border-[#272d38]/10 pt-5">
                        <Link
                            :href="routes.catalogueIndependent"
                            class="inline-flex items-center gap-1.5 rounded-md text-[15px] font-bold text-[#272d38] transition-colors duration-150 hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                        >
                            <span>Explore independent modules</span>
                            <ArrowRight
                                class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                aria-hidden="true"
                            />
                        </Link>
                    </div>
                </Card>
            </div>
        </div>
    </section>
</template>
