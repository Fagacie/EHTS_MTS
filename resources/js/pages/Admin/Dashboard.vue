<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    BarController,
    CategoryScale,
    Chart as ChartJS,
    DoughnutController,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
    Filler,
    type ChartData,
    type ChartOptions,
} from 'chart.js';
import {
    AlertCircle,
    ArrowRight,
    CalendarDays,
    CreditCard,
    ReceiptText,
    Users,
    UserCheck,
} from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { EtmsSharedProps } from '@/types/etms';

ChartJS.register(ArcElement, BarElement, BarController, CategoryScale, DoughnutController, LinearScale, LineController, LineElement, PointElement, Tooltip, Filler);

interface Report {
    customers: { total: number; active: number; new_in_period: number };
    catalogue: { published_sessions: number; completed_sessions: number; published_modules: number };
    bookings: { confirmed: number; unique_participants: number; pending: number; cancelled: number; refunded: number };
    revenue: { gross_myr: number; net_myr: number; successful_payments: number; pending_payments: number };
    refunds: { pending_count: number };
    certificates: { issued: number };
}

interface SessionItem {
    id: number;
    title: string;
    sessionCode: string;
    startsAt: string | null;
    venue: string;
    instructors: string[];
    confirmedBookings: number;
    capacity: number;
    status: string;
    occupancyPercent: number;
    detailUrl: string;
}

interface DashboardProps extends EtmsSharedProps {
    report: Report;
    upcomingSessions: SessionItem[];
    capacitySessions: { id: number; title: string; registered: number; capacity: number; percent: number; status: string; detailUrl: string }[];
    recentActivity: { event: string; title: string; actor: string; createdAt: string | null; reason: string | null }[];
    pendingActions: { attendanceReviews: number; pendingRefunds: number; pendingPayments: number; activeSeatHolds: number };
    filters: { from: string | null; to: string | null };
    revenueTrend: { labels: string[]; revenue: number[]; refunds: number[] };
    sessionPerformance: { upcoming: number; completed: number; cancelled: number };
    bookingDistribution: { label: string; value: number }[];
}

const props = defineProps<DashboardProps>();
const filters = reactive({ from: props.filters.from ?? '', to: props.filters.to ?? '' });

const stats = computed(() => [
    { label: 'Registered customers', value: props.report.customers.total, detail: `${props.report.customers.active} active`, icon: Users, route: '/admin-vue/users' },
    { label: 'Upcoming sessions', value: props.report.catalogue.published_sessions, detail: `${props.report.catalogue.completed_sessions} completed`, icon: CalendarDays, route: '/admin-vue/sessions' },
    { label: 'Confirmed bookings', value: props.report.bookings.confirmed, detail: `${props.report.bookings.unique_participants} unique participants`, icon: UserCheck, route: '/admin-vue/sessions' },
    { label: 'Net revenue', value: props.report.revenue.net_myr, detail: `${props.report.revenue.successful_payments} successful payments`, icon: CreditCard, route: '/admin-vue/payments', currency: true },
]);

function applyFilters(): void {
    router.get('/admin-vue', { from: filters.from || undefined, to: filters.to || undefined }, { preserveState: true, replace: true });
}

function clearFilters(): void {
    filters.from = '';
    filters.to = '';
    applyFilters();
}

function formatDate(value: string | null): string {
    if (!value) return 'Date unavailable';
    return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short', timeZone: props.app.displayTimezone }).format(new Date(value));
}

function formatActivityDate(value: string | null): string {
    if (!value) return 'Unknown time';
    return new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short', timeZone: props.app.displayTimezone }).format(new Date(value));
}

const fontFamily = '"Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, sans-serif';
const baseTooltip = {
    backgroundColor: '#292536',
    titleColor: '#FFFFFF',
    bodyColor: '#FFFFFF',
    titleFont: { family: fontFamily, size: 12, weight: 700 as const },
    bodyFont: { family: fontFamily, size: 12 },
    padding: 10,
    cornerRadius: 8,
};

