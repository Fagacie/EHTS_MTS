<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { cn } from '@/lib/utils';
import type { PortalOrdersIndexProps } from '@/types/etms';

const props = defineProps<PortalOrdersIndexProps>();

const { routes } = useRoutes();

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
</script>

<template>
    <Head title="Orders & Payments" />

    <PortalLayout breadcrumb="Orders & Payments">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        Orders &amp; Payments
                    </h1>
                    <p class="mt-1 text-[13.5px] text-[#625D6D]">
                        Review transaction history, payment statuses, and refund updates for your bookings and modules.
                    </p>
                </div>

                <Button as-child variant="outline" class="shrink-0">
                    <Link :href="routes.catalogue">
                        <span>Browse Training</span>
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </Button>
            </header>

            <Card class="space-y-3 p-4 sm:px-5">
                <div
                    class="flex flex-wrap items-center gap-2"
                    role="navigation"
                    aria-label="Filter orders by type"
                >
                    <span class="mr-1 text-[12.5px] font-bold text-[#625D6D]">Type:</span>

                    <Link
                        :href="buildFilterUrl({ status: props.statusFilter })"
                        preserve-scroll
                        :class="
                            cn(
                                'inline-flex min-h-8.5 items-center rounded-[8px] px-3 py-1.5 text-[12.5px] font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                props.typeFilter === ''
                                    ? 'bg-[#7057D9] text-white'
                                    : 'border border-[#E5E1EB] bg-white text-[#403B4B] hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338]',
                            )
                        "
                    >
                        All Types
                    </Link>

                    <Link
                        v-for="typeOption in props.orderTypes"
                        :key="typeOption.value"
                        :href="buildFilterUrl({ type: typeOption.value, status: props.statusFilter })"
                        preserve-scroll
                        :class="
                            cn(
                                'inline-flex min-h-8.5 items-center rounded-[8px] px-3 py-1.5 text-[12.5px] font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                props.typeFilter === typeOption.value
                                    ? 'bg-[#7057D9] text-white'
                                    : 'border border-[#E5E1EB] bg-white text-[#403B4B] hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338]',
                            )
                        "
                    >
                        {{ typeOption.label }}
                    </Link>
                </div>

                <div
                    class="flex flex-wrap items-center gap-2 border-t border-[#EEEAF2] pt-3"
                    role="navigation"
                    aria-label="Filter orders by payment status"
                >
                    <span class="mr-1 text-[12.5px] font-bold text-[#625D6D]">Status:</span>

                    <Link
                        :href="buildFilterUrl({ type: props.typeFilter })"
                        preserve-scroll
                        :class="
                            cn(
                                'inline-flex min-h-8.5 items-center rounded-[8px] px-3 py-1.5 text-[12.5px] font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                props.statusFilter === ''
                                    ? 'bg-[#7057D9] text-white'
                                    : 'border border-[#E5E1EB] bg-white text-[#403B4B] hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338]',
                            )
                        "
                    >
                        All Statuses
                    </Link>

                    <Link
                        v-for="statusOption in props.statuses"
                        :key="statusOption.value"
                        :href="buildFilterUrl({ type: props.typeFilter, status: statusOption.value })"
                        preserve-scroll
                        :class="
                            cn(
                                'inline-flex min-h-8.5 items-center rounded-[8px] px-3 py-1.5 text-[12.5px] font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                props.statusFilter === statusOption.value
                                    ? 'bg-[#7057D9] text-white'
                                    : 'border border-[#E5E1EB] bg-white text-[#403B4B] hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338]',
                            )
                        "
                    >
                        {{ statusOption.label }}
                    </Link>

                    <Link
                        v-if="props.typeFilter !== '' || props.statusFilter !== ''"
                        :href="routes.portalOrders"
                        preserve-scroll
                        class="ml-auto inline-flex min-h-8.5 items-center px-2 text-[12.5px] font-bold text-[#5942B5] hover:text-[#7057D9] hover:underline underline-offset-4"
                    >
                        Reset all filters
                    </Link>
                </div>
            </Card>

            <Card
                v-if="props.orders.length === 0"
                class="flex flex-col items-center justify-center px-6 py-12 text-center"
            >
                <p class="text-[16px] font-bold text-[#282338]">
                    No orders found
                </p>
                <p class="mt-1.5 max-w-md text-[13.5px] text-[#625D6D]">
                    {{
                        props.typeFilter !== '' || props.statusFilter !== ''
                            ? 'No orders match the currently selected filters.'
                            : 'Your completed or pending bookings and module purchases will appear here.'
                    }}
                </p>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <Button
                        v-if="props.typeFilter !== '' || props.statusFilter !== ''"
                        as-child
                        variant="outline"
                    >
                        <Link :href="routes.portalOrders" preserve-scroll>
                            View All Orders
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="routes.catalogue">
                            Explore Training Catalogue
                        </Link>
                    </Button>
                </div>
            </Card>

            <template v-else>
                <div class="space-y-3.5 md:hidden">
                    <Card
                        v-for="order in props.orders"
                        :key="order.id"
                        class="space-y-3 p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <Link
                                :href="order.detailUrl"
                                class="text-[13px] font-bold text-[#5942B5] tabular-nums hover:text-[#7057D9] hover:underline underline-offset-4"
                            >
                                {{ order.orderNumber }}
                            </Link>
                            <PortalStatusBadge
                                :status="order.statusValue"
                                :label="order.statusLabel"
                            />
                        </div>

                        <div>
                            <h2 class="text-[15px] font-bold text-[#282338]">
                                <Link
                                    :href="order.detailUrl"
                                    class="hover:text-[#5942B5] hover:underline underline-offset-4"
                                >
                                    {{ order.itemTitle }}
                                </Link>
                            </h2>
                            <p class="mt-0.5 text-[12.5px] text-[#625D6D]">
                                {{ order.orderTypeLabel }}
                            </p>
                        </div>

                        <dl class="space-y-2 border-t border-[#EEEAF2] pt-3 text-[13px]">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-[#625D6D]">Date</dt>
                                <dd class="text-[#292536] tabular-nums">
                                    {{ order.createdAtLabel }} MYT
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-[#625D6D]">Amount</dt>
                                <dd class="font-bold text-[#282338] tabular-nums">
                                    {{ order.totalLabel }}
                                </dd>
                            </div>
                        </dl>

                        <Button
                            as-child
                            :variant="order.statusValue === 'pending_payment' ? 'default' : 'outline'"
                            class="w-full"
                        >
                            <Link :href="order.detailUrl">
                                {{ order.statusValue === 'pending_payment' ? 'Complete Payment' : 'View Order Details' }}
                            </Link>
                        </Button>
                    </Card>
                </div>

                <Card class="hidden overflow-hidden md:block">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="pl-6">Order Number</TableHead>
                                <TableHead>Item</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Total</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="pr-6 text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="order in props.orders" :key="order.id">
                                <TableCell class="whitespace-nowrap pl-6">
                                    <Link
                                        :href="order.detailUrl"
                                        class="text-[13.5px] font-bold text-[#282338] tabular-nums hover:text-[#5942B5] hover:underline underline-offset-4"
                                    >
                                        {{ order.orderNumber }}
                                    </Link>
                                    <p class="mt-0.5 text-[12px] text-[#625D6D] tabular-nums">
                                        {{ order.createdAtLabel }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <Link
                                        :href="order.detailUrl"
                                        class="text-[13.5px] font-bold text-[#292536] hover:text-[#5942B5] hover:underline underline-offset-4"
                                    >
                                        {{ order.itemTitle }}
                                    </Link>
                                    <p
                                        v-if="order.itemCode"
                                        class="mt-0.5 text-[12px] text-[#625D6D] tabular-nums"
                                    >
                                        {{ order.itemCode }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <p class="text-[13px] text-[#292536]">
                                        {{ order.orderTypeLabel }}
                                    </p>
                                    <p
                                        v-if="order.gatewayLabel"
                                        class="mt-0.5 text-[12px] text-[#625D6D]"
                                    >
                                        {{ order.gatewayLabel }}
                                    </p>
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-[13.5px] font-bold text-[#282338] tabular-nums">
                                    {{ order.totalLabel }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap">
                                    <PortalStatusBadge
                                        :status="order.statusValue"
                                        :label="order.statusLabel"
                                    />
                                </TableCell>
                                <TableCell class="whitespace-nowrap pr-6 text-right">
                                    <Button
                                        as-child
                                        :variant="order.statusValue === 'pending_payment' ? 'default' : 'outline'"
                                        size="sm"
                                    >
                                        <Link :href="order.detailUrl">
                                            {{ order.statusValue === 'pending_payment' ? 'Complete Payment' : 'View Order' }}
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>

                <PublicPagination
                    :pagination="props.pagination"
                    item-label="orders"
                />
            </template>
        </div>
    </PortalLayout>
</template>
