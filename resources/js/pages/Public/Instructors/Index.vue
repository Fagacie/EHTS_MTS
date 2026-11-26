<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Award, CalendarDays, Users } from 'lucide-vue-next';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { InstructorsIndexProps } from '@/types/etms';

defineProps<InstructorsIndexProps>();

function initials(name: string): string {
    const parts = name.trim().split(/\s+/);
    return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '')).toUpperCase();
}
</script>

<template>
    <PublicLayout>
        <Head title="Instructors" />

        <section class="border-b border-line bg-sand-50/70">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div class="max-w-2xl">
                        <h1 class="type-page-title">Instructors</h1>
                        <p class="mt-2 text-sm leading-relaxed text-muted sm:text-base">
                            Engineers and hydrologists leading Ecohydrotech workshops and technical cohorts.
                        </p>
                    </div>
                    <p class="tabular text-sm font-medium text-muted">
                        <span class="font-semibold text-navy-950">{{ instructors.length }}</span>
                        {{ instructors.length === 1 ? 'instructor' : 'instructors' }}
                    </p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-if="instructors.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="instructor in instructors"
                    :key="instructor.id"
                    :href="instructor.profileUrl"
                    class="group flex flex-col overflow-hidden rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80 transition-all duration-150 hover:-translate-y-0.5 hover:shadow-card-hover"
                >
                    <div class="flex items-center gap-3.5">
                        <img
                            v-if="instructor.photoUrl"
                            :src="instructor.photoUrl"
                            :alt="instructor.displayName"
                            loading="lazy"
                            class="size-15 shrink-0 rounded-full object-cover ring-2 ring-sand-100"
                        />
                        <div
                            v-else
                            class="flex size-15 shrink-0 items-center justify-center rounded-full bg-navy-900 text-base font-bold text-white"
                        >
                            {{ initials(instructor.displayName) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold text-navy-950 transition-colors group-hover:text-primary-600">
                                {{ instructor.displayName }}
                            </h2>
                            <p v-if="instructor.professionalTitle" class="mt-0.5 text-xs font-medium text-muted">
                                {{ instructor.professionalTitle }}
                            </p>
                        </div>
                    </div>

                    <div v-if="instructor.qualifications" class="mt-4 flex items-start gap-2 text-xs font-medium text-navy-800">
                        <Award class="mt-0.5 size-3.5 shrink-0 text-accent-600" aria-hidden="true" />
                        <span>{{ instructor.qualifications }}</span>
                    </div>

                    <p v-if="instructor.shortBio" class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted">
                        {{ instructor.shortBio }}
                    </p>

                    <div v-if="instructor.specialisations.length > 0" class="mt-4 flex flex-wrap gap-1.5">
                        <span
                            v-for="spec in instructor.specialisations"
                            :key="spec"
                            class="rounded-md bg-sand-100 px-2 py-0.5 text-[11px] font-medium text-navy-900"
                        >
                            {{ spec }}
                        </span>
                    </div>

                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-4 mt-5">
                        <StatusBadge
                            v-if="(instructor.upcomingSessionsCount ?? 0) > 0"
                            tone="primary"
                        >
                            <CalendarDays class="size-3" aria-hidden="true" />
                            <span class="tabular">
                                {{ instructor.upcomingSessionsCount }} upcoming {{ instructor.upcomingSessionsCount === 1 ? 'session' : 'sessions' }}
                            </span>
                        </StatusBadge>
                        <span v-else class="text-xs text-muted">Instructor</span>

                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-navy-950 group-hover:text-primary-600">
                            <span>Profile</span>
                            <ArrowRight class="size-3.5" aria-hidden="true" />
                        </span>
                    </div>
                </Link>
            </div>

            <EmptyState
                v-else
                :icon="Users"
                title="No instructor profiles published yet"
                description="Instructor profiles will appear here once published."
            />
        </section>
    </PublicLayout>
</template>
