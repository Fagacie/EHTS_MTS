<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    CalendarDays,
    MapPin,
    Users,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import CourseCard from '@/components/public/CourseCard.vue';
import HeroSearchPanel from '@/components/public/HeroSearchPanel.vue';
import ProcessStrip from '@/components/public/ProcessStrip.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { HomepageProps, UpcomingSessionItem } from '@/types/etms';

const props = defineProps<HomepageProps>();

const sessionColumns: DataTableColumn[] = [
    { key: 'schedule', label: 'Date & Time', tabular: true },
    { key: 'title', label: 'Training Session' },
    { key: 'venue', label: 'Location' },
    { key: 'seats', label: 'Availability', tabular: true },
    { key: 'priceLabel', label: 'Fee', align: 'right', tabular: true },
];

type SessionRow = UpcomingSessionItem & Record<string, unknown>;

const sessionRows = computed<SessionRow[]>(() => props.upcomingSessions as SessionRow[]);

function sessionTone(session: UpcomingSessionItem): StatusTone {
    if (!session.isOpenForBooking || session.remainingSeats <= 0) return 'danger';
    if (session.remainingSeats <= 5) return 'warning';
    return 'success';
}

function instructorInitials(name: string): string {
    const parts = name.trim().split(/\s+/);
    return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '')).toUpperCase();
}
</script>

