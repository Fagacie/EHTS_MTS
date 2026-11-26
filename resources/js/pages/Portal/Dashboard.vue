<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    ArrowUpRight,
    Award,
    BookOpen,
    CalendarCheck,
    CheckCircle2,
    CreditCard,
    Download,
    Video,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import FeaturedCourseCard from '@/components/ds/FeaturedCourseCard.vue';
import KpiCard from '@/components/ds/KpiCard.vue';
import LearnerCalendarCard from '@/components/ds/LearnerCalendarCard.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { pluralize } from '@/lib/utils';
import type { PortalDashboardProps } from '@/types/etms';

const props = defineProps<PortalDashboardProps>();

const { routes } = useRoutes();

type ActivityFilter = 'all' | 'booking' | 'order';
const activityFilter = ref<ActivityFilter>('all');
const activitySearch = ref('');

const activityTabs: { key: ActivityFilter; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'booking', label: 'Sessions' },
    { key: 'order', label: 'Orders' },
];

const nextBooking = computed(() => props.upcomingBookings[0] ?? null);

const nextSessionCaption = computed(() => {
    if (props.stats.upcomingBookingsCount === 0) {
        return props.stats.pendingPaymentsCount > 0
            ? `${props.stats.pendingPaymentsCount} ${pluralize(props.stats.pendingPaymentsCount, 'pending payment')}`
            : 'No sessions scheduled';
    }
    if (props.stats.daysUntilNextSession === 0) return 'Starts today';
    if (props.stats.daysUntilNextSession !== null) {
        return `Next in ${props.stats.daysUntilNextSession} ${pluralize(props.stats.daysUntilNextSession, 'day')}`;
    }
    return 'Confirmed schedule';
});

const latestActivity = computed(() => {
    const q = activitySearch.value.trim().toLowerCase();

    return props.recentActivity
        .filter((item) => {
            if (activityFilter.value !== 'all' && item.type !== activityFilter.value) {
                return false;
            }
            if (!q) return true;
            return (
                item.title.toLowerCase().includes(q) ||
                item.reference.toLowerCase().includes(q) ||
                item.statusLabel.toLowerCase().includes(q)
            );
        })
        .slice(0, 5);
});

const viewAllActivityUrl = computed(() =>
    activityFilter.value === 'booking' ? routes.value.portalBookings : routes.value.portalOrders,
);

function activityTone(statusValue: string): StatusTone {
    if (['confirmed', 'paid', 'completed', 'issued'].includes(statusValue)) return 'success';
    if (['pending_payment', 'pending', 'processing'].includes(statusValue)) return 'warning';
    if (['cancelled', 'expired', 'failed', 'refunded', 'revoked'].includes(statusValue)) return 'danger';
    return 'primary';
}
</script>

