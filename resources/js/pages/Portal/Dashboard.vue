<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    Award,
    BookOpen,
    CalendarCheck,
    Clock,
    Download,
    MapPin,
    Play,
    X,
} from 'lucide-vue-next';
import PortalActivityChart from '@/components/portal/PortalActivityChart.vue';
import PortalCalendar from '@/components/portal/PortalCalendar.vue';
import PortalPortfolioChart from '@/components/portal/PortalPortfolioChart.vue';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { formatDisplayFee, pluralize } from '@/lib/utils';
import type { PortalDashboardProps } from '@/types/etms';

const props = defineProps<PortalDashboardProps>();

const { routes } = useRoutes();

type ActivityFilter = 'all' | 'booking' | 'order';

const activityFilter = ref<ActivityFilter>('all');
const activitySearch = ref('');

function setActivityFilter(value: string | number): void {
    if (value === 'booking' || value === 'order') {
        activityFilter.value = value;
        return;
    }

    activityFilter.value = 'all';
}

const activityTabs: { key: ActivityFilter; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'booking', label: 'Bookings' },
    { key: 'order', label: 'Orders' },
];

const filteredActivity = computed(() => {
    const q = activitySearch.value.trim().toLowerCase();

    return props.recentActivity.filter((item) => {
        const typeOk = activityFilter.value === 'all' || item.type === activityFilter.value;
        if (!typeOk) {
            return false;
        }

        if (!q) {
            return true;
        }

        return (
            item.title.toLowerCase().includes(q) ||
            item.reference.toLowerCase().includes(q) ||
            item.kindLabel.toLowerCase().includes(q) ||
            item.detail.toLowerCase().includes(q) ||
            item.statusLabel.toLowerCase().includes(q)
        );
    });
});

const nextSessionCaption = computed(() => {
    if (props.stats.upcomingBookingsCount === 0) {
        return props.stats.pendingPaymentsCount > 0
            ? `${props.stats.pendingPaymentsCount} ${pluralize(props.stats.pendingPaymentsCount, 'pending payment')}`
            : 'No sessions scheduled';
    }

    if (props.stats.daysUntilNextSession === 0) {
        return 'Next session starts today';
    }

    if (props.stats.daysUntilNextSession !== null) {
        return `Next session in ${props.stats.daysUntilNextSession} ${pluralize(props.stats.daysUntilNextSession, 'day')}`;
    }

    return 'Confirmed schedule';
});

function clearActivityFilters(): void {
    activityFilter.value = 'all';
    activitySearch.value = '';
}
</script>