const revenueData = computed<ChartData<'line'>>(() => ({
    labels: props.revenueTrend.labels,
    datasets: [
        { label: 'Net revenue', data: props.revenueTrend.revenue, borderColor: '#7057D9', backgroundColor: 'rgba(112,87,217,.10)', fill: true, tension: 0.35, pointRadius: 3, pointHoverRadius: 5 },
        { label: 'Refunds', data: props.revenueTrend.refunds, borderColor: '#A33C48', backgroundColor: 'transparent', borderDash: [5, 5], tension: 0.35, pointRadius: 2 },
    ],
}));

const revenueOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 200 },
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, color: '#625D6D', font: { family: fontFamily, size: 11 } } },
        tooltip: { ...baseTooltip, callbacks: { label: (ctx) => ` ${ctx.dataset.label}: RM ${Number(ctx.parsed.y).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` } },
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: '#625D6D', font: { family: fontFamily, size: 10 } } },
        y: { beginAtZero: true, grid: { color: '#EEEAF2' }, ticks: { color: '#625D6D', font: { family: fontFamily, size: 10 }, callback: (value) => `RM ${Number(value).toLocaleString('en-MY')}` } },
    },
};

const sessionData = computed<ChartData<'bar'>>(() => ({
    labels: ['Upcoming', 'Completed', 'Cancelled'],
    datasets: [{ label: 'Sessions', data: [props.sessionPerformance.upcoming, props.sessionPerformance.completed, props.sessionPerformance.cancelled], backgroundColor: ['#7057D9', '#BEB0EE', '#DDD6F1'], borderRadius: 6, borderSkipped: false, barPercentage: 0.58, categoryPercentage: 0.72 }],
}));

const sessionOptions: ChartOptions<'bar'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: baseTooltip },
    scales: { x: { grid: { display: false }, ticks: { color: '#625D6D', font: { family: fontFamily, size: 11 } } }, y: { beginAtZero: true, grid: { color: '#EEEAF2' }, ticks: { precision: 0, color: '#625D6D' } } },
};

const bookingData = computed<ChartData<'doughnut'>>(() => ({
    labels: props.bookingDistribution.map((item) => item.label),
    datasets: [{ data: props.bookingDistribution.map((item) => item.value), backgroundColor: ['#7057D9', '#BEB0EE', '#DDD6F1', '#9B86E8'], borderWidth: 2, borderColor: '#FFFFFF', hoverOffset: 2 }],
}));

const bookingTotal = computed(() => props.bookingDistribution.reduce((sum, item) => sum + item.value, 0));
const bookingOptions: ChartOptions<'doughnut'> = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: { legend: { display: false }, tooltip: baseTooltip },
};
</script>

