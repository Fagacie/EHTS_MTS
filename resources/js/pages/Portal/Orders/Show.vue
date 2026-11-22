<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, ArrowUpRight } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalOrderShowProps } from '@/types/etms';

const props = defineProps<PortalOrderShowProps>();

const { routes } = useRoutes();
const paying = ref(false);

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
</script>

<template>
    <Head :title="`Order ${order.orderNumber}`" />

    <PortalLayout :breadcrumb="order.orderNumber">
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="routes.portalOrders"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-neutral-600 transition-colors duration-150 hover:text-[#0F4C81]"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Back to Orders &amp; Payments</span>
                </Link>

                <PortalStatusBadge
                    :status="order.statusValue"
                    :label="order.statusLabel"
                />
            </div>

            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="font-mono text-2xl font-semibold tracking-tight text-neutral-900 tabular-nums sm:text-3xl">
                        {{ order.orderNumber }}
                    </h1>
                    <p class="mt-1 text-sm text-neutral-500 tabular-nums">
                        {{ order.orderTypeLabel }} &middot; Placed {{ order.createdAtLabel }}
                    </p>
                </div>

                <Button
                    v-if="order.canPayOnline"
                    type="button"
                    class="h-10 shrink-0 rounded-[6px] bg-[#0F4C81] px-5 text-sm font-medium text-white hover:bg-[#0F4C81]/90"
                    :disabled="paying"
                    @click="completeOnlinePayment"
                >
                    {{ paying ? 'Connecting...' : 'Complete Online Payment' }}
                </Button>
            </header>

            <div
                v-if="order.isPendingPayment"
                role="status"
                class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/70 p-4 text-sm text-neutral-800"
            >
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" aria-hidden="true" />
                <div>
                    <p class="font-semibold text-neutral-900">Awaiting Payment Confirmation</p>
                    <p class="mt-0.5 text-neutral-600">
                        This order will be marked as paid once verified payment confirmation is received.
                        <template v-if="order.expiresAtLabel">
                            Hold window ends <span class="font-medium text-neutral-900 tabular-nums">{{ order.expiresAtLabel }}</span>.
                        </template>
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                <div class="divide-y divide-neutral-200 rounded-lg border border-neutral-200 bg-white lg:col-span-8">
                    <section class="p-6">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                            Items
                        </h2>
                        <div class="mt-4 divide-y divide-neutral-100">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-neutral-900">
                                        {{ item.title }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-neutral-500 tabular-nums">
                                        <span v-if="item.code" class="font-mono">{{ item.code }} &middot; </span>
                                        <span>Qty {{ item.quantity }} &times; {{ item.unitPriceLabel }}</span>
                                    </p>
                                </div>
                                <span class="shrink-0 text-sm font-semibold text-neutral-900 tabular-nums">
                                    {{ item.lineTotalLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-neutral-200 pt-4">
                            <span class="text-sm font-semibold text-neutral-900">Total</span>
                            <span class="text-lg font-bold text-neutral-900 tabular-nums">
                                {{ order.totalLabel }}
                            </span>
                        </div>
                    </section>

                    <section v-if="order.bookings.length > 0 || order.entitlements.length > 0" class="p-6">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                            Fulfilment
                        </h2>
                        <div class="mt-4 divide-y divide-neutral-100">
                            <div
                                v-for="linkedBooking in order.bookings"
                                :key="`booking-${linkedBooking.id}`"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <p class="font-mono text-sm font-semibold text-neutral-900 tabular-nums">
                                        {{ linkedBooking.bookingReference }}
                                    </p>
                                    <p class="text-xs text-neutral-500">Physical Session Reservation</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <PortalStatusBadge
                                        :status="linkedBooking.statusValue"
                                        :label="linkedBooking.statusLabel"
                                    />
                                    <Link
                                        :href="linkedBooking.detailUrl"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-[#0F4C81] hover:underline"
                                    >
                                        <span>Open Booking</span>
                                        <ArrowUpRight class="h-3.5 w-3.5" aria-hidden="true" />
                                    </Link>
                                </div>
                            </div>

                            <div
                                v-for="entitlement in order.entitlements"
                                :key="`entitlement-${entitlement.id}`"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-neutral-900">
                                        {{ entitlement.moduleTitle }}
                                    </p>
                                    <p class="text-xs text-neutral-500">{{ entitlement.accessLabel }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <PortalStatusBadge
                                        :status="entitlement.statusValue"
                                        :label="entitlement.statusLabel"
                                    />
                                    <Link
                                        v-if="entitlement.isActive && entitlement.moduleUrl"
                                        :href="entitlement.moduleUrl"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-[#0F4C81] hover:underline"
                                    >
                                        <span>Open Module</span>
                                        <ArrowUpRight class="h-3.5 w-3.5" aria-hidden="true" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="p-6">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                            Payments
                        </h2>
                        <p v-if="order.payments.length === 0" class="mt-3 text-sm text-neutral-500">
                            No payment attempts recorded yet.
                        </p>
                        <div v-else class="mt-4 divide-y divide-neutral-100">
                            <div
                                v-for="payment in order.payments"
                                :key="payment.id"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-sm font-medium text-neutral-900 tabular-nums">
                                            {{ payment.paymentReference }}
                                        </span>
                                        <PortalStatusBadge
                                            :status="payment.statusValue"
                                            :label="payment.statusLabel"
                                        />
                                    </div>
                                    <p class="mt-0.5 text-xs text-neutral-500 tabular-nums">
                                        {{ payment.gatewayLabel }}
                                        <template v-if="payment.timestampPrefix && payment.timestampLabel">
                                            &middot; {{ payment.timestampPrefix }} {{ payment.timestampLabel }}
                                        </template>
                                    </p>
                                </div>
                                <span class="text-sm font-semibold text-neutral-900 tabular-nums">
                                    {{ payment.amountLabel }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <section v-if="order.refunds.length > 0" class="p-6">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                            Refunds
                        </h2>
                        <div class="mt-4 divide-y divide-neutral-100">
                            <div
                                v-for="refund in order.refunds"
                                :key="refund.id"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-sm font-medium text-neutral-900 tabular-nums">
                                            {{ refund.refundReference }}
                                        </span>
                                        <PortalStatusBadge
                                            :status="refund.statusValue"
                                            :label="refund.statusLabel"
                                        />
                                    </div>
                                    <p class="mt-0.5 text-xs text-neutral-500">
                                        {{ refund.methodLabel }}
                                        <template v-if="refund.reason"> &middot; {{ refund.reason }}</template>
                                    </p>
                                </div>
                                <span class="text-sm font-semibold text-neutral-900 tabular-nums">
                                    {{ refund.amountLabel }}
                                </span>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="rounded-lg border border-neutral-200 bg-white p-6 lg:col-span-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                        Billing Details
                    </h2>
                    <dl class="mt-4 space-y-3.5 text-sm">
                        <div>
                            <dt class="text-xs text-neutral-500">Name</dt>
                            <dd class="mt-0.5 font-medium text-neutral-900">{{ order.billing.customerName }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Email</dt>
                            <dd class="mt-0.5 break-all text-neutral-800">{{ order.billing.customerEmail }}</dd>
                        </div>
                        <div v-if="order.billing.customerPhone">
                            <dt class="text-xs text-neutral-500">Phone</dt>
                            <dd class="mt-0.5 text-neutral-800 tabular-nums">{{ order.billing.customerPhone }}</dd>
                        </div>
                        <div v-if="order.billing.billingAddress">
                            <dt class="text-xs text-neutral-500">Billing Address</dt>
                            <dd class="mt-0.5 leading-relaxed text-neutral-700">
                                {{ order.billing.billingAddress }}
                            </dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </div>
    </PortalLayout>
</template>
