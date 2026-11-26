<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    Clock,
    CreditCard,
    Receipt,
    RotateCcw,
    Search,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalOrderListItem, PortalOrdersIndexProps } from '@/types/etms';

const props = defineProps<PortalOrdersIndexProps>();

const { routes } = useRoutes();
const searchQuery = ref('');

type OrderRow = PortalOrderListItem & Record<string, unknown>;

const filteredOrders = computed<OrderRow[]>(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) {
        return props.orders as OrderRow[];
    }

    return props.orders.filter(
        (order) =>
            order.orderNumber.toLowerCase().includes(q) ||
            order.itemTitle.toLowerCase().includes(q) ||
            order.orderTypeLabel.toLowerCase().includes(q) ||
            (order.itemCode?.toLowerCase().includes(q) ?? false) ||
            (order.gatewayLabel?.toLowerCase().includes(q) ?? false),
    ) as OrderRow[];
});

const hasActiveFilters = computed(
    () => props.typeFilter !== '' || props.statusFilter !== '' || searchQuery.value.trim() !== '',
);

const columns: DataTableColumn[] = [
    { key: 'order', label: 'Order', tabular: true },
    { key: 'item', label: 'Training Item' },
    { key: 'type', label: 'Type & Gateway' },
    { key: 'status', label: 'Status' },
    { key: 'totalLabel', label: 'Amount', align: 'right', tabular: true },
];

function buildFilterUrl(params: { type?: string; status?: string }): string {
    const query = new URLSearchParams();
    if (params.type) {
        query.set('type', params.type);
    }
    if (params.status) {
        query.set('status', params.status);
    }

    const qs = query.toString();

    return qs ? `${routes.value.portalOrders}?${qs}` : routes.value.portalOrders;
}

function statusTone(statusValue: string): StatusTone {
    if (['paid', 'completed', 'confirmed'].includes(statusValue)) return 'success';
    if (['pending_payment', 'pending', 'partially_refunded'].includes(statusValue)) return 'warning';
    if (['failed', 'cancelled', 'expired', 'refunded'].includes(statusValue)) return 'danger';
    return 'primary';
}
</script>