<template>
<Head title="Administrator Dashboard"/>
<AdminLayout>
    <div class="mx-auto max-w-[1440px] space-y-4">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#292536] sm:text-[26px]">Administrator dashboard</h1>
                <p class="mt-1 text-[13.5px] text-[#625D6D]">A current view of training operations, finance and actions requiring review.</p>
            </div>
            <form class="flex flex-wrap items-end gap-2" @submit.prevent="applyFilters">
                <label class="text-[12px] font-semibold text-[#625D6D]">From<input v-model="filters.from" type="date" class="mt-1 h-9 rounded-[8px] border border-[#E5E1EB] bg-white px-3 text-[12px] text-[#292536]"/></label>
                <label class="text-[12px] font-semibold text-[#625D6D]">To<input v-model="filters.to" type="date" class="mt-1 h-9 rounded-[8px] border border-[#E5E1EB] bg-white px-3 text-[12px] text-[#292536]"/></label>
                <Button type="submit" size="sm">Apply</Button>
                <Button type="button" variant="outline" size="sm" @click="clearFilters">Clear</Button>
            </form>
        </header>

        <section class="grid grid-cols-2 gap-3 sm:gap-3.5 xl:grid-cols-4" aria-label="Key metrics">
            <Link v-for="stat in stats" :key="stat.label" :href="stat.route" class="block min-w-0 rounded-[12px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40">
                <Card class="flex h-full flex-col justify-between p-3.5 transition-colors duration-150 hover:border-[#B9ADE9] sm:p-4">
                    <div class="flex items-center justify-between gap-2.5"><span class="text-[12.5px] font-semibold text-[#625D6D]">{{stat.label}}</span><span class="grid h-8 w-8 shrink-0 place-items-center rounded-[9px] bg-[#F1EDFB] text-[#5942B5]"><component :is="stat.icon" class="h-4 w-4"/></span></div>
                    <p class="mt-2.5 text-[29px] leading-none font-extrabold tracking-tight text-[#292536] tabular-nums">{{stat.currency?'MYR ':''}}{{stat.value.toLocaleString(undefined,{minimumFractionDigits:stat.currency?2:0,maximumFractionDigits:stat.currency?2:0})}}</p>
                    <p class="mt-2 text-[12px] font-semibold text-[#5942B5]">{{stat.detail}}</p>
                </Card>
            </Link>
        </section>

        <section class="grid grid-cols-1 gap-3.5 lg:grid-cols-12" aria-label="Administrative analytics">
            <Card class="min-w-0 lg:col-span-8"><CardHeader class="pb-2"><CardTitle>Revenue and refunds</CardTitle></CardHeader><CardContent><div class="h-72"><Line :data="revenueData" :options="revenueOptions"/></div></CardContent></Card>
            <Card class="min-w-0 lg:col-span-4"><CardHeader class="pb-2"><CardTitle>Booking status</CardTitle></CardHeader><CardContent><div class="relative mx-auto h-44 w-44"><Doughnut :data="bookingData" :options="bookingOptions"/><div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"><span class="text-[23px] font-extrabold text-[#292536]">{{bookingTotal}}</span><span class="text-[11px] text-[#625D6D]">Bookings</span></div></div><ul class="mt-2 grid grid-cols-2 gap-2 text-[12px]"><li v-for="(item,index) in bookingDistribution" :key="item.label" class="flex items-center gap-2 text-[#403B4B]"><span class="h-2.5 w-2.5 rounded-full" :style="{backgroundColor:['#7057D9','#BEB0EE','#DDD6F1','#9B86E8'][index]}"/>{{item.label}} <strong class="ml-auto">{{item.value}}</strong></li></ul></CardContent></Card>
        </section>

        <section class="grid grid-cols-1 gap-3.5 lg:grid-cols-12" aria-label="Operations">
            <Card class="min-w-0 lg:col-span-5"><CardHeader class="pb-2"><CardTitle>Session performance</CardTitle></CardHeader><CardContent><div class="h-64"><Bar :data="sessionData" :options="sessionOptions"/></div></CardContent></Card>
            <Card class="min-w-0 lg:col-span-7"><CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3"><CardTitle>Session capacity</CardTitle><Link href="/admin-vue/sessions" class="inline-flex items-center gap-1 text-[12.5px] font-bold text-[#5942B5]">All sessions<ArrowRight class="h-3.5 w-3.5"/></Link></CardHeader><CardContent class="pt-0"><div v-if="capacitySessions.length" class="space-y-4"><Link v-for="session in capacitySessions" :key="session.id" :href="session.detailUrl" class="block rounded-[8px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"><div class="flex items-center justify-between gap-3"><p class="truncate text-[13px] font-bold text-[#292536]">{{session.title}}</p><p class="shrink-0 text-[12px] font-semibold text-[#625D6D]">{{session.registered}} / {{session.capacity}}</p></div><div class="mt-2 h-2 rounded-full bg-[#F1EDFB]"><div class="h-2 rounded-full bg-[#7057D9]" :style="{width:`${Math.min(session.percent,100)}%`}"/></div><p class="mt-1 text-[11.5px] text-[#625D6D]">{{session.status}} · {{session.percent}}% occupied</p></Link></div><p v-else class="py-8 text-center text-[13px] text-[#625D6D]">No upcoming sessions are approaching capacity.</p></CardContent></Card>
        </section>

        <section class="grid grid-cols-1 gap-3.5 lg:grid-cols-12" aria-label="Administration operations">
            <Card class="lg:col-span-7"><CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3"><CardTitle>Upcoming training sessions</CardTitle><Link href="/admin-vue/sessions" class="inline-flex items-center gap-1 text-[12.5px] font-bold text-[#5942B5]">Manage<ArrowRight class="h-3.5 w-3.5"/></Link></CardHeader><CardContent class="pt-0"><div v-if="upcomingSessions.length" class="divide-y divide-[#F0EDF4]"><Link v-for="session in upcomingSessions" :key="session.id" :href="session.detailUrl" class="flex flex-col gap-2 py-3.5 first:pt-1 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p class="truncate text-[13.5px] font-bold text-[#292536]">{{session.title}}</p><p class="mt-1 text-[12px] text-[#625D6D]">{{formatDate(session.startsAt)}} · {{session.venue||'Venue not set'}}</p><p class="mt-1 text-[12px] text-[#625D6D]">{{session.instructors.join(', ')||'Unassigned'}} · {{session.confirmedBookings}} / {{session.capacity}}</p></div><span class="shrink-0 rounded-full bg-[#F1EDFB] px-2.5 py-1 text-[11.5px] font-bold text-[#5942B5]">{{session.status}}</span></Link></div><p v-else class="py-8 text-center text-[13px] text-[#625D6D]">No published sessions are scheduled.</p></CardContent></Card>

            <Card class="lg:col-span-5"><CardHeader class="pb-3"><CardTitle>Pending actions</CardTitle></CardHeader><CardContent class="space-y-2 pt-0"><Link href="/admin-vue/sessions" class="flex items-center justify-between rounded-[8px] px-3 py-2.5 text-[13px] hover:bg-[#F3F1F7]"><span class="text-[#403B4B]">Attendance reviews</span><strong class="text-[#5942B5]">{{pendingActions.attendanceReviews}}</strong></Link><Link href="/admin-vue/refunds" class="flex items-center justify-between rounded-[8px] px-3 py-2.5 text-[13px] hover:bg-[#F3F1F7]"><span class="text-[#403B4B]">Refunds requiring attention</span><strong class="text-[#5942B5]">{{pendingActions.pendingRefunds}}</strong></Link><Link href="/admin-vue/payments" class="flex items-center justify-between rounded-[8px] px-3 py-2.5 text-[13px] hover:bg-[#F3F1F7]"><span class="text-[#403B4B]">Pending payments</span><strong class="text-[#5942B5]">{{pendingActions.pendingPayments}}</strong></Link><Link href="/admin-vue/sessions" class="flex items-center justify-between rounded-[8px] px-3 py-2.5 text-[13px] hover:bg-[#F3F1F7]"><span class="text-[#403B4B]">Active seat holds</span><strong class="text-[#5942B5]">{{pendingActions.activeSeatHolds}}</strong></Link></CardContent></Card>
        </section>

        <Card><CardHeader class="pb-3"><CardTitle>Recent administrative activity</CardTitle></CardHeader><CardContent class="pt-0"><div v-if="recentActivity.length" class="divide-y divide-[#F0EDF4]"><div v-for="activity in recentActivity" :key="`${activity.event}-${activity.createdAt}`" class="flex items-start gap-3 py-3 first:pt-1"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-[9px] bg-[#F1EDFB] text-[#5942B5]"><AlertCircle class="h-4 w-4"/></span><div class="min-w-0"><p class="text-[13px] font-bold text-[#292536]">{{activity.title}}</p><p class="mt-0.5 text-[12px] text-[#625D6D]">{{activity.actor}} · {{formatActivityDate(activity.createdAt)}}<span v-if="activity.reason"> · {{activity.reason}}</span></p></div></div></div><p v-else class="py-8 text-center text-[13px] text-[#625D6D]">No administrative activity has been recorded.</p></CardContent></Card>
    </div>
</AdminLayout>
</template>