<template>
    <Head title="Trainee Dashboard" />

    <PortalLayout
        v-model:search-query="activitySearch"
        breadcrumb="Dashboard"
        :unread-count="portalMeta.unreadNotificationsCount"
    >
        <div class="mx-auto max-w-[1440px] space-y-4">
            <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        Welcome back, {{ portalMeta.userName }}
                    </h1>
                    <p class="mt-1 text-[13.5px] text-[#625D6D]">
                        {{ stats.summaryLine }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Button
                        v-if="portalMeta.continueLearningUrl"
                        as-child
                        variant="outline"
                    >
                        <Link :href="portalMeta.continueLearningUrl">
                            <span>Continue Learning</span>
                            <ArrowRight class="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </Button>

                    <Button as-child>
                        <Link :href="routes.catalogue">
                            <span>Explore Training</span>
                            <ArrowRight class="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </header>

            <Card
                v-if="activeHoldBooking"
                role="status"
                class="flex flex-col gap-3.5 border-[#E8CFA1] bg-[#FBF2E3] p-4 shadow-none sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#8B5A17]" aria-hidden="true" />
                    <div class="min-w-0">
                        <p class="text-[14px] font-bold text-[#292536]">
                            Pending Seat Reservation ({{ activeHoldBooking.bookingReference }})
                        </p>
                        <p class="mt-0.5 text-[13px] text-[#403B4B]">
                            {{ activeHoldBooking.sessionTitle }}
                            <template v-if="activeHoldBooking.expiresAtLabel">
                                &middot; Expires {{ activeHoldBooking.expiresAtLabel }}
                            </template>
                        </p>
                    </div>
                </div>

                <Button
                    as-child
                    size="sm"
                    class="shrink-0"
                >
                    <Link :href="activeHoldBooking.detailUrl">
                        Complete Payment
                    </Link>
                </Button>
            </Card>

            <section aria-label="Training Summary">
                <div class="grid grid-cols-2 gap-3 sm:gap-3.5 xl:grid-cols-4">
                    <Link
                        :href="routes.portalModules"
                        class="block min-w-0 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    >
                        <Card class="flex h-full flex-col justify-between p-3.5 transition-colors duration-150 hover:border-[#B9ADE9] sm:p-4">
                            <div class="flex items-center justify-between gap-2.5">
                                <span class="text-[12.5px] font-semibold text-[#625D6D]">
                                    Active Modules
                                </span>
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[9px] bg-[#F1EDFB] text-[#5942B5]">
                                    <BookOpen class="h-4 w-4" aria-hidden="true" />
                                </span>
                            </div>
                            <p class="mt-2.5 text-[29px] leading-none font-extrabold tracking-tight text-[#282338] tabular-nums">
                                {{ stats.activeModulesCount }}
                            </p>
                            <p
                                :class="
                                    stats.activeModulesCount > 0
                                        ? 'mt-2 text-[12px] font-semibold text-[#5942B5]'
                                        : 'mt-2 text-[12px] text-[#625D6D]'
                                "
                            >
                                {{
                                    stats.activeModulesCount > 0
                                        ? 'Self-paced portal access'
                                        : 'No modules unlocked yet'
                                }}
                            </p>
                        </Card>
                    </Link>

                    <Link
                        :href="routes.portalBookings"
                        class="block min-w-0 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    >
                        <Card class="flex h-full flex-col justify-between p-3.5 transition-colors duration-150 hover:border-[#B9ADE9] sm:p-4">
                            <div class="flex items-center justify-between gap-2.5">
                                <span class="text-[12.5px] font-semibold text-[#625D6D]">
                                    Upcoming Bookings
                                </span>
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[9px] bg-[#F1EDFB] text-[#5942B5]">
                                    <CalendarCheck class="h-4 w-4" aria-hidden="true" />
                                </span>
                            </div>
                            <p class="mt-2.5 text-[29px] leading-none font-extrabold tracking-tight text-[#282338] tabular-nums">
                                {{ stats.upcomingBookingsCount }}
                            </p>
                            <p
                                :class="
                                    stats.upcomingBookingsCount > 0 || stats.pendingPaymentsCount > 0
                                        ? 'mt-2 text-[12px] font-semibold text-[#5942B5] tabular-nums'
                                        : 'mt-2 text-[12px] text-[#625D6D] tabular-nums'
                                "
                            >
                                {{ nextSessionCaption }}
                            </p>
                        </Card>
                    </Link>

                    <Link
                        :href="`${routes.portalBookings}?status=confirmed`"
                        class="block min-w-0 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    >
                        <Card class="flex h-full flex-col justify-between p-3.5 transition-colors duration-150 hover:border-[#B9ADE9] sm:p-4">
                            <div class="flex items-center justify-between gap-2.5">
                                <span class="text-[12.5px] font-semibold text-[#625D6D]">
                                    Completed Sessions
                                </span>
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[9px] bg-[#F1EDFB] text-[#5942B5]">
                                    <Play class="h-4 w-4" aria-hidden="true" />
                                </span>
                            </div>
                            <p class="mt-2.5 text-[29px] leading-none font-extrabold tracking-tight text-[#282338] tabular-nums">
                                {{ stats.completedSessionsCount }}
                            </p>
                            <p
                                :class="
                                    stats.completedSessionsCount > 0
                                        ? 'mt-2 text-[12px] font-semibold text-[#5942B5]'
                                        : 'mt-2 text-[12px] text-[#625D6D]'
                                "
                            >
                                {{
                                    stats.completedSessionsCount > 0
                                        ? 'In-person attendance recorded'
                                        : 'No completed workshops yet'
                                }}
                            </p>
                        </Card>
                    </Link>

                    <Link
                        :href="routes.portalCertificates"
                        class="block min-w-0 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    >
                        <Card class="flex h-full flex-col justify-between p-3.5 transition-colors duration-150 hover:border-[#B9ADE9] sm:p-4">
                            <div class="flex items-center justify-between gap-2.5">
                                <span class="text-[12.5px] font-semibold text-[#625D6D]">
                                    Certificates Earned
                                </span>
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[9px] bg-[#F1EDFB] text-[#5942B5]">
                                    <Award class="h-4 w-4" aria-hidden="true" />
                                </span>
                            </div>
                            <p class="mt-2.5 text-[29px] leading-none font-extrabold tracking-tight text-[#282338] tabular-nums">
                                {{ stats.certificatesCount }}
                            </p>
                            <p
                                :class="
                                    stats.certificatesCount > 0
                                        ? 'mt-2 text-[12px] font-semibold text-[#5942B5]'
                                        : 'mt-2 text-[12px] text-[#625D6D]'
                                "
                            >
                                {{
                                    stats.certificatesCount > 0
                                        ? 'Ready for download'
                                        : 'Earned after verification'
                                }}
                            </p>
                        </Card>
                    </Link>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-3.5 lg:grid-cols-12" aria-label="Learning Analytics">
                <div class="min-w-0 lg:col-span-8">
                    <PortalActivityChart :series="monthlyActivity" />
                </div>

                <div class="min-w-0 lg:col-span-4">
                    <PortalPortfolioChart :items="portfolioMix" />
                </div>
            </section>

            <section class="grid grid-cols-1 gap-3.5 lg:grid-cols-12" aria-label="Upcoming Training and Certificates">
                <Card class="flex min-w-0 flex-col lg:col-span-7">
                    <CardHeader class="flex flex-row items-center justify-between gap-4 space-y-0 pb-3">
                        <CardTitle>Upcoming Training</CardTitle>
                        <Link
                            :href="routes.portalBookings"
                            class="inline-flex items-center gap-1 text-[12.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
                        >
                            <span>All bookings</span>
                            <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                        </Link>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col pt-0">
                        <div v-if="upcomingBookings.length > 0" class="divide-y divide-[#F0EDF4]">
                            <div
                                v-for="booking in upcomingBookings"
                                :key="booking.id"
                                class="flex flex-col gap-3.5 py-3.5 first:pt-1 last:pb-1 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="flex min-w-0 items-start gap-3.5">
                                    <div
                                        class="flex w-[51px] shrink-0 flex-col items-center justify-center rounded-[9px] bg-[#F1EDFB] py-2 text-center text-[#5942B5]"
                                    >
                                        <span class="text-[10px] font-extrabold tracking-wider">
                                            {{ booking.monthShort }}
                                        </span>
                                        <span class="mt-0.5 text-[21px] leading-none font-extrabold tabular-nums">
                                            {{ booking.dayNumber }}
                                        </span>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <PortalStatusBadge
                                                :status="booking.statusValue"
                                                :label="booking.statusLabel"
                                            />
                                            <span class="text-[12px] text-[#625D6D] tabular-nums">
                                                {{ booking.bookingReference }}
                                            </span>
                                        </div>

                                        <h3 class="mt-1.5 text-[14px] font-bold leading-snug text-[#282338]">
                                            <Link
                                                :href="booking.detailUrl"
                                                class="hover:text-[#5942B5] hover:underline underline-offset-4"
                                            >
                                                {{ booking.sessionTitle }}
                                            </Link>
                                        </h3>

                                        <p class="mt-1.5 flex items-center gap-1.5 text-[12px] text-[#625D6D] tabular-nums">
                                            <Clock class="h-3.5 w-3.5 shrink-0 text-[#5942B5]" aria-hidden="true" />
                                            <span>{{ booking.dateRangeLabel }} &middot; {{ booking.timeRangeLabel }}</span>
                                        </p>

                                        <p class="mt-1 flex items-start gap-1.5 text-[12px] text-[#625D6D]">
                                            <MapPin class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#5942B5]" aria-hidden="true" />
                                            <span>{{ booking.venueFull }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="w-full shrink-0 sm:w-auto sm:pt-0.5">
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                        class="w-full whitespace-nowrap sm:w-auto"
                                    >
                                        <Link :href="booking.detailUrl">
                                            View
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-1 flex-col items-center justify-center rounded-[10px] border border-dashed border-[#D7D1E3] bg-[#FBFAFD] px-5 py-8 text-center"
                        >
                            <p class="text-[14px] font-bold text-[#282338]">
                                No upcoming training sessions
                            </p>
                            <p class="mt-1 max-w-sm text-[12.5px] text-[#625D6D]">
                                Confirmed physical sessions on your schedule will appear here.
                            </p>
                            <Button as-child variant="outline" size="sm" class="mt-4">
                                <Link :href="routes.sessions">Browse Sessions</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card class="flex min-w-0 flex-col lg:col-span-5">
                    <CardHeader class="flex flex-row items-center justify-between gap-4 space-y-0 pb-3">
                        <CardTitle>My Certificates</CardTitle>
                        <Link
                            :href="routes.portalCertificates"
                            class="inline-flex items-center gap-1 text-[12.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
                        >
                            <span>View all</span>
                            <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                        </Link>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col pt-0">
                        <div v-if="certificates.length > 0" class="divide-y divide-[#F0EDF4]">
                            <div
                                v-for="certificate in certificates"
                                :key="certificate.id"
                                class="space-y-3 py-3.5 first:pt-1 last:pb-1"
                            >
                                <div>
                                    <h3 class="line-clamp-2 text-[14px] font-bold leading-snug text-[#282338]">
                                        <Link
                                            :href="certificate.viewUrl"
                                            class="hover:text-[#5942B5] hover:underline underline-offset-4"
                                        >
                                            {{ certificate.title }}
                                        </Link>
                                    </h3>
                                    <p class="mt-1 text-[12px] text-[#625D6D] tabular-nums">
                                        {{ certificate.certificateNumber }} &middot; Issued {{ certificate.issuedDateLabel }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        size="sm"
                                    >
                                        <Link :href="certificate.viewUrl">
                                            View certificate
                                        </Link>
                                    </Button>
                                    <Button
                                        as="a"
                                        :href="certificate.downloadUrl"
                                        size="sm"
                                    >
                                        <Download class="h-3.5 w-3.5" aria-hidden="true" />
                                        <span>Download PDF</span>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-1 flex-col items-center justify-center rounded-[10px] border border-dashed border-[#D7D1E3] bg-[#FBFAFD] px-5 py-8 text-center"
                        >
                            <p class="text-[14px] font-bold text-[#282338]">
                                No certificates issued yet
                            </p>
                            <p class="mt-1 max-w-xs text-[12.5px] text-[#625D6D]">
                                Completed workshops and verified modules earn verifiable PDF certificates here.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="grid grid-cols-1 gap-3.5 lg:grid-cols-12" aria-label="Calendar and Recent Activity">
                <div class="min-w-0 lg:col-span-4 xl:col-span-3">
                    <PortalCalendar
                        :events="calendarEvents"
                        :initial-date="calendarInitialDate"
                    />
                </div>

                <Card class="flex min-w-0 flex-col overflow-hidden lg:col-span-8 xl:col-span-9" aria-labelledby="recent-activity-heading">
                    <CardHeader class="space-y-3 pb-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <CardTitle id="recent-activity-heading">
                                Recent Activity
                            </CardTitle>

                            <Link
                                :href="routes.portalOrders"
                                class="inline-flex items-center gap-1 text-[12.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
                            >
                                <span>Orders &amp; Payments</span>
                                <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                            </Link>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <Tabs
                                v-if="recentActivity.length > 0"
                                :model-value="activityFilter"
                                @update:model-value="setActivityFilter"
                            >
                                <TabsList aria-label="Filter recent activity">
                                    <TabsTrigger
                                        v-for="tab in activityTabs"
                                        :key="tab.key"
                                        :value="tab.key"
                                    >
                                        {{ tab.label }}
                                    </TabsTrigger>
                                </TabsList>
                            </Tabs>

                            <span
                                v-if="activitySearch.trim().length > 0"
                                class="inline-flex items-center gap-1.5 rounded-full bg-[#F1EDFB] px-2.5 py-1 text-[12px] font-semibold text-[#5942B5]"
                            >
                                <span>Filter: &ldquo;{{ activitySearch.trim() }}&rdquo;</span>
                                <button
                                    type="button"
                                    aria-label="Clear activity filter"
                                    class="rounded-full p-0.5 hover:bg-[#EAE5F8]"
                                    @click="activitySearch = ''"
                                >
                                    <X class="h-3 w-3" aria-hidden="true" />
                                </button>
                            </span>
                        </div>
                    </CardHeader>

                    <template v-if="filteredActivity.length > 0">
                        <div class="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow class="hover:bg-transparent">
                                        <TableHead class="pl-6">Date</TableHead>
                                        <TableHead>Record</TableHead>
                                        <TableHead>Detail</TableHead>
                                        <TableHead class="text-right tabular-nums">Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="pr-6 text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in filteredActivity" :key="item.id">
                                        <TableCell class="whitespace-nowrap pl-6 text-[12.5px] text-[#625D6D] tabular-nums">
                                            {{ item.shortDateLabel }}
                                        </TableCell>
                                        <TableCell class="max-w-[280px]">
                                            <p class="text-[13px] font-bold leading-snug text-[#292536]">
                                                {{ item.title }}
                                            </p>
                                            <p class="mt-0.5 text-[11.5px] text-[#625D6D] tabular-nums">
                                                {{ item.kindLabel }} &middot; {{ item.reference }}
                                            </p>
                                        </TableCell>
                                        <TableCell class="text-[12.5px] text-[#403B4B]">
                                            {{ item.detail }}
                                        </TableCell>
                                        <TableCell class="whitespace-nowrap text-right text-[13px] font-bold text-[#282338] tabular-nums">
                                            {{ item.amountLabel === '—' ? '—' : formatDisplayFee(item.amountLabel) }}
                                        </TableCell>
                                        <TableCell class="whitespace-nowrap">
                                            <PortalStatusBadge
                                                :status="item.statusValue"
                                                :label="item.statusLabel"
                                            />
                                        </TableCell>
                                        <TableCell class="whitespace-nowrap pr-6 text-right">
                                            <Button
                                                as-child
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Link :href="item.actionUrl">
                                                    View
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div class="divide-y divide-[#EEEAF2] border-t border-[#EEEAF2] md:hidden">
                            <div
                                v-for="item in filteredActivity"
                                :key="item.id"
                                class="space-y-2.5 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[13.5px] font-bold leading-snug text-[#292536]">
                                            {{ item.title }}
                                        </p>
                                        <p class="mt-0.5 text-[12px] text-[#625D6D] tabular-nums">
                                            {{ item.kindLabel }} &middot; {{ item.reference }}
                                        </p>
                                    </div>
                                    <PortalStatusBadge
                                        :status="item.statusValue"
                                        :label="item.statusLabel"
                                    />
                                </div>

                                <div class="flex items-center justify-between gap-3 text-[12.5px] text-[#403B4B]">
                                    <span>{{ item.detail }} &middot; {{ item.shortDateLabel }}</span>
                                    <span class="font-bold text-[#282338] tabular-nums">
                                        {{ item.amountLabel === '—' ? '—' : formatDisplayFee(item.amountLabel) }}
                                    </span>
                                </div>

                                <div class="pt-1">
                                    <Button as-child variant="outline" size="sm" class="w-full">
                                        <Link :href="item.actionUrl">
                                            View Record
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div v-else class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                        <p class="text-[14px] font-bold text-[#282338]">
                            {{ recentActivity.length > 0 ? 'No matching activity records' : 'No recent activity' }}
                        </p>
                        <p class="mt-1 text-[12.5px] text-[#625D6D]">
                            {{
                                recentActivity.length > 0
                                    ? 'Switch filter tabs or clear your search term to see all bookings and orders.'
                                    : 'Session bookings and module orders will appear here.'
                            }}
                        </p>
                        <Button
                            v-if="recentActivity.length > 0"
                            type="button"
                            variant="outline"
                            size="sm"
                            class="mt-3"
                            @click="clearActivityFilters"
                        >
                            Reset filters
                        </Button>
                    </div>
                </Card>
            </section>
        </div>
    </PortalLayout>
</template>
