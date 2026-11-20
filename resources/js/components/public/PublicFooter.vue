<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth';
import { useRoutes } from '@/composables/useRoutes';
import type { EtmsSharedProps } from '@/types/etms';

const page = usePage<EtmsSharedProps>();
const app = computed(() => page.props.app);
const { isAuthenticated, defaultDashboardUrl } = useAuth();
const { routes } = useRoutes();
</script>

<template>
    <footer class="border-t border-[#272d38]/10 bg-white text-[#272d38]">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">
                <div class="sm:col-span-2 lg:col-span-5">
                    <Link
                        :href="routes.home"
                        class="inline-flex items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                    >
                        <img
                            :src="app.logoUrl"
                            alt="Ecohydrotech Solution"
                            width="26"
                            height="26"
                            class="h-6.5 w-6.5 shrink-0"
                        >
                        <span class="text-[18px] font-bold text-[#272d38]">
                            {{ app.brandName }}
                        </span>
                    </Link>
                    <p class="mt-4 max-w-sm text-[15px] leading-relaxed text-[#272d38]/65">
                        Technical training in hydrology, environmental science, and civil engineering across Malaysia.
                    </p>
                </div>

                <div class="lg:col-span-3">
                    <h2 class="text-[15px] font-bold text-[#272d38]">
                        Platform
                    </h2>
                    <ul class="mt-3.5 space-y-2.5 text-[15px] text-[#272d38]/70">
                        <li>
                            <Link
                                :href="routes.catalogue"
                                class="rounded-md transition-colors duration-150 hover:text-[#272d38] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                Catalogue
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="routes.sessions"
                                class="rounded-md transition-colors duration-150 hover:text-[#272d38] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                Sessions
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="routes.instructors"
                                class="rounded-md transition-colors duration-150 hover:text-[#272d38] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                Instructors
                            </Link>
                        </li>
                        <li>
                            <a
                                v-if="isAuthenticated"
                                :href="defaultDashboardUrl"
                                class="rounded-md transition-colors duration-150 hover:text-[#272d38] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                My Portal
                            </a>
                            <Link
                                v-else
                                :href="routes.login"
                                class="rounded-md transition-colors duration-150 hover:text-[#272d38] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                Sign in
                            </Link>
                        </li>
                    </ul>
                </div>

                <div class="lg:col-span-4">
                    <h2 class="text-[15px] font-bold text-[#272d38]">
                        Institution &amp; Verification
                    </h2>
                    <ul class="mt-3.5 space-y-2.5 text-[15px] text-[#272d38]/70">
                        <li>
                            <a
                                :href="`${routes.home}#verify-certificate`"
                                class="rounded-md transition-colors duration-150 hover:text-[#272d38] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                Certificate verification standards
                            </a>
                        </li>
                        <li>
                            <a
                                :href="routes.portalDashboard"
                                class="rounded-md transition-colors duration-150 hover:text-[#272d38] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                Trainee Portal
                            </a>
                        </li>
                        <li>
                            <a
                                :href="routes.instructorDashboard"
                                class="rounded-md transition-colors duration-150 hover:text-[#272d38] hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                Instructor Portal
                            </a>
                        </li>
                        <li class="text-[14px] text-[#272d38]/60 tabular-nums">
                            {{ app.displayTimezone }} (MYT) &middot; {{ app.currency }} (RM)
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-12 flex flex-col gap-2 border-t border-[#272d38]/10 pt-6 text-[14px] text-[#272d38]/60 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>&copy; {{ app.year }} {{ app.brandName }}. All rights reserved.</p>
                <p>Session times in Malaysia Time (MYT). Fees in Malaysian Ringgit (RM).</p>
            </div>
        </div>
    </footer>
</template>
