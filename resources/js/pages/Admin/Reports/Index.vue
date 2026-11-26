<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Award,
    CheckCircle2,
    ChevronDown,
    Download,
    FileDown,
    RotateCcw,
    TicketCheck,
    Wallet,
} from 'lucide-vue-next';
import {
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuPortal,
    DropdownMenuRoot,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from 'reka-ui';
import AdminLayout from '@/layouts/AdminLayout.vue';
import KpiCard from '@/components/ds/KpiCard.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface SessionParticipantRow {
    booking_id: number;
    booking_reference: string;
    trainee_name: string;
    trainee_email: string;
    confirmed_at: string;
    attendance_status: string;
    review_status: string;
    certificate_issued: boolean;
}

interface SessionReportRow {
    id: number;
    session_code: string;
    title: string;
    starts_at_label: string;
    venue: string;
    instructors: string;
    capacity: number;
    confirmed_seats: number;
    present_count: number;
    verified_count: number;
    certificates_count: number;
    status: string;
    participants: SessionParticipantRow[];
}

interface BookingReportRow {
    id: number;
    reference: string;
    trainee_name: string;
    trainee_email: string;
    session_code: string;
    session_title: string;
    status: string;
    reserved_at: string;
    confirmed_at: string;
}

interface AttendanceReportRow {
    id: number;
    session_id: number;
    session_code: string;
    session_title: string;
    trainee_name: string;
    trainee_email: string;
    attendance_status: string;
    review_status: string;
    recorded_at: string;
    verified_at: string;
    remarks: string;
}

interface PaymentReportRow {
    id: number;
    payment_reference: string;
    order_number: string;
    order_type: string;
    customer_name: string;
    customer_email: string;
    gateway: string;
    amount_myr: number;
    status: string;
    timestamp: string;
}

interface RefundReportRow {
    id: number;
    refund_reference: string;
    order_number: string;
    customer_name: string;
    customer_email: string;
    amount_myr: number;
    method: string;
    status: string;
    reason: string;
    requested_at: string;
    processed_at: string;
}

interface CertificateReportRow {
    id: number;
    certificate_number: string;
    trainee_name: string;
    record_type: string;
    training_code: string;
    training_title: string;
    completion_date: string;
    issued_at: string;
    status: string;
}

interface PopularModuleRow {
    id: number;
    code: string;
    title: string;
    category: string;
    status: string;
    confirmed_bookings: number;
    independent_purchases: number;
    active_entitlements: number;
    total_enrolments: number;
    revenue_myr: number;
}

interface InstructorStatRow {
    id: number;
    name: string;
    email: string;
    assigned_sessions: number;
    completed_sessions: number;
    upcoming_sessions: number;
    confirmed_participants: number;
    certificates_issued: number;
}

interface ReportData {
    filters: { from: string | null; to: string | null };
    customers: { total: number; active: number; new_in_period: number };
    catalogue: {
        total_modules: number;
        published_modules: number;
        independent_modules: number;
        total_sessions: number;
        published_sessions: number;
        completed_sessions: number;
        cancelled_sessions: number;
    };
    bookings: {
        total: number;
        confirmed: number;
        pending: number;
        expired: number;
        cancelled: number;
        refunded: number;
        unique_participants: number;
    };
    revenue: {
        paid_orders_count: number;
        gross_myr: number;
        session_myr: number;
        module_myr: number;
        net_myr: number;
        successful_payments: number;
        failed_payments: number;
        pending_payments: number;
    };
    refunds: {
        completed_count: number;
        completed_myr: number;
        pending_count: number;
        pending_myr: number;
        rejected_count: number;
    };
    certificates: {
        issued: number;
        revoked: number;
        session_type: number;
        module_type: number;
    };
    attendance_summary: {
        total: number;
        present: number;
        absent: number;
        verified: number;
        pending_review: number;
        rate_percent: number;
    };
    popular_modules: PopularModuleRow[];
    instructor_stats: InstructorStatRow[];
    session_reports: SessionReportRow[];
    booking_rows: BookingReportRow[];
    attendance_rows: AttendanceReportRow[];
    payment_rows: PaymentReportRow[];
    refund_rows: RefundReportRow[];
    certificate_rows: CertificateReportRow[];
}

