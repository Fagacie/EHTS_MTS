<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import {
    CheckCircle2,
    CreditCard,
    RotateCcw,
    Search,
    X,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface PaymentItem {
    id: number;
    reference: string;
    orderNumber: string | null;
    customer: string;
    email: string | null;
    amount: string;
    refundedAmount: string;
    status: string;
    paidAt: string | null;
}

type PaymentRow = PaymentItem & Record<string, unknown>;

interface Props extends EtmsSharedProps {
    payments: {
        data: PaymentItem[];
        from: number | null;
        to: number | null;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
}

const props = defineProps<Props>();
const filters = reactive({ ...props.filters });
const action = ref<{ type: 'verify' | 'refund'; payment: PaymentItem } | null>(null);
const form = reactive({
    amount: '',
    refund_method: 'manual_bank_transfer',
    disbursement_reference: '',
    reason: '',
});
const busy = ref(false);

const modalOpen = computed({
    get: () => action.value !== null,
    set: (open: boolean) => {
        if (!open) action.value = null;
    },
});

const hasActiveFilters = computed(
    () => filters.q.trim() !== '' || filters.status !== '',
);

const rows = computed<PaymentRow[]>(() => props.payments.data as PaymentRow[]);

const columns: DataTableColumn[] = [
    { key: 'payment', label: 'Payment', tabular: true },
    { key: 'customer', label: 'Customer' },
    { key: 'amount', label: 'Amount', align: 'right', tabular: true },
    { key: 'status', label: 'Status' },
    { key: 'paid', label: 'Settled', tabular: true },
];

function apply(): void {
    router.get(
        '/admin-vue/payments',
        {
            q: filters.q.trim() || undefined,
            status: filters.status || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function selectStatus(val: string): void {
    filters.status = val;
    apply();
}

function clear(): void {
    filters.q = '';
    filters.status = '';
    apply();
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-MY', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: props.app.displayTimezone,
    }).format(new Date(value));
}

function statusTone(status: string): StatusTone {
    const s = status.toLowerCase();
    if (s.includes('success') || s.includes('paid') || s.includes('verified')) return 'success';
    if (s.includes('pend') || s.includes('process')) return 'warning';
    if (s.includes('fail') || s.includes('refund') || s.includes('cancel') || s.includes('expir')) return 'danger';
    return 'neutral';
}

function openRefund(payment: PaymentItem): void {
    form.amount = payment.amount;
    form.refund_method = 'manual_bank_transfer';
    form.disbursement_reference = '';
    form.reason = '';
    action.value = { type: 'refund', payment };
}

function submitAction(): void {
    if (!action.value) return;
    busy.value = true;
    const url =
        action.value.type === 'verify'
            ? `/admin-vue/payments/${action.value.payment.id}/verify`
            : `/admin-vue/payments/${action.value.payment.id}/refund`;

    router.post(url, action.value.type === 'verify' ? {} : form, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
            action.value = null;
        },
    });
}

function decodeLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <Head title="Payments" />

    <AdminLayout breadcrumb="Payments">
        <div class="space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Payments</h1>
                    <p class="mt-1 text-sm text-muted">
                        Review payment gateway transactions, verify settlements, and record refunds.
                    </p>
                </div>

                <AppButton href="/admin-vue/refunds" variant="outline" size="sm" pill>
                    <RotateCcw class="size-3.5" aria-hidden="true" />
                    <span>Refunds</span>
                </AppButton>
            </header>

            <!-- Filter & Search Bar -->
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 xl:flex-row xl:items-center xl:justify-between">
                <div
                    role="navigation"
                    aria-label="Filter payments by status"
                    class="flex flex-wrap items-center gap-1.5"
                >
                    <button
                        type="button"
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            filters.status === ''
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectStatus('')"
                    >
                        All
                    </button>

                    <button
                        v-for="statusOption in statuses"
                        :key="statusOption.value"
                        type="button"
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            filters.status === statusOption.value
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectStatus(statusOption.value)"
                    >
                        {{ statusOption.label }}
                    </button>
                </div>

                <form class="flex items-center gap-2" @submit.prevent="apply">
                    <div class="relative flex-1 xl:w-72">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            v-model="filters.q"
                            type="search"
                            placeholder="Search payment, order, customer..."
                            aria-label="Search payments"
                            class="h-9 w-full rounded-xl border border-line-strong/80 bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-9 items-center rounded-xl bg-navy-950 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900"
                    >
                        Search
                    </button>

                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-white px-3 text-xs font-semibold text-muted transition-colors hover:bg-sand-50 hover:text-navy-950"
                        @click="clear"
                    >
                        <RotateCcw class="size-3.5" aria-hidden="true" />
                        <span>Reset</span>
                    </button>
                </form>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="rows.length === 0"
                :icon="CreditCard"
                title="No payments found"
                :description="
                    hasActiveFilters
                        ? 'Try clearing your search term or status filter.'
                        : 'Payment transactions recorded by the gateway will appear here.'
                "
            >
                <template #action>
                    <AppButton
                        v-if="hasActiveFilters"
                        variant="outline"
                        size="sm"
                        pill
                        @click="clear"
                    >
                        Reset filters
                    </AppButton>
                </template>
            </EmptyState>

            <!-- DataTable + Pagination -->
            <template v-else>
                <DataTable :columns="columns" :rows="rows" row-key="id">
                    <template #cell-payment="{ row }">
                        <p class="tabular-nums-mono text-sm font-semibold text-navy-950">
                            {{ row.reference }}
                        </p>
                        <Link
                            v-if="row.orderNumber"
                            :href="`/admin-vue/orders/${row.orderNumber}`"
                            class="tabular-nums-mono mt-0.5 inline-block text-xs text-primary-600 hover:underline"
                        >
                            {{ row.orderNumber }}
                        </Link>
                        <p v-else class="mt-0.5 text-xs text-muted">No linked order</p>
                    </template>

                    <template #cell-customer="{ row }">
                        <p class="font-semibold text-navy-950">{{ row.customer }}</p>
                        <p v-if="row.email" class="mt-0.5 text-xs text-muted">{{ row.email }}</p>
                    </template>

                    <template #cell-amount="{ row }">
                        <p class="tabular font-bold text-navy-950">
                            MYR {{ row.amount }}
                        </p>
                        <p
                            v-if="row.refundedAmount && row.refundedAmount !== '0.00'"
                            class="tabular mt-0.5 text-xs text-danger-600"
                        >
                            Refunded MYR {{ row.refundedAmount }}
                        </p>
                    </template>

                    <template #cell-status="{ row }">
                        <StatusBadge :tone="statusTone(row.status)" dot>
                            {{ row.status }}
                        </StatusBadge>
                    </template>

                    <template #cell-paid="{ row }">
                        <span class="tabular text-sm text-ink">
                            {{ formatDate(row.paidAt) }}
                        </span>
                    </template>

                    <template #actions="{ row }">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <button
                                type="button"
                                class="inline-flex h-8 items-center gap-1 rounded-full bg-sand-50 px-3 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-navy-950 hover:text-white hover:ring-navy-950"
                                @click="action = { type: 'verify', payment: row }"
                            >
                                <CheckCircle2 class="size-3.5" aria-hidden="true" />
                                <span>Verify</span>
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-8 items-center gap-1 rounded-full bg-warning-50 px-3 text-xs font-semibold text-warning-700 ring-1 ring-inset ring-warning-600/20 transition-colors hover:bg-warning-600 hover:text-white"
                                @click="openRefund(row)"
                            >
                                <RotateCcw class="size-3.5" aria-hidden="true" />
                                <span>Refund</span>
                            </button>
                        </div>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <div
                    v-if="payments.total > 0"
                    class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
                >
                    <span class="tabular">
                        Showing {{ payments.from ?? 0 }}–{{ payments.to ?? 0 }} of {{ payments.total }} payments
                    </span>

                    <nav
                        v-if="payments.links.length > 3"
                        class="flex flex-wrap items-center gap-1"
                        aria-label="Pagination"
                    >
                        <template v-for="link in payments.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="tabular inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs font-semibold transition-colors"
                                :class="
                                    link.active
                                        ? 'bg-navy-950 text-white'
                                        : 'bg-white text-ink ring-1 ring-line hover:bg-sand-50'
                                "
                            >
                                {{ decodeLabel(link.label) }}
                            </Link>
                            <span
                                v-else
                                class="tabular inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs font-medium text-muted/45"
                            >
                                {{ decodeLabel(link.label) }}
                            </span>
                        </template>
                    </nav>
                </div>
            </template>
        </div>

        <!-- Reka UI Verify / Refund Modal -->
        <DialogRoot v-model:open="modalOpen">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/55 backdrop-blur-[2px]" />
                <DialogContent
                    v-if="action"
                    class="fixed top-1/2 left-1/2 z-50 w-[calc(100vw-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-neutral-200 focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <DialogTitle class="text-base font-bold text-navy-950">
                                {{ action.type === 'verify' ? 'Verify payment settlement' : 'Record refund' }}
                            </DialogTitle>
                            <p class="mt-1 text-xs text-muted">
                                <span class="tabular-nums-mono font-semibold">{{ action.payment.reference }}</span>
                                &middot; MYR {{ action.payment.amount }}
                            </p>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted hover:bg-sand-100 hover:text-navy-950"
                            aria-label="Close dialog"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </DialogClose>
                    </div>

                    <div v-if="action.type === 'verify'" class="mt-4">
                        <p class="rounded-xl bg-sand-50 p-3.5 text-sm text-ink ring-1 ring-line/70">
                            Query the payment gateway to reconcile and verify the settlement status of this payment.
                        </p>
                    </div>

                    <div v-else class="mt-4 space-y-3.5">
                        <div>
                            <label class="block text-xs font-medium text-ink">
                                Refund amount (MYR) <span class="text-danger-600">*</span>
                            </label>
                            <input
                                v-model="form.amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                required
                                class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-ink">Refund method</label>
                            <select
                                v-model="form.refund_method"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            >
                                <option value="manual_bank_transfer">Manual bank transfer</option>
                                <option value="original_method">Original payment method</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-ink">
                                Disbursement reference <span class="font-normal text-muted">(optional)</span>
                            </label>
                            <input
                                v-model="form.disbursement_reference"
                                type="text"
                                placeholder="e.g. Bank transfer reference"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-ink">
                                Reason <span class="text-danger-600">*</span>
                            </label>
                            <textarea
                                v-model="form.reason"
                                rows="3"
                                required
                                maxlength="500"
                                placeholder="Enter mandatory refund reason..."
                                class="mt-1.5 w-full rounded-xl border border-line-strong/80 bg-white p-3 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2.5">
                        <DialogClose
                            class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-muted hover:bg-sand-100 hover:text-navy-950"
                        >
                            Cancel
                        </DialogClose>
                        <button
                            type="button"
                            :disabled="busy || (action.type === 'refund' && (!form.amount || !form.reason.trim()))"
                            class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                            @click="submitAction"
                        >
                            {{ busy ? 'Saving...' : action.type === 'verify' ? 'Verify payment' : 'Record refund' }}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </AdminLayout>
</template>
