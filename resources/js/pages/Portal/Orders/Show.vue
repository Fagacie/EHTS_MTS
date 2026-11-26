<script setup lang="ts">
import { computed, ref } from 'vue';
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
    AlertCircle,
    ArrowLeft,
    ArrowUpRight,
    BookOpen,
    CalendarCheck,
    Check,
    Copy,
    CreditCard,
    Download,
    FileText,
    X,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalOrderShowProps } from '@/types/etms';

const props = defineProps<PortalOrderShowProps>();

const { routes } = useRoutes();
const paying = ref(false);
const copiedKey = ref<string | null>(null);
const receiptModalOpen = ref(false);

function completeOnlinePayment(): void {
    if (paying.value) {
        return;
    }

    paying.value = true;
    router.post(
        props.order.payUrl,
        {},
        {
            onFinish: () => {
                paying.value = false;
            },
        },
    );
}

async function copyText(key: string, value: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(value);
        copiedKey.value = key;
        window.setTimeout(() => {
            if (copiedKey.value === key) {
                copiedKey.value = null;
            }
        }, 1800);
    } catch {
        // Clipboard API unavailable
    }
}

function statusTone(statusValue: string): StatusTone {
    if (['paid', 'completed', 'confirmed', 'active', 'succeeded'].includes(statusValue)) return 'success';
    if (['pending_payment', 'pending', 'processing', 'partially_refunded'].includes(statusValue)) return 'warning';
    if (['failed', 'cancelled', 'expired', 'refunded', 'revoked'].includes(statusValue)) return 'danger';
    return 'primary';
}

const isPaid = computed(() => ['paid', 'completed'].includes(props.order.statusValue));
const latestPayment = computed(() => props.order.payments[0] ?? null);
</script>

