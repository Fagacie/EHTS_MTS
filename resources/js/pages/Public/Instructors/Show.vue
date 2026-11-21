<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from 'lucide-vue-next';
import SessionsDataTable from '@/components/public/SessionsDataTable.vue';
import { Badge } from '@/components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Card } from '@/components/ui/card';
import { useRoutes } from '@/composables/useRoutes';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { getInitials } from '@/lib/utils';
import type { InstructorShowProps } from '@/types/etms';

defineProps<InstructorShowProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head :title="instructor.displayName" />

    <PublicLayout>
        <section class="border-b border-[#252238]/10 bg-[#F7F8FC] py-10 lg:py-14">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink :href="routes.instructors">Instructors</BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage>{{ instructor.displayName }}</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>

                <div class="mt-6 flex flex-wrap items-center gap-6">
                    <img
                        v-if="instructor.photoUrl"
                        :src="instructor.photoUrl"
                        :alt="instructor.displayName"
                        class="h-28 w-28 shrink-0 rounded-full border-4 border-white object-cover shadow-lg"
                    >
                    <span
                        v-else
                        class="flex h-28 w-28 shrink-0 items-center justify-center rounded-full bg-[#7057D9] text-2xl font-bold tracking-wide text-white shadow-lg"
                    >
                        {{ getInitials(instructor.displayName) }}
                    </span>

                    <div>
                        <h1 class="text-4xl font-extrabold text-[#252238] sm:text-5xl">
                            {{ instructor.displayName }}
                        </h1>
                        <p
                            v-if="instructor.professionalTitle"
                            class="mt-1.5 text-[16px] text-[#272d38]/70"
                        >
                            {{ instructor.professionalTitle }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-12 lg:py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-12 lg:items-start">
                    <div class="space-y-10 lg:col-span-8">
                        <div>
                            <h2 class="text-[22px] font-bold text-[#272d38]">
                                Professional Biography
                            </h2>
                            <div class="mt-4 whitespace-pre-line text-[16px] leading-relaxed text-[#272d38]/75">
                                {{ instructor.biography || instructor.shortBio || 'Detailed professional biography will be updated shortly.' }}
                            </div>
                        </div>

                        <div class="border-t border-[#272d38]/10 pt-8">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <h2 class="text-2xl font-bold text-[#272d38]">
                                    Upcoming Assigned Sessions
                                </h2>
                                <Link
                                    :href="routes.sessions"
                                    class="group inline-flex items-center gap-1.5 text-[15px] font-bold text-[#272d38] hover:underline underline-offset-4"
                                >
                                    <span>All sessions</span>
                                    <ArrowRight class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true" />
                                </Link>
                            </div>

                            <SessionsDataTable
                                v-if="upcomingSessions.length > 0"
                                :sessions="upcomingSessions"
                                class="mt-6"
                            />

                            <Card
                                v-else
                                class="mt-5 p-6 text-[16px] text-[#272d38]/70"
                            >
                                No upcoming public sessions are currently scheduled for this instructor.
                                <Link
                                    :href="routes.sessions"
                                    class="ml-1 font-bold text-[#272d38] underline underline-offset-4 hover:opacity-80"
                                >
                                    Browse all scheduled workshops
                                </Link>
                            </Card>
                        </div>
                    </div>

                    <aside class="space-y-6 lg:col-span-4">
                        <Card class="space-y-6 p-6">
                            <div v-if="instructor.qualifications">
                                <p class="text-[14px] font-bold text-[#272d38]">
                                    Credentials
                                </p>
                                <p class="mt-2 text-[15px] font-semibold text-[#272d38]/80">
                                    {{ instructor.qualifications }}
                                </p>
                            </div>

                            <div
                                v-if="instructor.specialisations.length > 0"
                                :class="instructor.qualifications ? 'border-t border-[#272d38]/10 pt-5' : ''"
                            >
                                <p class="text-[14px] font-bold text-[#272d38]">
                                    Technical Specialisations
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <Badge
                                        v-for="spec in instructor.specialisations"
                                        :key="spec"
                                        variant="secondary"
                                        class="font-normal"
                                    >
                                        {{ spec }}
                                    </Badge>
                                </div>
                            </div>

                            <div
                                v-if="instructor.shortBio"
                                class="border-t border-[#272d38]/10 pt-5"
                            >
                                <p class="text-[14px] font-bold text-[#272d38]">
                                    Summary
                                </p>
                                <p class="mt-2 text-[14px] leading-relaxed text-[#272d38]/70">
                                    {{ instructor.shortBio }}
                                </p>
                            </div>

                            <div class="border-t border-[#272d38]/10 pt-5">
                                <Link
                                    :href="routes.instructors"
                                    class="inline-flex items-center gap-1.5 text-[15px] font-bold text-[#272d38] hover:underline underline-offset-4"
                                >
                                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                                    <span>All instructors</span>
                                </Link>
                            </div>
                        </Card>
                    </aside>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