<template>
    <PublicLayout>
        <Head title="Hydrology & Environmental Engineering Training" />

        <div class="space-y-20 pb-8 sm:space-y-24">
            <!-- Sunlit Editorial Hero + Floating Multi-Tab Search Panel -->
            <section class="mx-auto max-w-7xl px-4 pt-5 sm:px-6 sm:pt-7 lg:px-8">
                <div class="relative overflow-hidden rounded-3xl bg-navy-950 shadow-elevated">
                    <img
                        :src="assets.heroPhotoUrl"
                        alt="Engineers inspecting a river hydrology monitoring station in Malaysia"
                        class="absolute inset-0 h-full w-full object-cover object-center"
                    />

                    <!-- Clean directional contrast scrim keeping left typography legible while preserving the right-side photo -->
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-navy-950/90 via-navy-950/60 to-navy-950/15"
                        aria-hidden="true"
                    />

                    <div class="relative z-10 px-6 pt-16 pb-24 sm:px-12 sm:pt-20 sm:pb-28 lg:px-16 lg:pt-24 lg:pb-32">
                        <div class="max-w-xl">
                            <h1 class="type-display text-white">
                                Practical hydrology &amp; water-engineering courses.
                            </h1>

                            <p class="mt-4 max-w-lg text-base leading-relaxed text-white/85 sm:text-[17px]">
                                Instructor-led field cohorts across Malaysia and self-paced technical modules with manuals, recordings, and digital certificates.
                            </p>

                            <div class="mt-8 flex flex-wrap items-center gap-3">
                                <AppButton :href="routes.sessions" variant="warm" size="lg" pill>
                                    <span>View Live Sessions</span>
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </AppButton>
                                <AppButton
                                    :href="routes.catalogue"
                                    variant="outline"
                                    size="lg"
                                    pill
                                    class="!border-white/30 !bg-navy-950/35 !text-white backdrop-blur-xs hover:!bg-white/15"
                                >
                                    <span>Browse Modules</span>
                                </AppButton>
                            </div>

                            <dl class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-4 border-t border-white/15 pt-6 text-white">
                                <div>
                                    <dt class="text-xs text-white/65">Upcoming intakes</dt>
                                    <dd class="tabular mt-0.5 text-xl font-bold">{{ stats.upcomingSessionsCount }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-white/65">Published modules</dt>
                                    <dd class="tabular mt-0.5 text-xl font-bold">{{ stats.publishedModulesCount }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-white/65">Instructors</dt>
                                    <dd class="tabular mt-0.5 text-xl font-bold">{{ stats.publicInstructorsCount }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Floating Multi-Tab Search Bar -->
                <HeroSearchPanel
                    :categories="categories"
                    :sessions-url="routes.sessions"
                    :catalogue-url="routes.catalogue"
                />
            </section>

            <!-- Upcoming Live Sessions Schedule (Most Actionable First) -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="type-page-title">Upcoming live sessions</h2>
                        <p class="mt-1.5 text-sm text-muted">
                            Real-time seat counts with {{ stats.reservationHoldMinutes }}-minute checkout holds.
                        </p>
                    </div>
                    <AppButton :href="routes.sessions" variant="outline" size="sm" pill>
                        <CalendarDays class="size-3.5" aria-hidden="true" />
                        <span>All Sessions</span>
                    </AppButton>
                </div>

                <div class="mt-6">
                    <DataTable
                        :columns="sessionColumns"
                        :rows="sessionRows"
                        row-key="id"
                        empty-title="No upcoming live cohorts scheduled right now"
                        empty-description="Explore our self-paced training modules or check back soon for new intake dates."
                    >
                        <template #cell-schedule="{ row }">
                            <div class="font-semibold text-navy-950">{{ row.dateRangeLabel }}</div>
                            <div class="mt-0.5 text-xs text-muted">{{ row.timeLabel }}</div>
                        </template>

                        <template #cell-title="{ row }">
                            <Link
                                :href="row.detailUrl"
                                class="font-semibold text-navy-950 transition-colors hover:text-primary-600"
                            >
                                {{ row.title }}
                            </Link>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted">
                                <span class="tabular-nums-mono">{{ row.sessionCode }}</span>
                                <span v-if="row.moduleCategory">&middot; {{ row.moduleCategory }}</span>
                                <span v-if="row.instructorNames">&middot; {{ row.instructorNames }}</span>
                            </div>
                        </template>

                        <template #cell-venue="{ row }">
                            <div class="inline-flex items-start gap-1.5 text-sm text-ink">
                                <MapPin class="mt-0.5 size-3.5 shrink-0 text-muted" aria-hidden="true" />
                                <span>
                                    {{ row.venueName }}
                                    <span v-if="row.venueLocation" class="block text-xs text-muted">
                                        {{ row.venueLocation }}
                                    </span>
                                </span>
                            </div>
                        </template>

                        <template #cell-seats="{ row }">
                            <StatusBadge :tone="sessionTone(row)" dot>
                                <template v-if="row.isOpenForBooking && row.remainingSeats > 0">
                                    {{ row.remainingSeats }} / {{ row.capacity }} open
                                </template>
                                <template v-else>
                                    {{ row.statusLabel }}
                                </template>
                            </StatusBadge>
                        </template>

                        <template #cell-priceLabel="{ row }">
                            <span class="font-semibold text-navy-950">{{ row.priceLabel }}</span>
                        </template>

                        <template #actions="{ row }">
                            <AppButton
                                :href="row.detailUrl"
                                :variant="row.isOpenForBooking ? 'primary' : 'outline'"
                                size="sm"
                                pill
                            >
                                {{ row.isOpenForBooking ? 'Reserve' : 'Details' }}
                            </AppButton>
                        </template>
                    </DataTable>
                </div>
            </section>

            <!-- Photo-First Self-Paced Training Modules -->
            <section v-if="featuredModules.length > 0" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="type-page-title">Self-paced training modules</h2>
                        <p class="mt-1.5 text-sm text-muted">
                            Standalone digital modules with downloadable technical manuals and video lessons.
                        </p>
                    </div>
                    <AppButton :href="routes.catalogue" variant="outline" size="sm" pill>
                        <span>Full Catalogue</span>
                        <ArrowRight class="size-3.5" aria-hidden="true" />
                    </AppButton>
                </div>

                <div class="mt-7 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <CourseCard
                        v-for="mod in featuredModules"
                        :key="mod.id"
                        :module="mod"
                    />
                </div>
            </section>

            <!-- Compact Disciplines Directory (Editorial Index, No Repetitive Icon Boxes) -->
            <section v-if="categories.length > 0" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-3 border-b border-line pb-5 sm:flex-row sm:items-end">
                    <h2 class="type-section-title text-navy-950">Browse by subject area</h2>
                    <Link
                        :href="routes.catalogue"
                        class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700"
                    >
                        <span>All modules</span>
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="cat in categories"
                        :key="cat.id"
                        :href="cat.catalogueUrl"
                        class="group flex items-start justify-between gap-3 rounded-xl border border-line bg-white p-4 transition-all duration-150 hover:border-navy-700/35 hover:bg-sand-50/60"
                    >
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-navy-950 transition-colors group-hover:text-primary-600">
                                {{ cat.name }}
                            </h3>
                            <p v-if="cat.description" class="mt-1 line-clamp-2 text-xs leading-relaxed text-muted">
                                {{ cat.description }}
                            </p>
                            <p class="tabular mt-2.5 text-xs font-medium text-accent-700">
                                {{ cat.publishedModulesCount }} {{ cat.publishedModulesCount === 1 ? 'module' : 'modules' }}
                            </p>
                        </div>
                        <ArrowUpRight class="mt-0.5 size-4 shrink-0 text-muted transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-primary-600" aria-hidden="true" />
                    </Link>
                </div>
            </section>

            <!-- 4-Step Process Strip -->
            <ProcessStrip :reservation-hold-minutes="stats.reservationHoldMinutes" />

            <!-- Instructors Strip -->
            <section v-if="instructors.length > 0" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="type-page-title">Course instructors</h2>
                        <p class="mt-1.5 text-sm text-muted">
                            Field engineers and hydrologists leading Ecohydrotech sessions.
                        </p>
                    </div>
                    <AppButton :href="routes.instructors" variant="outline" size="sm" pill>
                        <Users class="size-3.5" aria-hidden="true" />
                        <span>All Instructors</span>
                    </AppButton>
                </div>

                <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="instructor in instructors"
                        :key="instructor.id"
                        :href="instructor.profileUrl"
                        class="group flex flex-col rounded-2xl bg-white p-5 shadow-card ring-1 ring-neutral-200/80 transition-all duration-150 hover:-translate-y-0.5 hover:shadow-card-hover"
                    >
                        <div class="flex items-center gap-3.5">
                            <img
                                v-if="instructor.photoUrl"
                                :src="instructor.photoUrl"
                                :alt="instructor.displayName"
                                loading="lazy"
                                class="size-13 shrink-0 rounded-full object-cover ring-2 ring-sand-100"
                            />
                            <div
                                v-else
                                class="flex size-13 shrink-0 items-center justify-center rounded-full bg-navy-900 text-sm font-bold text-white"
                            >
                                {{ instructorInitials(instructor.displayName) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-navy-950 transition-colors group-hover:text-primary-600">
                                    {{ instructor.displayName }}
                                </h3>
                                <p v-if="instructor.professionalTitle" class="truncate text-xs text-muted">
                                    {{ instructor.professionalTitle }}
                                </p>
                            </div>
                        </div>

                        <p v-if="instructor.shortBio" class="mt-3.5 line-clamp-3 text-xs leading-relaxed text-muted">
                            {{ instructor.shortBio }}
                        </p>

                        <div v-if="instructor.specialisations.length > 0" class="mt-3.5 flex flex-wrap gap-1.5">
                            <span
                                v-for="spec in instructor.specialisations.slice(0, 2)"
                                :key="spec"
                                class="rounded-md bg-sand-100 px-2 py-0.5 text-[11px] font-medium text-navy-900"
                            >
                                {{ spec }}
                            </span>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Grounded Classroom Feature & Account CTA -->
            <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid overflow-hidden rounded-3xl border border-line bg-sand-50 lg:grid-cols-12">
                    <div class="flex flex-col justify-center p-8 sm:p-11 lg:col-span-7">
                        <h2 class="type-page-title">
                            Manage bookings, recordings, and certificates in one place
                        </h2>
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-muted sm:text-base">
                            Sign up for a learner account to reserve live training seats, download module manuals, stream session recordings, and retrieve official PDF certificates.
                        </p>
                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <AppButton :href="routes.register" variant="primary" size="lg" pill>
                                <span>Create Learner Account</span>
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </AppButton>
                            <AppButton :href="routes.login" variant="outline" size="lg" pill>
                                <span>Sign in</span>
                            </AppButton>
                        </div>
                    </div>

                    <div class="relative min-h-60 lg:col-span-5">
                        <picture class="absolute inset-0 h-full w-full">
                            <source
                                v-if="assets.classroomPhotoWebpUrl"
                                :srcset="assets.classroomPhotoWebpUrl"
                                type="image/webp"
                            />
                            <img
                                :src="assets.classroomPhotoUrl"
                                alt="Hydrology workshop classroom session"
                                loading="lazy"
                                class="h-full w-full object-cover"
                            />
                        </picture>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
