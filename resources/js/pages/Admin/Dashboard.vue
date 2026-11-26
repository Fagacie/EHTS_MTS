<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    DoughnutController,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
    type ChartData,
    type ChartOptions,
} from 'chart.js';
import {
    ArrowUpRight,
    Award,
    BarChart3,
    BookOpen,
    CalendarDays,
    CreditCard,
    Plus,
    RotateCcw,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import AdminCalendarCard, { type AdminCalendarSession } from '@/components/ds/AdminCalendarCard.vue';
import KpiCard from '@/components/ds/KpiCard.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

ChartJS.register(
    ArcElement,
    BarElement,
    BarController,
    CategoryScale,
    DoughnutController,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
    Filler,
);

interface Report {
    customers: { total: number; active: number; new_in_period: number };
    catalogue: { published_sessions: number; completed_sessions: number; published_modules: number };
    bookings: { confirmed: number; unique_participants: number; pending: number; cancelled: number; refunded: number };
    revenue: { gross_myr: number; net_myr: number; successful_payments: number; pending_payments: number };
    refunds: { pending_count: number };
    certificates: { issued: number };
}

interface DashboardProps extends EtmsSharedProps {
    report: Report;
    calendarSessions: AdminCalendarSession[];
    calendarInitialDate?: string;
    capacitySessions: {
        id: number;
        title: string;
        registered: number;
        capacity: number;
        percent: number;
        status: string;
        detailUrl: string;
    }[];
    pendingActions: {
        attendanceReviews: number;
        pendingRefunds: number;
        pendingPayments: number;
        activeSeatHolds: number;
    };
    filters: { from: string | null; to: string | null };
    revenueTrend: { labels: string[]; revenue: number[]; refunds: number[] };
    sessionPerformance: { upcoming: number; completed: number; cancelled: number };
    bookingDistribution: { label: string; value: number }[];
}

const props = defineProps<DashboardProps>();
const filters = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const hasDateFilters = computed(() => Boolean(filters.from || filters.to));

function applyFilters(): void {
    router.get(
        '/admin-vue',
        { from: filters.from || undefined, to: filters.to || undefined },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    filters.from = '';
    filters.to = '';
    applyFilters();
}

function occupancyBarClass(percent: number): string {
    if (percent >= 90) return 'bg-warm-500';
    if (percent >= 70) return 'bg-primary-600';
    return 'bg-accent-600';
}

const actionQueue = computed(() => [
    {
        label: 'Attendance reviews',
        count: props.pendingActions.attendanceReviews,
        href: '/admin-vue/sessions',
    },
    {
        label: 'Refunds pending',
        count: props.pendingActions.pendingRefunds,
        href: '/admin-vue/refunds',
    },
    {
        label: 'Pending payments',
        count: props.pendingActions.pendingPayments,
        href: '/admin-vue/payments',
    },
    {
        label: 'Active seat holds',
        count: props.pendingActions.activeSeatHolds,
        href: '/admin-vue/sessions',
    },
]);

const platformSnapshot = computed(() => [
    {
        label: 'Gross revenue',
        value: `MYR ${props.report.revenue.gross_myr.toLocaleString('en-MY', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`,
    },
    {
        label: 'Published modules',
        value: props.report.catalogue.published_modules.toLocaleString(),
    },
    {
        label: 'Certificates issued',
        value: props.report.certificates.issued.toLocaleString(),
    },
    {
        label: 'New customers',
        value: props.report.customers.new_in_period.toLocaleString(),
    },
]);

const quickOperations = [
    { label: 'Schedule session', href: '/admin-vue/sessions/create', icon: Plus },
    { label: 'Add module', href: '/admin-vue/modules/create', icon: BookOpen },
    { label: 'Certificates', href: '/admin-vue/certificates', icon: Award },
    { label: 'Export reports', href: '/admin-vue/reports', icon: BarChart3 },
];

const fontFamily = '"Inter Variable", Inter, ui-sans-serif, system-ui, sans-serif';
const baseTooltip = {
    backgroundColor: '#0d1829',
    titleColor: '#ffffff',
    bodyColor: '#ffffff',
    titleFont: { family: fontFamily, size: 12, weight: 600 as const },
    bodyFont: { family: fontFamily, size: 12 },
    padding: 10,
    cornerRadius: 8,
};

const revenueData = computed<ChartData<'line'>>(() => ({
    labels: props.revenueTrend.labels,
    datasets: [
        {
            label: 'Net revenue',
            data: props.revenueTrend.revenue,
            borderColor: '#2e5fd0',
            backgroundColor: 'rgba(46, 95, 208, 0.08)',
            borderWidth: 2,
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#2e5fd0',
        },
        {
            label: 'Refunds',
            data: props.revenueTrend.refunds,
            borderColor: '#e86a33',
            backgroundColor: 'transparent',
            borderWidth: 2,
            borderDash: [5, 5],
            tension: 0.35,
            pointRadius: 2,
            pointBackgroundColor: '#e86a33',
        },
    ],
}));

const revenueOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 200 },
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                usePointStyle: true,
                boxWidth: 7,
                color: '#5c687a',
                font: { family: fontFamily, size: 11 },
            },
        },
        tooltip: {
            ...baseTooltip,
            callbacks: {
                label: (ctx) =>
                    ` ${ctx.dataset.label}: MYR ${Number(ctx.parsed.y).toLocaleString('en-MY', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    })}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#5c687a', font: { family: fontFamily, size: 10 } },
        },
        y: {
            beginAtZero: true,
            grid: { color: '#eae7e0' },
            ticks: {
                color: '#5c687a',
                font: { family: fontFamily, size: 10 },
                callback: (value) => `RM ${Number(value).toLocaleString('en-MY')}`,
            },
        },
    },
};

const sessionData = computed<ChartData<'bar'>>(() => ({
    labels: ['Upcoming', 'Completed', 'Cancelled'],
    datasets: [
        {
            label: 'Sessions',
            data: [
                props.sessionPerformance.upcoming,
                props.sessionPerformance.completed,
                props.sessionPerformance.cancelled,
            ],
            backgroundColor: ['#2e5fd0', '#159b9a', '#cbd5e1'],
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.55,
            categoryPercentage: 0.7,
        },
    ],
}));

const sessionOptions: ChartOptions<'bar'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: baseTooltip },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#5c687a', font: { family: fontFamily, size: 11 } },
        },
        y: {
            beginAtZero: true,
            grid: { color: '#eae7e0' },
            ticks: { precision: 0, color: '#5c687a', font: { family: fontFamily, size: 11 } },
        },
    },
};

const doughnutPalette = ['#0d1829', '#2e5fd0', '#159b9a', '#e86a33'];

const bookingData = computed<ChartData<'doughnut'>>(() => ({
    labels: props.bookingDistribution.map((item) => item.label),
    datasets: [
        {
            data: props.bookingDistribution.map((item) => item.value),
            backgroundColor: doughnutPalette,
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: 3,
        },
    ],
}));

const bookingTotal = computed(() =>
    props.bookingDistribution.reduce((sum, item) => sum + item.value, 0),
);

const bookingOptions: ChartOptions<'doughnut'> = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: { legend: { display: false }, tooltip: baseTooltip },
};

const netRevenueFormatted = computed(
    () =>
        `MYR ${props.report.revenue.net_myr.toLocaleString('en-MY', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`,
);
</script>

<template>
    <Head title="Administrator Dashboard" />

    <AdminLayout breadcrumb="Dashboard">
        <div class="space-y-6">
            <!-- Header + Date Range Filter -->
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="type-page-title">Operations overview</h1>
                    <p class="mt-1 text-sm text-muted">
                        Training schedules, enrolments, revenue, and pending administrative actions.
                    </p>
                </div>

                <form class="flex flex-wrap items-center gap-2" @submit.prevent="applyFilters">
                    <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-1.5 ring-1 ring-line">
                        <label class="flex items-center gap-1.5 text-xs font-medium text-muted">
                            <span>From</span>
                            <input
                                v-model="filters.from"
                                type="date"
                                class="tabular border-0 bg-transparent text-xs font-semibold text-navy-950 focus:outline-none"
                            />
                        </label>
                        <span class="text-line-strong">/</span>
                        <label class="flex items-center gap-1.5 text-xs font-medium text-muted">
                            <span>To</span>
                            <input
                                v-model="filters.to"
                                type="date"
                                class="tabular border-0 bg-transparent text-xs font-semibold text-navy-950 focus:outline-none"
                            />
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900"
                    >
                        Apply
                    </button>

                    <button
                        v-if="hasDateFilters"
                        type="button"
                        class="inline-flex h-9 items-center gap-1 rounded-lg bg-white px-3 text-xs font-semibold text-muted ring-1 ring-line transition-colors hover:text-navy-950"
                        @click="clearFilters"
                    >
                        <RotateCcw class="size-3.5" aria-hidden="true" />
                        <span>Clear</span>
                    </button>
                </form>
            </header>

            <!-- 4-Column KPI Grid -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key metrics">
                <Link href="/admin-vue/users" class="block focus-visible:outline-none">
                    <KpiCard
                        label="Registered customers"
                        :value="report.customers.total.toLocaleString()"
                        :caption="`${report.customers.active} active accounts`"
                        :icon="Users"
                    />
                </Link>

                <Link href="/admin-vue/sessions" class="block focus-visible:outline-none">
                    <KpiCard
                        label="Upcoming sessions"
                        :value="report.catalogue.published_sessions.toLocaleString()"
                        :caption="`${report.catalogue.completed_sessions} completed`"
                        :icon="CalendarDays"
                    />
                </Link>

                <Link href="/admin-vue/sessions" class="block focus-visible:outline-none">
                    <KpiCard
                        label="Confirmed bookings"
                        :value="report.bookings.confirmed.toLocaleString()"
                        :caption="`${report.bookings.unique_participants} unique participants`"
                        :icon="UserCheck"
                    />
                </Link>

                <Link href="/admin-vue/payments" class="block focus-visible:outline-none">
                    <KpiCard
                        label="Net revenue"
                        :value="netRevenueFormatted"
                        :caption="`${report.revenue.successful_payments} successful payments`"
                        :icon="CreditCard"
                        variant="dark"
                    />
                </Link>
            </section>

            <!-- Action Queue Strip -->
            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Actions requiring review">
                <Link
                    v-for="item in actionQueue"
                    :key="item.label"
                    :href="item.href"
                    class="group flex items-center justify-between rounded-xl bg-white px-4 py-3 ring-1 ring-neutral-200/80 transition-all hover:ring-primary-600/35"
                >
                    <span class="text-xs font-medium text-ink group-hover:text-navy-950">
                        {{ item.label }}
                    </span>
                    <span
                        class="tabular inline-flex min-w-7 items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold"
                        :class="
                            item.count > 0
                                ? 'bg-warning-50 text-warning-700 ring-1 ring-inset ring-warning-600/20'
                                : 'bg-sand-100 text-muted'
                        "
                    >
                        {{ item.count }}
                    </span>
                </Link>
            </section>

            <!-- Interactive Operations Calendar + Platform Snapshot & Quick Actions -->
            <section class="grid gap-5 lg:grid-cols-12" aria-label="Schedule calendar and quick operations">
                <div class="lg:col-span-8">
                    <AdminCalendarCard
                        :sessions="calendarSessions"
                        :initial-date="calendarInitialDate"
                    />
                </div>

                <div class="flex flex-col justify-between rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80 lg:col-span-4">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-navy-950">Platform snapshot</h2>
                            <Link
                                href="/admin-vue/reports"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700"
                            >
                                <span>Reports</span>
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            </Link>
                        </div>

                        <dl class="mt-4 divide-y divide-line/70">
                            <div
                                v-for="metric in platformSnapshot"
                                :key="metric.label"
                                class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <dt class="text-xs text-muted">{{ metric.label }}</dt>
                                <dd class="tabular text-sm font-bold text-navy-950">{{ metric.value }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-6 border-t border-line pt-5">
                        <p class="text-xs font-semibold text-navy-950">Quick operations</p>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <Link
                                v-for="op in quickOperations"
                                :key="op.label"
                                :href="op.href"
                                class="group flex items-center gap-2 rounded-xl bg-sand-50/90 px-3 py-2.5 text-xs font-semibold text-navy-950 ring-1 ring-line/70 transition-colors hover:bg-navy-950 hover:text-white hover:ring-navy-950"
                            >
                                <component :is="op.icon" class="size-3.5 shrink-0" aria-hidden="true" />
                                <span class="truncate">{{ op.label }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Analytics Row: Revenue Trend + Booking Distribution -->
            <section class="grid gap-5 lg:grid-cols-12" aria-label="Revenue and booking analytics">
                <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80 lg:col-span-8">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-navy-950">Revenue &amp; refunds</h2>
                            <p class="mt-0.5 text-xs text-muted">Net collections vs refunded amounts</p>
                        </div>
                        <Link
                            href="/admin-vue/payments"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700"
                        >
                            <span>Payments</span>
                            <ArrowUpRight class="size-3.5" aria-hidden="true" />
                        </Link>
                    </div>
                    <div class="h-72">
                        <Line :data="revenueData" :options="revenueOptions" />
                    </div>
                </div>

                <div class="flex flex-col justify-between rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80 lg:col-span-4">
                    <div>
                        <h2 class="text-sm font-semibold text-navy-950">Booking distribution</h2>
                        <p class="mt-0.5 text-xs text-muted">Breakdown by reservation status</p>
                    </div>

                    <div class="relative mx-auto my-4 h-44 w-44">
                        <Doughnut :data="bookingData" :options="bookingOptions" />
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="tabular text-2xl font-extrabold text-navy-950">{{ bookingTotal }}</span>
                            <span class="text-xs text-muted">Bookings</span>
                        </div>
                    </div>

                    <ul class="grid grid-cols-2 gap-x-4 gap-y-2 border-t border-line pt-4 text-xs">
                        <li
                            v-for="(item, index) in bookingDistribution"
                            :key="item.label"
                            class="flex items-center gap-2 text-ink"
                        >
                            <span
                                class="size-2.5 shrink-0 rounded-full"
                                :style="{ backgroundColor: doughnutPalette[index % doughnutPalette.length] }"
                            />
                            <span class="truncate text-muted">{{ item.label }}</span>
                            <strong class="tabular ml-auto font-semibold text-navy-950">{{ item.value }}</strong>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Operations Row: Session Performance + Capacity -->
            <section class="grid gap-5 lg:grid-cols-12" aria-label="Session operations">
                <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80 lg:col-span-5">
                    <h2 class="text-sm font-semibold text-navy-950">Session performance</h2>
                    <p class="mt-0.5 text-xs text-muted">Status count across scheduled sessions</p>
                    <div class="mt-4 h-60">
                        <Bar :data="sessionData" :options="sessionOptions" />
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80 lg:col-span-7">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-navy-950">Session capacity</h2>
                            <p class="mt-0.5 text-xs text-muted">Live seat occupancy across upcoming sessions</p>
                        </div>
                        <Link
                            href="/admin-vue/sessions"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700"
                        >
                            <span>All sessions</span>
                            <ArrowUpRight class="size-3.5" aria-hidden="true" />
                        </Link>
                    </div>

                    <div v-if="capacitySessions.length > 0" class="mt-5 space-y-4">
                        <Link
                            v-for="session in capacitySessions"
                            :key="session.id"
                            :href="session.detailUrl"
                            class="group block"
                        >
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <p class="truncate font-semibold text-navy-950 transition-colors group-hover:text-primary-600">
                                    {{ session.title }}
                                </p>
                                <span class="tabular shrink-0 text-xs font-semibold text-muted">
                                    {{ session.registered }} / {{ session.capacity }} ({{ session.percent }}%)
                                </span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-sand-100">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="occupancyBarClass(session.percent)"
                                    :style="{ width: `${Math.min(session.percent, 100)}%` }"
                                />
                            </div>
                        </Link>
                    </div>

                    <p v-else class="py-12 text-center text-sm text-muted">
                        No upcoming sessions are approaching capacity.
                    </p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