<template>
    <Head title="Orders & Payments" />

    <PortalLayout breadcrumb="Orders & Payments">
        <div class="mx-auto max-w-[1440px] space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Orders &amp; payments</h1>
                    <p class="mt-1 text-sm text-muted">
                        Review transaction history, payment confirmations, and refund updates for your bookings and modules.
                    </p>
                </div>

                <AppButton :href="routes.catalogue" variant="warm" size="sm" pill>
                    <span>Browse Training</span>
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </AppButton>
            </header>

            <!-- Modern Filter & Quick Search Bar -->
            <div class="space-y-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <!-- Order Type Pills -->
                    <div
                        role="navigation"
                        aria-label="Filter orders by type"
                        class="flex flex-wrap items-center gap-1.5"
                    >
                        <span class="mr-1 text-xs font-semibold text-muted">Type:</span>

                        <Link
                            :href="buildFilterUrl({ status: props.statusFilter })"
                            preserve-scroll
                            class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                            :class="
                                props.typeFilter === ''
                                    ? 'bg-navy-950 text-white shadow-xs'
                                    : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                            "
                        >
                            All Types
                        </Link>

                        <Link
                            v-for="typeOption in props.orderTypes"
                            :key="typeOption.value"
                            :href="buildFilterUrl({ type: typeOption.value, status: props.statusFilter })"
                            preserve-scroll
                            class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                            :class="
                                props.typeFilter === typeOption.value
                                    ? 'bg-navy-950 text-white shadow-xs'
                                    : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                            "
                        >
                            {{ typeOption.label }}
                        </Link>
                    </div>

                    <!-- Inline Search + Reset -->
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 lg:w-64">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                                aria-hidden="true"
                            />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Filter order #, item, code..."
                                aria-label="Filter displayed orders"
                                class="h-9 w-full rounded-xl border border-line-strong/80 bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>

                        <Link
                            v-if="hasActiveFilters"
                            :href="routes.portalOrders"
                            preserve-scroll
                            class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-white px-3 text-xs font-semibold text-muted transition-colors hover:bg-sand-50 hover:text-navy-950"
                            @click="searchQuery = ''"
                        >
                            <RotateCcw class="size-3.5" aria-hidden="true" />
                            <span>Reset</span>
                        </Link>
                    </div>
                </div>

                <!-- Status Pills -->
                <div
                    role="navigation"
                    aria-label="Filter orders by payment status"
                    class="flex flex-wrap items-center gap-1.5 border-t border-line/70 pt-3"
                >
                    <span class="mr-1 text-xs font-semibold text-muted">Status:</span>

                    <Link
                        :href="buildFilterUrl({ type: props.typeFilter })"
                        preserve-scroll
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors duration-150"
                        :class="
                            props.statusFilter === ''
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                    >
                        All Statuses
                    </Link>

                    <Link
                        v-for="statusOption in props.statuses"
                        :key="statusOption.value"
                        :href="buildFilterUrl({ type: props.typeFilter, status: statusOption.value })"
                        preserve-scroll
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-colors duration-150"
                        :class="
                            props.statusFilter === statusOption.value
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                    >
                        {{ statusOption.label }}
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="filteredOrders.length === 0"
                :icon="Receipt"
                title="No orders found"
                :description="
                    hasActiveFilters
                        ? 'No orders match your current filters or search term.'
                        : 'Your completed or pending bookings and module purchases will appear here.'
                "
            >
                <template #action>
                    <div class="flex flex-wrap items-center justify-center gap-2.5">
                        <AppButton
                            v-if="hasActiveFilters"
                            :href="routes.portalOrders"
                            variant="outline"
                            size="sm"
                            pill
                            @click="searchQuery = ''"
                        >
                            Reset filters
                        </AppButton>
                        <AppButton :href="routes.catalogue" variant="warm" size="sm" pill>
                            Explore Training Catalogue
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <template v-else>
                <!-- Mobile Cards (< md) -->
                <div class="space-y-3.5 md:hidden">
                    <div
                        v-for="order in filteredOrders"
                        :key="order.id"
                        class="rounded-2xl bg-white p-4 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <Link
                                :href="order.detailUrl"
                                class="tabular-nums-mono text-xs font-semibold text-navy-900 hover:text-primary-600"
                            >
                                {{ order.orderNumber }}
                            </Link>
                            <StatusBadge :tone="statusTone(order.statusValue)" dot>
                                {{ order.statusLabel }}
                            </StatusBadge>
                        </div>

                        <Link
                            :href="order.detailUrl"
                            class="mt-2.5 block text-base font-semibold text-navy-950 hover:text-primary-600"
                        >
                            {{ order.itemTitle }}
                        </Link>

                        <div class="mt-2.5 space-y-1.5 text-xs text-muted">
                            <p class="flex items-center gap-1.5">
                                <CreditCard class="size-3.5 text-primary-600" aria-hidden="true" />
                                <span>
                                    {{ order.orderTypeLabel }}
                                    <template v-if="order.gatewayLabel"> &middot; {{ order.gatewayLabel }}</template>
                                </span>
                            </p>
                            <p class="tabular flex items-center gap-1.5">
                                <Clock class="size-3.5 text-accent-600" aria-hidden="true" />
                                <span>{{ order.createdAtLabel }} MYT</span>
                            </p>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-line pt-3">
                            <span class="tabular text-sm font-bold text-navy-950">
                                {{ order.totalLabel }}
                            </span>
                            <AppButton
                                :href="order.detailUrl"
                                :variant="order.statusValue === 'pending_payment' ? 'warm' : 'outline'"
                                size="sm"
                                pill
                            >
                                <span>
                                    {{ order.statusValue === 'pending_payment' ? 'Complete Payment' : 'View Order' }}
                                </span>
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            </AppButton>
                        </div>
                    </div>
                </div>

                <!-- Desktop Modern DataTable -->
                <div class="hidden md:block">
                    <DataTable
                        :columns="columns"
                        :rows="filteredOrders"
                        row-key="id"
                    >
                        <template #cell-order="{ row }">
                            <Link
                                :href="row.detailUrl"
                                class="tabular-nums-mono text-sm font-semibold text-navy-950 transition-colors hover:text-primary-600"
                            >
                                {{ row.orderNumber }}
                            </Link>
                            <p class="tabular mt-0.5 text-xs text-muted">
                                {{ row.createdAtLabel }}
                            </p>
                        </template>

                        <template #cell-item="{ row }">
                            <Link
                                :href="row.detailUrl"
                                class="font-semibold text-navy-950 transition-colors hover:text-primary-600"
                            >
                                {{ row.itemTitle }}
                            </Link>
                            <p v-if="row.itemCode" class="tabular-nums-mono mt-0.5 text-xs text-muted">
                                {{ row.itemCode }}
                            </p>
                        </template>

                        <template #cell-type="{ row }">
                            <div class="text-sm font-medium text-ink">
                                {{ row.orderTypeLabel }}
                            </div>
                            <div v-if="row.gatewayLabel" class="mt-0.5 text-xs text-muted">
                                via {{ row.gatewayLabel }}
                            </div>
                        </template>

                        <template #cell-status="{ row }">
                            <StatusBadge :tone="statusTone(row.statusValue)" dot>
                                {{ row.statusLabel }}
                            </StatusBadge>
                        </template>

                        <template #cell-totalLabel="{ row }">
                            <span class="tabular font-bold text-navy-950">
                                {{ row.totalLabel }}
                            </span>
                        </template>

                        <template #actions="{ row }">
                            <Link
                                :href="row.detailUrl"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-150"
                                :class="
                                    row.statusValue === 'pending_payment'
                                        ? 'bg-warm-500 text-white shadow-xs hover:bg-warm-600'
                                        : 'bg-sand-50 text-navy-950 ring-1 ring-line hover:bg-navy-950 hover:text-white hover:ring-navy-950'
                                "
                            >
                                <span>
                                    {{ row.statusValue === 'pending_payment' ? 'Pay Now' : 'View Order' }}
                                </span>
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            </Link>
                        </template>
                    </DataTable>
                </div>

                <PublicPagination
                    :pagination="props.pagination"
                    item-label="orders"
                />
            </template>
        </div>
    </PortalLayout>
</template>
