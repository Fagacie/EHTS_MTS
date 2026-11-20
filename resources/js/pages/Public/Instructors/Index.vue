<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { getInitials, pluralize } from '@/lib/utils';
import type { InstructorsIndexProps } from '@/types/etms';

defineProps<InstructorsIndexProps>();
</script>

<template>
    <Head title="Faculty & Instructors" />

    <PublicLayout>
        <section class="border-b border-[#272d38]/10 bg-white py-12 lg:py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <h1 class="text-3xl font-bold text-[#272d38] sm:text-4xl">
                            Faculty &amp; Instructors
                        </h1>
                        <p class="mt-3 text-[16px] leading-relaxed text-[#272d38]/70">
                            Practising civil engineers, hydrologists, and environmental specialists leading workshops across Malaysia.
                        </p>
                    </div>

                    <div class="shrink-0 sm:text-right">
                        <p class="text-2xl font-bold text-[#272d38] tabular-nums">
                            {{ instructors.length }}
                        </p>
                        <p class="text-[14px] text-[#272d38]/65">
                            Published {{ pluralize(instructors.length, 'instructor') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-12 lg:py-16" aria-label="Instructor directory">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="instructors.length > 0"
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <Card
                        v-for="instructor in instructors"
                        :key="instructor.id"
                        class="group flex flex-col justify-between p-6 transition-colors duration-150 hover:border-[#272d38]/30"
                    >
                        <div>
                            <div class="flex items-center gap-4">
                                <img
                                    v-if="instructor.photoUrl"
                                    :src="instructor.photoUrl"
                                    :alt="instructor.displayName"
                                    class="h-14 w-14 shrink-0 rounded-full border border-[#272d38]/12 object-cover"
                                >
                                <span
                                    v-else
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#272d38] text-base font-bold tracking-wide text-white"
                                >
                                    {{ getInitials(instructor.displayName) }}
                                </span>

                                <div class="min-w-0">
                                    <h2 class="truncate text-[18px] font-bold text-[#272d38]">
                                        <Link
                                            :href="instructor.profileUrl"
                                            class="rounded-md transition-colors duration-150 group-hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                                        >
                                            {{ instructor.displayName }}
                                        </Link>
                                    </h2>
                                    <p
                                        v-if="instructor.professionalTitle"
                                        class="mt-0.5 truncate text-[14px] text-[#272d38]/65"
                                    >
                                        {{ instructor.professionalTitle }}
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="instructor.qualifications"
                                class="mt-4 text-[14px] font-semibold text-[#272d38]"
                            >
                                {{ instructor.qualifications }}
                            </p>

                            <p
                                v-if="instructor.shortBio"
                                class="mt-2.5 line-clamp-2 text-[15px] leading-relaxed text-[#272d38]/70"
                            >
                                {{ instructor.shortBio }}
                            </p>

                            <div
                                v-if="instructor.specialisations.length > 0"
                                class="mt-4 flex flex-wrap gap-2"
                            >
                                <Badge
                                    v-for="spec in instructor.specialisations.slice(0, 3)"
                                    :key="spec"
                                    variant="secondary"
                                    class="font-normal"
                                >
                                    {{ spec }}
                                </Badge>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between gap-4 border-t border-[#272d38]/10 pt-4">
                            <span class="text-[14px] text-[#272d38]/65 tabular-nums">
                                {{ instructor.upcomingSessionsCount ?? 0 }}
                                {{ pluralize(instructor.upcomingSessionsCount ?? 0, 'upcoming session') }}
                            </span>
                            <Link
                                :href="instructor.profileUrl"
                                class="inline-flex items-center gap-1.5 text-[15px] font-bold text-[#272d38] hover:underline underline-offset-4"
                            >
                                <span>View profile</span>
                                <ArrowRight
                                    class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                    aria-hidden="true"
                                />
                            </Link>
                        </div>
                    </Card>
                </div>

                <Card
                    v-else
                    class="px-6 py-14 text-center"
                >
                    <h2 class="text-[19px] font-bold text-[#272d38]">
                        No public instructor profiles published yet
                    </h2>
                    <p class="mx-auto mt-2 max-w-md text-[16px] text-[#272d38]/65">
                        Instructor profiles appear here once scheduled workshops are released.
                    </p>
                </Card>
            </div>
        </section>
    </PublicLayout>
</template>