interface Props extends EtmsSharedProps {
    report: ReportData;
    filters: { from: string; to: string };
    pdfUrl: string;
    exportUrls: {
        sessions: string;
        bookings: string;
        attendance: string;
        revenue: string;
        refunds: string;
        popular_modules: string;
        certificates: string;
        instructors: string;
    };
}

type ReportTab =
    | 'all'
    | 'sessions'
    | 'bookings'
    | 'attendance'
    | 'finance'
    | 'modules'
    | 'certificates';

const props = defineProps<Props>();

const activeTab = ref<ReportTab>('all');
const selectedSessionId = ref<number | 'all'>('all');

const filters = reactive({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const hasActiveFilter = computed(() => Boolean(filters.from || filters.to));

const tabs = computed<{ key: ReportTab; label: string; count?: number }[]>(() => [
    { key: 'all', label: 'Overview' },
    { key: 'sessions', label: 'Sessions & Participants', count: props.report.session_reports.length },
    { key: 'bookings', label: 'Bookings', count: props.report.booking_rows.length },
    { key: 'attendance', label: 'Attendance', count: props.report.attendance_rows.length },
    {
        key: 'finance',
        label: 'Revenue & Refunds',
        count: props.report.payment_rows.length + props.report.refund_rows.length,
    },
    { key: 'modules', label: 'Modules', count: props.report.popular_modules.length },
    {
        key: 'certificates',
        label: 'Certificates & Instructors',
        count: props.report.certificate_rows.length,
    },
]);

const filteredSessions = computed(() => {
    if (selectedSessionId.value === 'all') return props.report.session_reports;
    return props.report.session_reports.filter((s) => s.id === selectedSessionId.value);
});

function formatMyr(amount: number): string {
    return `MYR ${Number(amount ?? 0).toLocaleString('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function formatDateLabel(iso: string): string {
    if (!iso) return '';
    const d = new Date(`${iso}T00:00:00`);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

const periodLabel = computed(() => {
    const f = props.report.filters.from;
    const t = props.report.filters.to;
    if (f && t) return `${formatDateLabel(f)} – ${formatDateLabel(t)}`;
    if (f) return `From ${formatDateLabel(f)}`;
    if (t) return `Up to ${formatDateLabel(t)}`;
    return 'All time';
});

function toIsoDate(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function apply(): void {
    router.get(
        '/admin-vue/reports',
        {
            ...(filters.from ? { from: filters.from } : {}),
            ...(filters.to ? { to: filters.to } : {}),
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters(): void {
    filters.from = '';
    filters.to = '';
    apply();
}

function applyPreset(preset: 'all' | '30d' | 'month' | 'year'): void {
    const now = new Date();
    if (preset === 'all') {
        clearFilters();
        return;
    }
    if (preset === '30d') {
        const start = new Date(now);
        start.setDate(start.getDate() - 29);
        filters.from = toIsoDate(start);
        filters.to = toIsoDate(now);
    } else if (preset === 'month') {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        filters.from = toIsoDate(start);
        filters.to = toIsoDate(now);
    } else if (preset === 'year') {
        const start = new Date(now.getFullYear(), 0, 1);
        filters.from = toIsoDate(start);
        filters.to = toIsoDate(now);
    }
    apply();
}

function exportHref(baseUrl: string, extra?: Record<string, string>): string {
    const params = new URLSearchParams();
    if (filters.from) params.set('from', filters.from);
    if (filters.to) params.set('to', filters.to);
    if (extra) {
        Object.entries(extra).forEach(([k, v]) => params.set(k, v));
    }
    const qs = params.toString();
    return qs ? `${baseUrl}?${qs}` : baseUrl;
}

const activePdfLabel = computed(() => {
    const labels: Record<ReportTab, string> = {
        all: 'Download Full PDF',
        sessions: 'Download Sessions PDF',
        bookings: 'Download Bookings PDF',
        attendance: 'Download Attendance PDF',
        finance: 'Download Finance PDF',
        modules: 'Download Modules PDF',
        certificates: 'Download Certificates PDF',
    };
    return labels[activeTab.value];
});

const csvExports = computed(() => [
    { label: 'Sessions & Capacity (.csv)', href: exportHref(props.exportUrls.sessions) },
    { label: 'Bookings & Registrations (.csv)', href: exportHref(props.exportUrls.bookings) },
    { label: 'Attendance Records (.csv)', href: exportHref(props.exportUrls.attendance) },
    { label: 'Revenue & Paid Orders (.csv)', href: exportHref(props.exportUrls.revenue) },
    { label: 'Refunds (.csv)', href: exportHref(props.exportUrls.refunds) },
    { label: 'Module Performance (.csv)', href: exportHref(props.exportUrls.popular_modules) },
    { label: 'Certificates Register (.csv)', href: exportHref(props.exportUrls.certificates) },
    { label: 'Instructor Activity (.csv)', href: exportHref(props.exportUrls.instructors) },
]);

function statusTone(status: string): 'success' | 'warning' | 'danger' | 'neutral' {
    const s = status.toLowerCase();
    if (
        ['published', 'confirmed', 'successful', 'paid', 'completed', 'issued', 'present', 'verified'].includes(s)
    ) {
        return 'success';
    }
    if (
        [
            'pending',
            'pending payment',
            'draft',
            'requested',
            'processing',
            'submitted',
            'correction requested',
            'unrecorded',
        ].includes(s)
    ) {
        return 'warning';
    }
    if (['failed', 'cancelled', 'revoked', 'rejected', 'absent', 'expired', 'refunded'].includes(s)) {
        return 'danger';
    }
    return 'neutral';
}
</script>

<template>
    <Head title="Reports — Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title text-ink">Reports</h1>
                    <p class="mt-1 text-sm text-muted">
                        Sessions, participants, bookings, attendance, revenue, refunds, modules, and certificates.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <DropdownMenuRoot>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex h-10 items-center gap-2 rounded-xl border border-line bg-surface px-3.5 text-sm font-medium text-ink shadow-xs transition hover:bg-sand-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                            >
                                <Download class="size-4 text-muted" aria-hidden="true" />
                                <span>Export</span>
                                <ChevronDown class="size-3.5 text-muted" aria-hidden="true" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuPortal>
                            <DropdownMenuContent
                                align="end"
                                :side-offset="6"
                                class="z-50 min-w-[245px] rounded-xl border border-line bg-surface p-1.5 shadow-elevated"
                            >
                                <DropdownMenuItem
                                    as-child
                                    class="flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-navy-950 outline-none transition hover:bg-sand-100 focus:bg-sand-100"
                                >
                                    <a :href="exportHref(pdfUrl, { section: 'all' })">
                                        <span>Full System Report (.pdf)</span>
                                    </a>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator class="my-1 h-px bg-line/80" />
                                <DropdownMenuItem
                                    v-for="item in csvExports"
                                    :key="item.label"
                                    as-child
                                    class="flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-sm text-ink outline-none transition hover:bg-sand-100 focus:bg-sand-100"
                                >
                                    <a :href="item.href">
                                        <span>{{ item.label }}</span>
                                    </a>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenuPortal>
                    </DropdownMenuRoot>

                    <a
                        :href="exportHref(pdfUrl, { section: activeTab })"
                        class="inline-flex h-10 items-center gap-2 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                    >
                        <FileDown class="size-4" aria-hidden="true" />
                        <span>{{ activePdfLabel }}</span>
                    </a>
                </div>
            </div>

            <!-- Date Range Filter Bar -->
            <section
                class="rounded-2xl border border-line/85 bg-surface p-4 shadow-card"
                aria-label="Report date filter"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                !hasActiveFilter
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-sand-100 text-muted hover:text-ink'
                            "
                            @click="applyPreset('all')"
                        >
                            All time
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-sand-100 px-3 py-1.5 text-xs font-medium text-muted transition hover:text-ink"
                            @click="applyPreset('30d')"
                        >
                            Last 30 days
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-sand-100 px-3 py-1.5 text-xs font-medium text-muted transition hover:text-ink"
                            @click="applyPreset('month')"
                        >
                            This month
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-sand-100 px-3 py-1.5 text-xs font-medium text-muted transition hover:text-ink"
                            @click="applyPreset('year')"
                        >
                            This year
                        </button>
                    </div>

                    <form class="flex flex-wrap items-end gap-2.5" @submit.prevent="apply">
                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>From</span>
                            <input
                                v-model="filters.from"
                                type="date"
                                class="h-9 rounded-lg border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>
                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>To</span>
                            <input
                                v-model="filters.to"
                                type="date"
                                class="h-9 rounded-lg border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>
                        <button
                            type="submit"
                            class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-3.5 text-sm font-medium text-white transition hover:bg-navy-900"
                        >
                            Apply
                        </button>
                        <button
                            v-if="hasActiveFilter"
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-line bg-surface px-3 text-sm font-medium text-muted transition hover:bg-sand-100 hover:text-ink"
                            @click="clearFilters"
                        >
                            <RotateCcw class="size-3.5" aria-hidden="true" />
                            <span>Reset</span>
                        </button>
                    </form>
                </div>
            </section>

            <!-- Report Category Tabs -->
            <nav
                class="flex items-center gap-1 overflow-x-auto border-b border-line pb-px"
                aria-label="Report categories"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="inline-flex shrink-0 items-center gap-2 border-b-2 px-3.5 py-2.5 text-sm font-medium transition"
                    :class="
                        activeTab === tab.key
                            ? 'border-navy-950 text-navy-950'
                            : 'border-transparent text-muted hover:border-line hover:text-ink'
                    "
                    @click="activeTab = tab.key"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        v-if="tab.count !== undefined"
                        class="tabular rounded-full px-1.5 py-0.5 text-[11px] font-semibold"
                        :class="
                            activeTab === tab.key
                                ? 'bg-navy-950 text-white'
                                : 'bg-sand-100 text-muted'
                        "
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </nav>

            <!-- TAB 1: OVERVIEW -->
            <template v-if="activeTab === 'all'">
                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <KpiCard
                        label="Net revenue"
                        :value="formatMyr(report.revenue.net_myr)"
                        :caption="`Gross ${formatMyr(report.revenue.gross_myr)} · ${report.revenue.paid_orders_count} paid orders`"
                        :icon="Wallet"
                        variant="dark"
                    />
                    <KpiCard
                        label="Confirmed bookings"
                        :value="report.bookings.confirmed"
                        :caption="`${report.bookings.unique_participants} unique trainees · ${report.bookings.total} total`"
                        :icon="TicketCheck"
                    />
                    <KpiCard
                        label="Attendance rate"
                        :value="`${report.attendance_summary.rate_percent.toFixed(1)}%`"
                        :caption="`${report.attendance_summary.present} present · ${report.attendance_summary.verified} verified`"
                        :icon="CheckCircle2"
                    />
                    <KpiCard
                        label="Certificates issued"
                        :value="report.certificates.issued"
                        :caption="`${report.certificates.session_type} session · ${report.certificates.module_type} module`"
                        :icon="Award"
                    />
                </section>

                <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <div class="flex items-baseline justify-between gap-2 pb-3">
                            <h2 class="text-base font-semibold text-ink">Revenue &amp; refunds</h2>
                            <span class="text-xs text-muted">{{ periodLabel }}</span>
                        </div>
                        <dl class="divide-y divide-line/70 text-sm">
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Training session bookings</dt>
                                <dd class="tabular font-medium text-ink">{{ formatMyr(report.revenue.session_myr) }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Standalone module purchases</dt>
                                <dd class="tabular font-medium text-ink">{{ formatMyr(report.revenue.module_myr) }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="font-medium text-ink">Gross revenue</dt>
                                <dd class="tabular font-semibold text-ink">{{ formatMyr(report.revenue.gross_myr) }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">
                                    Completed refunds ({{ report.refunds.completed_count }})
                                </dt>
                                <dd class="tabular font-medium text-danger-600">
                                    - {{ formatMyr(report.refunds.completed_myr) }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between pt-3">
                                <dt class="font-semibold text-ink">Net revenue</dt>
                                <dd class="tabular text-base font-bold text-navy-950">
                                    {{ formatMyr(report.revenue.net_myr) }}
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-line/70 pt-3 text-xs text-muted">
                            <span>
                                Payments: <strong class="font-medium text-ink">{{ report.revenue.successful_payments }}</strong> paid
                                · {{ report.revenue.pending_payments }} pending
                                · {{ report.revenue.failed_payments }} failed
                            </span>
                            <button
                                type="button"
                                class="font-medium text-primary-600 hover:underline"
                                @click="activeTab = 'finance'"
                            >
                                View ledger
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <div class="flex items-baseline justify-between gap-2 pb-3">
                            <h2 class="text-base font-semibold text-ink">Operations &amp; attendance</h2>
                            <span class="text-xs text-muted">{{ periodLabel }}</span>
                        </div>
                        <dl class="divide-y divide-line/70 text-sm">
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Registered trainees</dt>
                                <dd class="tabular font-medium text-ink">
                                    {{ report.customers.total }}
                                    <span class="ml-1 text-xs font-normal text-muted">
                                        ({{ report.customers.active }} active · {{ report.customers.new_in_period }} new)
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Training sessions</dt>
                                <dd class="tabular font-medium text-ink">
                                    {{ report.catalogue.total_sessions }}
                                    <span class="ml-1 text-xs font-normal text-muted">
                                        ({{ report.catalogue.completed_sessions }} completed · {{ report.catalogue.cancelled_sessions }} cancelled)
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Training modules</dt>
                                <dd class="tabular font-medium text-ink">
                                    {{ report.catalogue.published_modules }} / {{ report.catalogue.total_modules }} published
                                </dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Attendance verification</dt>
                                <dd class="tabular font-medium text-ink">
                                    {{ report.attendance_summary.verified }} verified
                                    <span class="ml-1 text-xs font-normal text-muted">
                                        · {{ report.attendance_summary.pending_review }} pending review
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between pt-3">
                                <dt class="text-muted">Certificates revoked</dt>
                                <dd class="tabular font-medium text-ink">{{ report.certificates.revoked }}</dd>
                            </div>
                        </dl>
                        <div class="mt-4 flex items-center justify-between border-t border-line/70 pt-3 text-xs text-muted">
                            <span>All 7 detailed reports are included in Full System PDF.</span>
                            <button
                                type="button"
                                class="font-medium text-primary-600 hover:underline"
                                @click="activeTab = 'sessions'"
                            >
                                View sessions
                            </button>
                        </div>
                    </div>
                </section>
            </template>

            <!-- TAB 2: SESSIONS & PARTICIPANTS -->
            <template v-if="activeTab === 'sessions'">
                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <div class="flex flex-col gap-3 border-b border-line/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-ink">Sessions &amp; capacity</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Schedule, venue, assigned instructors, confirmed seats, attendance, and issued certificates.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select
                                v-model="selectedSessionId"
                                class="h-9 rounded-lg border border-line bg-surface px-3 text-xs font-medium text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option value="all">All sessions ({{ report.session_reports.length }})</option>
                                <option v-for="s in report.session_reports" :key="s.id" :value="s.id">
                                    {{ s.session_code }} — {{ s.title }}
                                </option>
                            </select>
                            <a
                                :href="exportHref(exportUrls.sessions)"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                            >
                                <Download class="size-3.5" aria-hidden="true" />
                                <span>CSV</span>
                            </a>
                        </div>
                    </div>

                    <div v-if="filteredSessions.length === 0" class="px-5 py-10 text-center text-sm text-muted">
                        No sessions found for this period.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Session</th>
                                    <th class="px-4 py-3">Schedule</th>
                                    <th class="px-4 py-3">Instructor(s)</th>
                                    <th class="px-4 py-3 text-right">Seats</th>
                                    <th class="px-4 py-3 text-right">Present</th>
                                    <th class="px-4 py-3 text-right">Certs</th>
                                    <th class="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr v-for="s in filteredSessions" :key="s.id" class="hover:bg-sand-50/60">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-ink">{{ s.title }}</p>
                                        <p class="text-xs text-muted">{{ s.session_code }} · {{ s.venue }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-ink">{{ s.starts_at_label }}</td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ s.instructors }}</td>
                                    <td class="tabular px-4 py-3 text-right text-ink">
                                        {{ s.confirmed_seats }} / {{ s.capacity }}
                                    </td>
                                    <td class="tabular px-4 py-3 text-right text-ink">{{ s.present_count }}</td>
                                    <td class="tabular px-4 py-3 text-right text-ink">{{ s.certificates_count }}</td>
                                    <td class="px-5 py-3">
                                        <StatusBadge :tone="statusTone(s.status)" :label="s.status" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Participant Lists per Session -->
                <div class="space-y-4">
                    <section
                        v-for="s in filteredSessions"
                        :key="`roster-${s.id}`"
                        class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-line/80 px-5 py-3.5">
                            <div>
                                <h3 class="text-sm font-semibold text-ink">
                                    {{ s.session_code }} — {{ s.title }}
                                </h3>
                                <p class="text-xs text-muted">
                                    {{ s.participants.length }} confirmed participant(s) · {{ s.starts_at_label }}
                                </p>
                            </div>
                            <StatusBadge :tone="statusTone(s.status)" :label="s.status" />
                        </div>

                        <div v-if="s.participants.length === 0" class="px-5 py-6 text-center text-xs text-muted">
                            No confirmed participants for this session.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full border-collapse text-left text-sm">
                                <thead>
                                    <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                        <th class="px-5 py-2.5">Booking Ref</th>
                                        <th class="px-4 py-2.5">Trainee</th>
                                        <th class="px-4 py-2.5">Confirmed</th>
                                        <th class="px-4 py-2.5">Attendance</th>
                                        <th class="px-4 py-2.5">Review</th>
                                        <th class="px-5 py-2.5">Certificate</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/65">
                                    <tr v-for="p in s.participants" :key="p.booking_id" class="hover:bg-sand-50/60">
                                        <td class="tabular px-5 py-2.5 font-medium text-ink">{{ p.booking_reference }}</td>
                                        <td class="px-4 py-2.5">
                                            <p class="font-medium text-ink">{{ p.trainee_name }}</p>
                                            <p class="text-xs text-muted">{{ p.trainee_email }}</p>
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-muted">{{ p.confirmed_at }}</td>
                                        <td class="px-4 py-2.5">
                                            <StatusBadge :tone="statusTone(p.attendance_status)" :label="p.attendance_status" />
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <StatusBadge :tone="statusTone(p.review_status)" :label="p.review_status" />
                                        </td>
                                        <td class="px-5 py-2.5 text-xs font-medium text-ink">
                                            {{ p.certificate_issued ? 'Issued' : '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </template>

            <!-- TAB 3: BOOKINGS -->
            <template v-if="activeTab === 'bookings'">
                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-ink">Bookings &amp; registrations</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ report.bookings.confirmed }} confirmed · {{ report.bookings.pending }} pending
                                · {{ report.bookings.cancelled + report.bookings.expired }} closed
                                · {{ report.bookings.refunded }} refunded
                            </p>
                        </div>
                        <a
                            :href="exportHref(exportUrls.bookings)"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>CSV</span>
                        </a>
                    </div>

                    <div v-if="report.booking_rows.length === 0" class="px-5 py-10 text-center text-sm text-muted">
                        No bookings recorded for this period.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Booking Ref</th>
                                    <th class="px-4 py-3">Trainee</th>
                                    <th class="px-4 py-3">Session</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Reserved</th>
                                    <th class="px-5 py-3">Confirmed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr v-for="b in report.booking_rows" :key="b.id" class="hover:bg-sand-50/60">
                                    <td class="tabular px-5 py-3 font-medium text-ink">{{ b.reference }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink">{{ b.trainee_name }}</p>
                                        <p class="text-xs text-muted">{{ b.trainee_email }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink">{{ b.session_title }}</p>
                                        <p class="text-xs text-muted">{{ b.session_code }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :tone="statusTone(b.status)" :label="b.status" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ b.reserved_at }}</td>
                                    <td class="px-5 py-3 text-xs text-muted">{{ b.confirmed_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>

            <!-- TAB 4: ATTENDANCE -->
            <template v-if="activeTab === 'attendance'">
                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-ink">Attendance verification report</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ report.attendance_summary.present }} present · {{ report.attendance_summary.absent }} absent
                                · {{ report.attendance_summary.verified }} verified
                                · {{ report.attendance_summary.rate_percent.toFixed(1) }}% attendance rate
                            </p>
                        </div>
                        <a
                            :href="exportHref(exportUrls.attendance)"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>CSV</span>
                        </a>
                    </div>

                    <div v-if="report.attendance_rows.length === 0" class="px-5 py-10 text-center text-sm text-muted">
                        No attendance records found for this period.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Session</th>
                                    <th class="px-4 py-3">Trainee</th>
                                    <th class="px-4 py-3">Attendance</th>
                                    <th class="px-4 py-3">Review Status</th>
                                    <th class="px-4 py-3">Recorded</th>
                                    <th class="px-5 py-3">Verified</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr v-for="a in report.attendance_rows" :key="a.id" class="hover:bg-sand-50/60">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-ink">{{ a.session_title }}</p>
                                        <p class="text-xs text-muted">{{ a.session_code }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink">{{ a.trainee_name }}</p>
                                        <p class="text-xs text-muted">{{ a.trainee_email }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :tone="statusTone(a.attendance_status)" :label="a.attendance_status" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :tone="statusTone(a.review_status)" :label="a.review_status" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ a.recorded_at }}</td>
                                    <td class="px-5 py-3 text-xs text-muted">{{ a.verified_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>

            <!-- TAB 5: REVENUE, PAYMENTS & REFUNDS -->
            <template v-if="activeTab === 'finance'">
                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-ink">Payments ledger</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Gross {{ formatMyr(report.revenue.gross_myr) }} · Net {{ formatMyr(report.revenue.net_myr) }}
                                · {{ report.revenue.successful_payments }} paid
                            </p>
                        </div>
                        <a
                            :href="exportHref(exportUrls.revenue)"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>CSV</span>
                        </a>
                    </div>

                    <div v-if="report.payment_rows.length === 0" class="px-5 py-10 text-center text-sm text-muted">
                        No payment transactions recorded for this period.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Payment Ref</th>
                                    <th class="px-4 py-3">Order #</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Gateway</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-5 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr v-for="p in report.payment_rows" :key="p.id" class="hover:bg-sand-50/60">
                                    <td class="tabular px-5 py-3 font-medium text-ink">{{ p.payment_reference }}</td>
                                    <td class="tabular px-4 py-3 text-xs text-muted">{{ p.order_number }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink">{{ p.customer_name }}</p>
                                        <p class="text-xs text-muted">{{ p.order_type }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-ink">{{ p.gateway }}</td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :tone="statusTone(p.status)" :label="p.status" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ p.timestamp }}</td>
                                    <td class="tabular px-5 py-3 text-right font-semibold text-ink">
                                        {{ formatMyr(p.amount_myr) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-ink">Refunds ledger</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Completed: {{ report.refunds.completed_count }} ({{ formatMyr(report.refunds.completed_myr) }})
                                · Pending: {{ report.refunds.pending_count }} · Rejected: {{ report.refunds.rejected_count }}
                            </p>
                        </div>
                        <a
                            :href="exportHref(exportUrls.refunds)"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>CSV</span>
                        </a>
                    </div>

                    <div v-if="report.refund_rows.length === 0" class="px-5 py-8 text-center text-sm text-muted">
                        No refunds recorded for this period.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Refund Ref</th>
                                    <th class="px-4 py-3">Order #</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Method</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Requested</th>
                                    <th class="px-5 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr v-for="r in report.refund_rows" :key="r.id" class="hover:bg-sand-50/60">
                                    <td class="tabular px-5 py-3 font-medium text-ink">{{ r.refund_reference }}</td>
                                    <td class="tabular px-4 py-3 text-xs text-muted">{{ r.order_number }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink">{{ r.customer_name }}</p>
                                        <p class="text-xs text-muted">{{ r.customer_email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-ink">{{ r.method }}</td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :tone="statusTone(r.status)" :label="r.status" />
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ r.requested_at }}</td>
                                    <td class="tabular px-5 py-3 text-right font-semibold text-ink">
                                        {{ formatMyr(r.amount_myr) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>

            <!-- TAB 6: MODULES -->
            <template v-if="activeTab === 'modules'">
                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-ink">Module sales &amp; performance</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Ranked by confirmed session bookings and standalone module purchases.
                            </p>
                        </div>
                        <a
                            :href="exportHref(exportUrls.popular_modules)"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>CSV</span>
                        </a>
                    </div>

                    <div v-if="report.popular_modules.length === 0" class="px-5 py-10 text-center text-sm text-muted">
                        No module activity recorded for this period.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Module</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Session bookings</th>
                                    <th class="px-4 py-3 text-right">Module purchases</th>
                                    <th class="px-4 py-3 text-right">Active access</th>
                                    <th class="px-5 py-3 text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr v-for="mod in report.popular_modules" :key="mod.id" class="hover:bg-sand-50/60">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-ink">{{ mod.title }}</p>
                                        <p class="text-xs text-muted">{{ mod.code }} · {{ mod.category }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :tone="statusTone(mod.status)" :label="mod.status" />
                                    </td>
                                    <td class="tabular px-4 py-3 text-right text-ink">{{ mod.confirmed_bookings }}</td>
                                    <td class="tabular px-4 py-3 text-right text-ink">{{ mod.independent_purchases }}</td>
                                    <td class="tabular px-4 py-3 text-right text-ink">{{ mod.active_entitlements }}</td>
                                    <td class="tabular px-5 py-3 text-right font-semibold text-ink">
                                        {{ formatMyr(mod.revenue_myr) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>

            <!-- TAB 7: CERTIFICATES & INSTRUCTORS -->
            <template v-if="activeTab === 'certificates'">
                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-ink">Certificates register</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ report.certificates.issued }} issued ({{ report.certificates.session_type }} session ·
                                {{ report.certificates.module_type }} module) · {{ report.certificates.revoked }} revoked
                            </p>
                        </div>
                        <a
                            :href="exportHref(exportUrls.certificates)"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>CSV</span>
                        </a>
                    </div>

                    <div v-if="report.certificate_rows.length === 0" class="px-5 py-10 text-center text-sm text-muted">
                        No certificates recorded for this period.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Certificate #</th>
                                    <th class="px-4 py-3">Trainee</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Training Programme</th>
                                    <th class="px-4 py-3">Issued</th>
                                    <th class="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr v-for="c in report.certificate_rows" :key="c.id" class="hover:bg-sand-50/60">
                                    <td class="tabular px-5 py-3 font-medium text-ink">{{ c.certificate_number }}</td>
                                    <td class="px-4 py-3 font-medium text-ink">{{ c.trainee_name }}</td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ c.record_type }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-ink">{{ c.training_title }}</p>
                                        <p class="text-xs text-muted">{{ c.training_code }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted">{{ c.issued_at }}</td>
                                    <td class="px-5 py-3">
                                        <StatusBadge :tone="statusTone(c.status)" :label="c.status" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                        <div>
                            <h2 class="text-base font-semibold text-ink">Instructor activity</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Assigned sessions, confirmed participants, and certificates issued per instructor.
                            </p>
                        </div>
                        <a
                            :href="exportHref(exportUrls.instructors)"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:underline"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>CSV</span>
                        </a>
                    </div>

                    <div v-if="report.instructor_stats.length === 0" class="px-5 py-8 text-center text-sm text-muted">
                        No instructors found.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                    <th class="px-5 py-3">Instructor</th>
                                    <th class="px-4 py-3 text-right">Assigned</th>
                                    <th class="px-4 py-3 text-right">Completed</th>
                                    <th class="px-4 py-3 text-right">Upcoming</th>
                                    <th class="px-4 py-3 text-right">Participants</th>
                                    <th class="px-5 py-3 text-right">Certificates</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/65">
                                <tr v-for="inst in report.instructor_stats" :key="inst.id" class="hover:bg-sand-50/60">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-ink">{{ inst.name }}</p>
                                        <p class="text-xs text-muted">{{ inst.email }}</p>
                                    </td>
                                    <td class="tabular px-4 py-3 text-right font-medium text-ink">
                                        {{ inst.assigned_sessions }}
                                    </td>
                                    <td class="tabular px-4 py-3 text-right text-ink">{{ inst.completed_sessions }}</td>
                                    <td class="tabular px-4 py-3 text-right text-ink">{{ inst.upcoming_sessions }}</td>
                                    <td class="tabular px-4 py-3 text-right text-ink">
                                        {{ inst.confirmed_participants }}
                                    </td>
                                    <td class="tabular px-5 py-3 text-right font-medium text-ink">
                                        {{ inst.certificates_issued }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>
        </div>
    </AdminLayout>
</template>