<template>
    <Head title="Learner Dashboard" />

    <PortalLayout
        v-model:search-query="activitySearch"
        breadcrumb="Dashboard"
        :unread-count="portalMeta.unreadNotificationsCount"
    >
        <div class="mx-auto max-w-[1440px] space-y-6">
            <!-- Greeting & Quick Actions -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">
                        Welcome back, {{ portalMeta.userName }}
                    </h1>
                    <p class="mt-1 text-sm text-muted">
                        {{ stats.summaryLine }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <AppButton
                        v-if="portalMeta.continueLearningUrl"
                        :href="portalMeta.continueLearningUrl"
                        variant="outline"
                        size="sm"
                        pill
                    >
                        <span>Continue Learning</span>
                        <ArrowRight class="size-3.5" aria-hidden="true" />
                    </AppButton>
                    <AppButton :href="routes.sessions" variant="warm" size="sm" pill>
                        <span>Explore Sessions</span>
                        <ArrowRight class="size-3.5" aria-hidden="true" />
                    </AppButton>
                </div>
            </header>

            <!-- Active Payment Hold Banner -->
            <div
                v-if="activeHoldBooking"
                role="status"
                class="flex flex-col justify-between gap-3 rounded-2xl bg-warning-50 p-4 ring-1 ring-inset ring-warning-600/20 sm:flex-row sm:items-center sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 size-5 shrink-0 text-warning-600" aria-hidden="true" />
                    <div>
                        <p class="text-sm font-semibold text-navy-950">
                            Seat held for {{ activeHoldBooking.sessionTitle }}
                        </p>
                        <p class="mt-0.5 text-xs text-warning-700">
                            Ref <span class="tabular-nums-mono font-semibold">{{ activeHoldBooking.bookingReference }}</span>
                            <template v-if="activeHoldBooking.expiresAtLabel">
                                &middot; Expires {{ activeHoldBooking.expiresAtLabel }}
                            </template>
                        </p>
                    </div>
                </div>
                <AppButton :href="activeHoldBooking.detailUrl" variant="primary" size="sm" pill>
                    Complete Payment
                </AppButton>
            </div>

            <!-- 4-Column KPI Grid (highlight / dark / default variants) -->
            <section aria-label="Learning summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <KpiCard
                    label="Upcoming Sessions"
                    :value="stats.upcomingBookingsCount"
                    :caption="nextSessionCaption"
                    :icon="CalendarCheck"
                    variant="highlight"
                    :href="routes.portalBookings"
                />
                <KpiCard
                    label="Active Modules"
                    :value="stats.activeModulesCount"
                    caption="Unlocked self-paced access"
                    :icon="BookOpen"
                    variant="dark"
                    :href="routes.portalModules"
                />
                <KpiCard
                    label="Completed Sessions"
                    :value="stats.completedSessionsCount"
                    caption="Verified attendance"
                    :icon="CheckCircle2"
                    variant="default"
                    :href="routes.portalBookings"
                />
                <KpiCard
                    label="Certificates Earned"
                    :value="stats.certificatesCount"
                    caption="Official digital records"
                    :icon="Award"
                    variant="default"
                    :href="routes.portalCertificates"
                />
            </section>

            <!-- Active Course Card (with Progress Stepper) + Interactive Monthly Calendar -->
            <section class="grid gap-5 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <FeaturedCourseCard
                        :booking="nextBooking"
                        :browse-url="routes.sessions"
                        :modules-url="routes.portalModules"
                    />
                </div>
                <div class="lg:col-span-6">
                    <LearnerCalendarCard
                        :events="calendarEvents"
                        :initial-date="calendarInitialDate"
                    />
                </div>
            </section>

            <!-- Compact 2-Column Lower Row: Latest Updates (max 5) + Quick Access / Certificates -->
            <section class="grid gap-5 lg:grid-cols-12">
                <!-- Compact Activity Feed (capped at 5 items so dashboard stays light) -->
                <div class="flex flex-col rounded-2xl bg-white p-5 shadow-card ring-1 ring-neutral-200/80 lg:col-span-7">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-base font-bold text-navy-950">Latest updates</h2>

                        <div class="flex items-center gap-2">
                            <div class="inline-flex rounded-full bg-sand-100 p-0.5">
                                <button
                                    v-for="tab in activityTabs"
                                    :key="tab.key"
                                    type="button"
                                    class="cursor-pointer rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                                    :class="
                                        activityFilter === tab.key
                                            ? 'bg-white text-navy-950 shadow-xs'
                                            : 'text-muted hover:text-navy-950'
                                    "
                                    @click="activityFilter = tab.key"
                                >
                                    {{ tab.label }}
                                </button>
                            </div>

                            <Link
                                :href="viewAllActivityUrl"
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold text-primary-600 transition-colors hover:bg-primary-50"
                            >
                                <span>View all</span>
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            </Link>
                        </div>
                    </div>

                    <ul v-if="latestActivity.length > 0" class="mt-4 divide-y divide-neutral-100">
                        <li v-for="item in latestActivity" :key="item.id">
                            <Link
                                :href="item.actionUrl"
                                class="group flex items-center justify-between gap-3 py-3 transition-colors hover:bg-sand-50/70 -mx-2 px-2 rounded-xl"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex size-8.5 shrink-0 items-center justify-center rounded-xl"
                                        :class="
                                            item.type === 'booking'
                                                ? 'bg-accent-50 text-accent-700'
                                                : 'bg-primary-50 text-primary-600'
                                        "
                                    >
                                        <CalendarCheck v-if="item.type === 'booking'" class="size-4" aria-hidden="true" />
                                        <CreditCard v-else class="size-4" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-navy-950 group-hover:text-primary-600">
                                            {{ item.title }}
                                        </p>
                                        <p class="mt-0.5 flex items-center gap-1.5 text-xs text-muted">
                                            <span class="tabular-nums-mono">{{ item.reference }}</span>
                                            <span>&middot;</span>
                                            <span class="tabular">{{ item.shortDateLabel }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-3">
                                    <StatusBadge :tone="activityTone(item.statusValue)">
                                        {{ item.statusLabel }}
                                    </StatusBadge>
                                    <span class="tabular hidden text-xs font-semibold text-navy-950 sm:inline">
                                        {{ item.amountLabel }}
                                    </span>
                                </div>
                            </Link>
                        </li>
                    </ul>

                    <div v-else class="flex flex-1 flex-col items-center justify-center py-10 text-center">
                        <p class="text-sm font-semibold text-navy-950">No recent updates yet</p>
                        <p class="mt-1 text-xs text-muted">
                            Your latest session bookings and orders will appear here.
                        </p>
                    </div>
                </div>

                <!-- Right Column: Certificates & Learning Shortcuts -->
                <div class="flex flex-col gap-5 lg:col-span-5">
                    <!-- Earned Certificates Card -->
                    <div class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-neutral-200/80">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-bold text-navy-950">Certificates</h2>
                            <Link
                                :href="routes.portalCertificates"
                                class="text-xs font-semibold text-primary-600 hover:text-primary-700"
                            >
                                View all
                            </Link>
                        </div>

                        <ul v-if="certificates.length > 0" class="mt-3.5 space-y-2.5">
                            <li
                                v-for="cert in certificates.slice(0, 3)"
                                :key="cert.id"
                                class="flex items-center justify-between gap-3 rounded-xl bg-sand-50 px-3.5 py-2.5"
                            >
                                <div class="min-w-0">
                                    <Link
                                        :href="cert.viewUrl"
                                        class="block truncate text-sm font-semibold text-navy-950 hover:text-primary-600"
                                    >
                                        {{ cert.title }}
                                    </Link>
                                    <p class="tabular-nums-mono mt-0.5 text-[11px] text-muted">
                                        {{ cert.certificateNumber }} &middot; {{ cert.issuedDateLabel }}
                                    </p>
                                </div>
                                <AppButton
                                    :href="cert.downloadUrl"
                                    external
                                    variant="outline"
                                    size="sm"
                                    icon-only
                                    pill
                                    :aria-label="`Download ${cert.title} certificate`"
                                >
                                    <Download class="size-3.5" aria-hidden="true" />
                                </AppButton>
                            </li>
                        </ul>

                        <p v-else class="mt-3 text-xs leading-relaxed text-muted">
                            Certificates appear here automatically once your attendance is verified.
                        </p>
                    </div>

                    <!-- Compact Learning Library Shortcuts -->
                    <div class="grid grid-cols-2 gap-3">
                        <Link
                            :href="routes.portalModules"
                            class="group flex flex-col justify-between rounded-2xl bg-white p-4 shadow-card ring-1 ring-neutral-200/80 transition-all duration-150 hover:-translate-y-0.5 hover:shadow-card-hover"
                        >
                            <BookOpen class="size-5 text-primary-600" aria-hidden="true" />
                            <div class="mt-4">
                                <p class="text-sm font-semibold text-navy-950 group-hover:text-primary-600">
                                    My Modules
                                </p>
                                <p class="mt-0.5 text-xs text-muted">Manuals &amp; lessons</p>
                            </div>
                        </Link>

                        <Link
                            :href="routes.portalRecordings"
                            class="group flex flex-col justify-between rounded-2xl bg-white p-4 shadow-card ring-1 ring-neutral-200/80 transition-all duration-150 hover:-translate-y-0.5 hover:shadow-card-hover"
                        >
                            <Video class="size-5 text-accent-600" aria-hidden="true" />
                            <div class="mt-4">
                                <p class="text-sm font-semibold text-navy-950 group-hover:text-primary-600">
                                    Recordings
                                </p>
                                <p class="mt-0.5 text-xs text-muted">Session replays</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </PortalLayout>
</template>