<template>
    <Head :title="`Order ${order.orderNumber}`" />

    <PortalLayout :breadcrumb="order.orderNumber">
        <div class="mx-auto max-w-[1280px] space-y-6">
            <!-- Quiet Back Link -->
            <div>
                <Link
                    :href="routes.portalOrders"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Orders &amp; Payments</span>
                </Link>
            </div>

            <!-- Page Header (Directly on canvas — clean editorial hierarchy) -->
            <header class="flex flex-col gap-4 border-b border-line pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="tabular-nums-mono text-2xl font-bold tracking-tight text-navy-950 sm:text-[26px]">
                            {{ order.orderNumber }}
                        </h1>

                        <button
                            type="button"
                            class="inline-flex size-7 items-center justify-center rounded-lg text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                            :title="copiedKey === 'order' ? 'Copied' : 'Copy order number'"
                            @click="copyText('order', order.orderNumber)"
                        >
                            <Check v-if="copiedKey === 'order'" class="size-3.5 text-success-600" aria-hidden="true" />
                            <Copy v-else class="size-3.5" aria-hidden="true" />
                        </button>

                        <StatusBadge :tone="statusTone(order.statusValue)" dot>
                            {{ order.statusLabel }}
                        </StatusBadge>
                    </div>

                    <p class="tabular text-sm text-muted">
                        {{ order.orderTypeLabel }} &middot; Placed {{ order.createdAtLabel }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-sand-50"
                        @click="receiptModalOpen = true"
                    >
                        <FileText class="size-3.5 text-muted" aria-hidden="true" />
                        <span>Preview Receipt</span>
                    </button>

                    <a
                        :href="order.receiptUrl"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-navy-950 px-4 text-xs font-semibold text-white transition-colors hover:bg-navy-900"
                    >
                        <Download class="size-3.5" aria-hidden="true" />
                        <span>Download Receipt</span>
                    </a>

                    <AppButton
                        v-if="order.canPayOnline"
                        variant="warm"
                        size="sm"
                        :disabled="paying"
                        @click="completeOnlinePayment"
                    >
                        <CreditCard class="size-3.5" aria-hidden="true" />
                        <span>{{ paying ? 'Connecting...' : 'Complete Payment' }}</span>
                    </AppButton>
                </div>
            </header>

            <!-- Pending Payment Notice (only if pending) -->
            <div
                v-if="order.isPendingPayment"
                role="status"
                class="flex flex-col gap-3 rounded-xl bg-warning-50 px-5 py-4 ring-1 ring-warning-600/20 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 size-4 shrink-0 text-warning-700" aria-hidden="true" />
                    <div class="text-sm">
                        <p class="font-semibold text-navy-950">Payment required to confirm this order</p>
                        <p class="mt-0.5 text-xs text-ink/80">
                            Complete checkout online to activate your seat or module access.
                            <template v-if="order.expiresAtLabel">
                                Hold expires <span class="tabular font-semibold">{{ order.expiresAtLabel }}</span>.
                            </template>
                        </p>
                    </div>
                </div>

                <AppButton
                    v-if="order.canPayOnline"
                    variant="warm"
                    size="sm"
                    class="shrink-0"
                    :disabled="paying"
                    @click="completeOnlinePayment"
                >
                    <span>{{ paying ? 'Connecting...' : 'Pay Now' }}</span>
                </AppButton>
            </div>

            <!-- Clean 12-Column Asymmetric Content Layout -->
            <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
                <!-- Primary Column (8 cols): Single seamless white surface -->
                <div class="rounded-xl bg-white ring-1 ring-neutral-200/80 lg:col-span-8">
                    <!-- Items Table -->
                    <div class="p-6">
                        <h2 class="text-sm font-semibold text-navy-950">Items</h2>

                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-line text-xs font-medium text-muted">
                                        <th class="pb-2.5 pr-4 font-medium">Description</th>
                                        <th class="pb-2.5 px-3 text-center font-medium">Qty</th>
                                        <th class="pb-2.5 px-3 text-right font-medium">Unit price</th>
                                        <th class="pb-2.5 pl-3 text-right font-medium">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/60">
                                    <tr v-for="item in order.items" :key="item.id">
                                        <td class="py-3.5 pr-4">
                                            <p class="text-sm font-medium text-navy-950">{{ item.title }}</p>
                                            <p v-if="item.code" class="tabular-nums-mono mt-0.5 text-xs text-muted">
                                                {{ item.code }}
                                            </p>
                                        </td>
                                        <td class="tabular py-3.5 px-3 text-center text-sm text-ink">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="tabular py-3.5 px-3 text-right text-sm text-muted">
                                            {{ item.unitPriceLabel }}
                                        </td>
                                        <td class="tabular py-3.5 pl-3 text-right text-sm font-semibold text-navy-950">
                                            {{ item.lineTotalLabel }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals -->
                        <div class="mt-4 flex justify-end border-t border-line pt-4">
                            <dl class="w-full max-w-[260px] space-y-1.5 text-sm">
                                <div class="flex items-center justify-between text-muted">
                                    <dt>Subtotal</dt>
                                    <dd class="tabular text-ink">{{ order.totalLabel }}</dd>
                                </div>
                                <div class="flex items-center justify-between text-muted">
                                    <dt>Tax</dt>
                                    <dd class="tabular text-ink">{{ order.currency }} 0.00</dd>
                                </div>
                                <div class="flex items-center justify-between border-t border-line pt-2 font-semibold text-navy-950">
                                    <dt>{{ isPaid ? 'Total paid' : 'Total due' }}</dt>
                                    <dd class="tabular text-base font-bold">{{ order.totalLabel }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Linked Access (Bookings & Modules) -->
                    <div
                        v-if="order.bookings.length > 0 || order.entitlements.length > 0"
                        class="border-t border-line p-6"
                    >
                        <h2 class="text-sm font-semibold text-navy-950">Fulfilment</h2>

                        <div class="mt-3 divide-y divide-line/60">
                            <div
                                v-for="linkedBooking in order.bookings"
                                :key="`booking-${linkedBooking.id}`"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div class="flex items-center gap-3">
                                    <CalendarCheck class="size-4 shrink-0 text-primary-600" aria-hidden="true" />
                                    <div>
                                        <p class="tabular-nums-mono text-sm font-medium text-navy-950">
                                            {{ linkedBooking.bookingReference }}
                                        </p>
                                        <p class="text-xs text-muted">Live training session reservation</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <StatusBadge :tone="statusTone(linkedBooking.statusValue)" dot>
                                        {{ linkedBooking.statusLabel }}
                                    </StatusBadge>
                                    <Link
                                        :href="linkedBooking.detailUrl"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 transition-colors hover:text-primary-700"
                                    >
                                        <span>Open booking</span>
                                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                                    </Link>
                                </div>
                            </div>

                            <div
                                v-for="entitlement in order.entitlements"
                                :key="`entitlement-${entitlement.id}`"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div class="flex items-center gap-3">
                                    <BookOpen class="size-4 shrink-0 text-accent-600" aria-hidden="true" />
                                    <div>
                                        <p class="text-sm font-medium text-navy-950">
                                            {{ entitlement.moduleTitle }}
                                        </p>
                                        <p class="text-xs text-muted">{{ entitlement.accessLabel }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <StatusBadge :tone="statusTone(entitlement.statusValue)" dot>
                                        {{ entitlement.statusLabel }}
                                    </StatusBadge>
                                    <Link
                                        v-if="entitlement.isActive && entitlement.moduleUrl"
                                        :href="entitlement.moduleUrl"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 transition-colors hover:text-primary-700"
                                    >
                                        <span>Open module</span>
                                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment & Refund History -->
                    <div class="border-t border-line p-6">
                        <h2 class="text-sm font-semibold text-navy-950">Transactions</h2>

                        <p v-if="order.payments.length === 0" class="mt-2.5 text-sm text-muted">
                            No payment attempts recorded yet.
                        </p>

                        <div v-else class="mt-3 divide-y divide-line/60">
                            <div
                                v-for="payment in order.payments"
                                :key="payment.id"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="tabular-nums-mono inline-flex items-center gap-1.5 text-sm font-medium text-navy-950 transition-colors hover:text-primary-600"
                                            title="Copy payment reference"
                                            @click="copyText(`pay-${payment.id}`, payment.paymentReference)"
                                        >
                                            <span>{{ payment.paymentReference }}</span>
                                            <Check
                                                v-if="copiedKey === `pay-${payment.id}`"
                                                class="size-3 text-success-600"
                                                aria-hidden="true"
                                            />
                                            <Copy v-else class="size-3 text-muted/60" aria-hidden="true" />
                                        </button>
                                        <StatusBadge :tone="statusTone(payment.statusValue)" dot>
                                            {{ payment.statusLabel }}
                                        </StatusBadge>
                                    </div>
                                    <p class="tabular mt-0.5 text-xs text-muted">
                                        {{ payment.gatewayLabel }}
                                        <template v-if="payment.timestampPrefix && payment.timestampLabel">
                                            &middot; {{ payment.timestampPrefix }} {{ payment.timestampLabel }}
                                        </template>
                                    </p>
                                </div>
                                <span class="tabular text-sm font-semibold text-navy-950">
                                    {{ payment.amountLabel }}
                                </span>
                            </div>
                        </div>

                        <div v-if="order.refunds.length > 0" class="mt-5 border-t border-line/70 pt-4">
                            <h3 class="text-xs font-semibold text-danger-700">Refunds</h3>
                            <div class="mt-2.5 divide-y divide-line/60">
                                <div
                                    v-for="refund in order.refunds"
                                    :key="refund.id"
                                    class="flex flex-wrap items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0"
                                >
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="tabular-nums-mono text-sm font-medium text-navy-950">
                                                {{ refund.refundReference }}
                                            </span>
                                            <StatusBadge :tone="statusTone(refund.statusValue)" dot>
                                                {{ refund.statusLabel }}
                                            </StatusBadge>
                                        </div>
                                        <p class="mt-0.5 text-xs text-muted">
                                            {{ refund.methodLabel }}
                                            <template v-if="refund.reason"> &middot; {{ refund.reason }}</template>
                                        </p>
                                    </div>
                                    <span class="tabular text-sm font-semibold text-navy-950">
                                        {{ refund.amountLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (4 cols): Quiet Metadata Summary -->
                <aside class="rounded-xl bg-white p-6 ring-1 ring-neutral-200/80 lg:col-span-4">
                    <div>
                        <p class="text-xs font-medium text-muted">
                            {{ isPaid ? 'Amount paid' : 'Amount due' }}
                        </p>
                        <p class="tabular mt-1 text-2xl font-bold tracking-tight text-navy-950">
                            {{ order.totalLabel }}
                        </p>
                    </div>

                    <dl class="mt-5 space-y-3 border-t border-line pt-5 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-xs text-muted">Receipt no.</dt>
                            <dd class="tabular-nums-mono text-xs font-semibold text-navy-950">
                                RCT-{{ order.orderNumber }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-xs text-muted">Payment method</dt>
                            <dd class="text-xs font-medium text-navy-950">
                                {{ latestPayment?.gatewayLabel || 'Online Gateway' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-xs text-muted">Currency</dt>
                            <dd class="text-xs font-medium text-navy-950">{{ order.currency }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 border-t border-line pt-5">
                        <h2 class="text-xs font-semibold text-navy-950">Customer details</h2>
                        <dl class="mt-3 space-y-2.5 text-sm">
                            <div>
                                <dt class="text-xs text-muted">Name</dt>
                                <dd class="mt-0.5 font-medium text-navy-950">
                                    {{ order.billing.customerName }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted">Email</dt>
                                <dd class="mt-0.5 break-all text-xs text-ink">
                                    {{ order.billing.customerEmail }}
                                </dd>
                            </div>
                            <div v-if="order.billing.customerPhone">
                                <dt class="text-xs text-muted">Phone</dt>
                                <dd class="tabular mt-0.5 text-xs text-ink">
                                    {{ order.billing.customerPhone }}
                                </dd>
                            </div>
                            <div v-if="order.billing.billingAddress">
                                <dt class="text-xs text-muted">Billing address</dt>
                                <dd class="mt-0.5 text-xs leading-relaxed text-ink/85">
                                    {{ order.billing.billingAddress }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </div>

        <!-- Reka UI Official Receipt Preview Modal -->
        <DialogRoot v-model:open="receiptModalOpen">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/60 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 flex max-h-[90vh] w-[calc(100vw-2rem)] max-w-2xl -translate-x-1/2 -translate-y-1/2 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-neutral-200 focus:outline-none"
                >
                    <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
                        <DialogTitle class="text-sm font-semibold text-navy-950">
                            Receipt Preview &middot; RCT-{{ order.orderNumber }}
                        </DialogTitle>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                            aria-label="Close receipt preview"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </DialogClose>
                    </div>

                    <div class="overflow-y-auto bg-sand-50/70 p-4 sm:p-6">
                        <div class="mx-auto overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-neutral-200/90">
                            <div class="flex h-1.5 w-full">
                                <div class="w-3/5 bg-[#0b2a5b]" />
                                <div class="w-1/4 bg-[#0f62a4]" />
                                <div class="w-[15%] bg-[#2d8a3e]" />
                            </div>

                            <div class="space-y-6 p-6 sm:p-8">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <img
                                            src="/images/ecohydrotech-logo.png"
                                            alt="Ecohydrotech Solutions Sdn. Bhd."
                                            class="h-11 w-auto object-contain"
                                        />
                                        <p class="mt-2 text-[11px] leading-relaxed text-muted">
                                            Ecohydrotech Solutions Sdn. Bhd.<br />
                                            Hydrology &amp; Water Resources Training Centre
                                        </p>
                                    </div>
                                    <div class="sm:text-right">
                                        <p class="text-lg font-extrabold tracking-wide text-[#0b2a5b]">
                                            {{ isPaid ? 'OFFICIAL RECEIPT' : 'ORDER RECEIPT' }}
                                        </p>
                                        <div class="mt-1.5">
                                            <StatusBadge :tone="statusTone(order.statusValue)" dot>
                                                {{ order.statusLabel }}
                                            </StatusBadge>
                                        </div>
                                        <p class="tabular-nums-mono mt-2 text-[11px] text-muted">
                                            RCT-{{ order.orderNumber }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid gap-4 rounded-xl bg-sand-50 p-4 text-xs ring-1 ring-line/70 sm:grid-cols-2">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-[#0f62a4]">
                                            Received From
                                        </p>
                                        <p class="mt-1 font-bold text-navy-950">{{ order.billing.customerName }}</p>
                                        <p class="text-muted">{{ order.billing.customerEmail }}</p>
                                        <p v-if="order.billing.customerPhone" class="tabular text-muted">
                                            {{ order.billing.customerPhone }}
                                        </p>
                                    </div>
                                    <div class="space-y-1 sm:border-l sm:border-line sm:pl-4">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-[#0f62a4]">
                                            Receipt Details
                                        </p>
                                        <div class="flex justify-between gap-2">
                                            <span class="text-muted">Order Ref:</span>
                                            <span class="tabular-nums-mono font-semibold text-navy-950">{{ order.orderNumber }}</span>
                                        </div>
                                        <div class="flex justify-between gap-2">
                                            <span class="text-muted">Date:</span>
                                            <span class="tabular font-semibold text-navy-950">{{ order.createdAtLabel }}</span>
                                        </div>
                                        <div v-if="latestPayment" class="flex justify-between gap-2">
                                            <span class="text-muted">Gateway:</span>
                                            <span class="font-semibold text-navy-950">{{ latestPayment.gatewayLabel }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-lg ring-1 ring-line">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-[#0b2a5b] text-[10px] uppercase tracking-wider text-white">
                                            <tr>
                                                <th class="px-3 py-2.5">Description</th>
                                                <th class="px-3 py-2.5 text-center">Qty</th>
                                                <th class="px-3 py-2.5 text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-line">
                                            <tr v-for="item in order.items" :key="`preview-${item.id}`">
                                                <td class="px-3 py-2.5">
                                                    <p class="font-semibold text-navy-950">{{ item.title }}</p>
                                                    <p v-if="item.code" class="tabular-nums-mono text-[10px] text-muted">{{ item.code }}</p>
                                                </td>
                                                <td class="tabular px-3 py-2.5 text-center text-ink">{{ item.quantity }}</td>
                                                <td class="tabular px-3 py-2.5 text-right font-bold text-navy-950">{{ item.lineTotalLabel }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex items-center justify-between rounded-lg bg-[#0b2a5b] px-4 py-3 text-white">
                                    <span class="text-xs font-semibold uppercase tracking-wider">
                                        {{ isPaid ? 'Total Paid' : 'Total Due' }}
                                    </span>
                                    <span class="tabular text-base font-extrabold">{{ order.totalLabel }}</span>
                                </div>

                                <p class="border-t border-line pt-3 text-center text-[10.5px] text-muted">
                                    This is a computer-generated official receipt from Ecohydrotech Solutions Sdn. Bhd. No signature is required.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 border-t border-line bg-white px-5 py-3.5">
                        <DialogClose
                            class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                        >
                            Close
                        </DialogClose>
                        <a
                            :href="order.receiptUrl"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-navy-950 px-4 text-xs font-semibold text-white transition-colors hover:bg-navy-900"
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>Download PDF Receipt</span>
                        </a>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </PortalLayout>
</template>
