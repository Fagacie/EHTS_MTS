<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Check } from 'lucide-vue-next';
import { useRoutes } from '@/composables/useRoutes';
import { getInitials } from '@/lib/utils';
import type { HomepageAssets, PublicInstructorItem } from '@/types/etms';

defineProps<{
    instructors: PublicInstructorItem[];
    assets: HomepageAssets;
}>();

const { routes } = useRoutes();

const highlights = [
    'Verified session attendance',
    'QR-verified digital certificates',
    'Individual & corporate invoicing',
];
</script>

<template>
    <section
        id="verify-certificate"
        class="bg-white py-16 lg:py-24"
        aria-labelledby="home-credibility-title"
    >
        <span id="verify-preview" class="sr-only" aria-hidden="true" />

        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-12">
                <div class="lg:col-span-6">
                    <div class="overflow-hidden rounded-xl border border-[#272d38]/12 bg-white shadow-[0_1px_2px_rgba(39,45,56,0.04)]">
                        <picture>
                            <source
                                v-if="assets.classroomPhotoWebpUrl"
                                :srcset="assets.classroomPhotoWebpUrl"
                                type="image/webp"
                            >
                            <img
                                :src="assets.classroomPhotoUrl"
                                alt="Instructor guiding professionals through a hydrological catchment diagram in a training room"
                                width="900"
                                height="740"
                                loading="lazy"
                                class="aspect-[4/3] w-full object-cover object-[55%_42%]"
                            >
                        </picture>
                    </div>
                </div>

                <div class="flex flex-col justify-center lg:col-span-6">
                    <h2
                        id="home-credibility-title"
                        class="text-3xl font-bold text-[#272d38] sm:text-4xl"
                    >
                        Practical training led by practising engineers.
                    </h2>
                    <p class="mt-4 text-[16px] leading-relaxed text-[#272d38]/70">
                        Every workshop and self-paced course is built around practical Malaysian engineering standards, clear attendance records, and straightforward billing for individuals and organisations.
                    </p>

                    <ul class="mt-6 space-y-2.5">
                        <li
                            v-for="item in highlights"
                            :key="item"
                            class="flex items-center gap-2.5 text-[15px] font-semibold text-[#272d38]"
                        >
                            <Check class="h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 border-t border-[#272d38]/10 pt-10">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h3 class="text-[20px] font-bold text-[#272d38]">
                        Faculty &amp; Instructors
                    </h3>
                    <Link
                        :href="routes.instructors"
                        class="group inline-flex items-center gap-1.5 rounded-md text-[15px] font-bold text-[#272d38] transition-colors duration-150 hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                    >
                        <span>All instructors</span>
                        <ArrowRight
                            class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                </div>

                <div
                    v-if="instructors.length > 0"
                    class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="instructor in instructors"
                        :key="instructor.id"
                        :href="instructor.profileUrl"
                        class="group flex items-center gap-4 rounded-xl border border-[#272d38]/12 bg-white p-4 shadow-[0_1px_2px_rgba(39,45,56,0.04)] transition-colors duration-150 hover:border-[#272d38]/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                    >
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#272d38] text-sm font-bold tracking-wide text-white"
                        >
                            {{ getInitials(instructor.displayName) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-[16px] font-bold text-[#272d38] group-hover:underline underline-offset-4">
                                {{ instructor.displayName }}
                            </p>
                            <p class="mt-0.5 truncate text-[14px] text-[#272d38]/65">
                                {{ instructor.professionalTitle ?? instructor.specialisations[0] ?? 'Practising Technical Instructor' }}
                            </p>
                        </div>
                    </Link>
                </div>

                <p v-else class="mt-6 text-[16px] text-[#272d38]/70">
                    Instructor profiles are published as sessions are scheduled.
                </p>
            </div>
        </div>
    </section>
</template>
