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
    ReceiptText,
    RotateCcw,
    Search,
    X,
    XCircle,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface RefundItem {
    id: number;
    reference: string;
    orderNumber: string | null;
    customer: string;
    amount: string;
    method: string;
    status: string;
    reason: string | null;
    requestedAt: string | null;
}

type RefundRow = RefundItem & Record<string, unknown>;

interface Props extends EtmsSharedProps {
    refunds: {
        data: RefundItem[];
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
const action = ref<{ type: 'complete' | 'reject'; refund: RefundItem } | null>(null);
const form = reactive({
    refund_method: 'manual_bank_transfer',
    disbursement_reference: '',
    failure_reason: '',
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

const rows = computed<RefundRow[]>(() => props.refunds.data as RefundRow[]);

const columns: DataTableColumn[] = [
    { key: 'refund', label: 'Refund', tabular: true },
    { key: 'customer', label: 'Customer' },
    { key: 'amount', label: 'Amount', align: 'right', tabular: true },
    { key: 'method', label: 'Method' },
    { key: 'status', label: 'Status' },
    { key: 'reason', label: 'Reason' },
];

function apply(): void {
    router.get(
        '/admin-vue/refunds',
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

function statusTone(status: string): StatusTone {
    const s = status.toLowerCase();
    if (s.includes('complet')) return 'success';
    if (s.includes('request') || s.includes('approv') || s.includes('process')) return 'warning';
    if (s.includes('reject') || s.includes('fail')) return 'danger';
    return 'neutral';
}

function submitAction(): void {
    if (!action.value) return;
    busy.value = true;
    const isComplete = action.value.type === 'complete';
    router.post(
        `/admin-vue/refunds/${action.value.refund.id}/${isComplete ? 'complete' : 'reject'}`,
        isComplete
            ? {
                  refund_method: form.refund_method,
                  disbursement_reference: form.disbursement_reference,
              }
            : { failure_reason: form.failure_reason.trim() },
        {
            preserveScroll: true,
            onFinish: () => {
                busy.value = false;
                action.value = null;
                form.disbursement_reference = '';
                form.failure_reason = '';
            },
        },
    );
}

function decodeLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <Head title="Refunds" />

    <AdminLayout breadcrumb="Refunds">
        <div class="space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Refunds</h1>
                    <p class="mt-1 text-sm text-muted">
                        Review refund requests and record disbursement completion or rejection.
                    </p>
                </div>

                <AppButton href="/admin-vue/payments" variant="outline" size="sm" pill>
                    <CreditCard class="size-3.5" aria-hidden="true" />
                    <span>Payments</span>
                </AppButton>
            </header>

            <!-- Filter & Search Bar -->
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 xl:flex-row xl:items-center xl:justify-between">
                <div
                    role="navigation"
                    aria-label="Filter refunds by status"
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
                            placeholder="Search refund, order, customer..."
                            aria-label="Search refunds"
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
                :icon="ReceiptText"
                title="No refunds found"
                :description="
                    hasActiveFilters
                        ? 'Try clearing your search or status filter.'
                        : 'Refund requests and processed disbursements will appear here.'
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
                    <template #cell-refund="{ row }">
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
                        <span class="font-semibold text-navy-950">{{ row.customer }}</span>
                    </template>

                    <template #cell-amount="{ row }">
                        <span class="tabular font-bold text-navy-950">MYR {{ row.amount }}</span>
                    </template>

                    <template #cell-method="{ row }">
                        <span class="text-sm text-ink">{{ row.method }}</span>
                    </template>

                    <template #cell-status="{ row }">
                        <StatusBadge :tone="statusTone(row.status)" dot>
                            {{ row.status }}
                        </StatusBadge>
                    </template>

                    <template #cell-reason="{ row }">
                        <p class="max-w-xs truncate text-xs text-muted" :title="row.reason ?? ''">
                            {{ row.reason || '—' }}
                        </p>
                    </template>

                    <template #actions="{ row }">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <button
                                v-if="['Requested', 'Approved', 'Processing'].includes(row.status)"
                                type="button"
                                class="inline-flex h-8 items-center gap-1 rounded-full bg-success-50 px-3 text-xs font-semibold text-success-700 ring-1 ring-inset ring-success-600/20 transition-colors hover:bg-success-600 hover:text-white"
                                @click="action = { type: 'complete', refund: row }"
                            >
                                <CheckCircle2 class="size-3.5" aria-hidden="true" />
                                <span>Complete</span>
                            </button>

                            <button
                                v-if="row.status === 'Requested'"
                                type="button"
                                class="inline-flex h-8 items-center gap-1 rounded-full bg-danger-50 px-3 text-xs font-semibold text-danger-700 ring-1 ring-inset ring-danger-600/20 transition-colors hover:bg-danger-600 hover:text-white"
                                @click="action = { type: 'reject', refund: row }"
                            >
                                <XCircle class="size-3.5" aria-hidden="true" />
                                <span>Reject</span>
                            </button>

                            <span
                                v-if="!['Requested', 'Approved', 'Processing'].includes(row.status)"
                                class="text-xs text-muted"
                            >
                                —
                            </span>
                        </div>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <div
                    v-if="refunds.total > 0"
                    class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
                >
                    <span class="tabular">
                        Showing {{ refunds.from ?? 0 }}–{{ refunds.to ?? 0 }} of {{ refunds.total }} refunds
                    </span>

                    <nav
                        v-if="refunds.links.length > 3"
                        class="flex flex-wrap items-center gap-1"
                        aria-label="Pagination"
                    >
                        <template v-for="link in refunds.links" :key="link.label">
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

        <!-- Reka UI Complete / Reject Refund Modal -->
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
                                {{ action.type === 'complete' ? 'Complete refund' : 'Reject refund' }}
                            </DialogTitle>
                            <p class="mt-1 text-xs text-muted">
                                <span class="tabular-nums-mono font-semibold">{{ action.refund.reference }}</span>
                                &middot; MYR {{ action.refund.amount }}
                            </p>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted hover:bg-sand-100 hover:text-navy-950"
                            aria-label="Close dialog"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </DialogClose>
                    </div>

                    <div v-if="action.type === 'complete'" class="mt-4 space-y-3.5">
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
                    </div>

                    <div v-else class="mt-4">
                        <label class="block text-xs font-medium text-ink">
                            Rejection reason <span class="text-danger-600">*</span>
                        </label>
                        <textarea
                            v-model="form.failure_reason"
                            rows="3"
                            required
                            maxlength="500"
                            placeholder="Enter mandatory rejection reason..."
                            class="mt-1.5 w-full rounded-xl border border-line-strong/80 bg-white p-3 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2.5">
                        <DialogClose
                            class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-muted hover:bg-sand-100 hover:text-navy-950"
                        >
                            Cancel
                        </DialogClose>
                        <button
                            type="button"
                            :disabled="busy || (action.type === 'reject' && !form.failure_reason.trim())"
                            class="inline-flex h-9 items-center rounded-lg px-5 text-xs font-semibold text-white transition-colors disabled:opacity-50"
                            :class="
                                action.type === 'reject'
                                    ? 'bg-danger-600 hover:bg-danger-700'
                                    : 'bg-navy-950 hover:bg-navy-900'
                            "
                            @click="submitAction"
                        >
                            {{ busy ? 'Saving...' : action.type === 'complete' ? 'Complete refund' : 'Reject refund' }}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </AdminLayout>
</template>
