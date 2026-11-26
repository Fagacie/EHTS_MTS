<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, ArrowUpRight } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
        <div class="mx-auto max-w-[1440px] space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="routes.portalOrders"
                    class="inline-flex items-center gap-1.5 text-[13.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
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
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] tabular-nums sm:text-[26px]">
                        Order {{ order.orderNumber }}
                    </h1>
                    <p class="mt-1 text-[13.5px] text-[#625D6D] tabular-nums">
                        {{ order.orderTypeLabel }} &middot; Placed {{ order.createdAtLabel }}
                    </p>
                </div>

                <Button
                    v-if="order.canPayOnline"
                    type="button"
                    class="shrink-0"
                    :disabled="paying"
                    @click="completeOnlinePayment"
                >
                    {{ paying ? 'Connecting...' : 'Complete Online Payment' }}
                </Button>
            </header>

            <Card
                v-if="order.isPendingPayment"
                role="status"
                class="border-[#E8CFA1] bg-[#FBF2E3] p-4 shadow-none sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#8B5A17]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536]">
                            Awaiting Payment Confirmation
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            This order will be marked as paid once verified payment confirmation is received from the payment gateway.
                            <template v-if="order.expiresAtLabel">
                                Hold window ends <span class="font-semibold tabular-nums">{{ order.expiresAtLabel }}</span>.
                            </template>
                        </p>
                    </div>
                </div>
            </Card>

            <div class="grid gap-5 lg:grid-cols-12 lg:items-start">
                <Card class="divide-y divide-[#EEEAF2] lg:col-span-8">
                    <section class="p-5 sm:p-6">
                        <h2 class="text-[12px] font-bold uppercase tracking-wider text-[#625D6D]">
                            Order Items
                        </h2>
                        <div class="mt-4 divide-y divide-[#EEEAF2]">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                            >
                                <div class="min-w-0">
                                    <p class="text-[14.5px] font-bold text-[#282338]">
                                        {{ item.title }}
                                    </p>
                                    <p class="mt-0.5 text-[12.5px] text-[#625D6D] tabular-nums">
                                        <span v-if="item.code" class="font-mono">{{ item.code }} &middot; </span>
                                        <span>Qty {{ item.quantity }} &times; {{ item.unitPriceLabel }}</span>
                                    </p>
                                </div>
                                <span class="shrink-0 text-[14.5px] font-bold text-[#282338] tabular-nums">
                                    {{ item.lineTotalLabel }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-[#EEEAF2] pt-3.5">
                            <span class="text-[14px] font-bold text-[#282338]">Total Amount</span>
                            <span class="text-[16px] font-extrabold text-[#282338] tabular-nums">
                                {{ order.totalLabel }}
                            </span>
                        </div>
                    </section>

                    <section v-if="order.bookings.length > 0 || order.entitlements.length > 0" class="p-5 sm:p-6">
                        <h2 class="text-[12px] font-bold uppercase tracking-wider text-[#625D6D]">
                            Fulfilment
                        </h2>
                        <div class="mt-4 divide-y divide-[#EEEAF2]">
                            <div
                                v-for="linkedBooking in order.bookings"
                                :key="`booking-${linkedBooking.id}`"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <p class="text-[13.5px] font-bold text-[#282338] tabular-nums">
                                        {{ linkedBooking.bookingReference }}
                                    </p>
                                    <p class="mt-0.5 text-[12.5px] text-[#625D6D]">Physical Session Reservation</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <PortalStatusBadge
                                        :status="linkedBooking.statusValue"
                                        :label="linkedBooking.statusLabel"
                                    />
                                    <Link
                                        :href="linkedBooking.detailUrl"
                                        class="inline-flex items-center gap-1 text-[12.5px] font-bold text-[#5942B5] hover:text-[#7057D9] hover:underline underline-offset-4"
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
                                    <p class="text-[13.5px] font-bold text-[#282338]">
                                        {{ entitlement.moduleTitle }}
                                    </p>
                                    <p class="mt-0.5 text-[12.5px] text-[#625D6D]">{{ entitlement.accessLabel }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <PortalStatusBadge
                                        :status="entitlement.statusValue"
                                        :label="entitlement.statusLabel"
                                    />
                                    <Link
                                        v-if="entitlement.isActive && entitlement.moduleUrl"
                                        :href="entitlement.moduleUrl"
                                        class="inline-flex items-center gap-1 text-[12.5px] font-bold text-[#5942B5] hover:text-[#7057D9] hover:underline underline-offset-4"
                                    >
                                        <span>Open Module</span>
                                        <ArrowUpRight class="h-3.5 w-3.5" aria-hidden="true" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="p-5 sm:p-6">
                        <h2 class="text-[12px] font-bold uppercase tracking-wider text-[#625D6D]">
                            Payment History
                        </h2>
                        <p v-if="order.payments.length === 0" class="mt-3 text-[13.5px] text-[#625D6D]">
                            No payment attempts have been recorded for this order yet.
                        </p>
                        <div v-else class="mt-4 divide-y divide-[#EEEAF2]">
                            <div
                                v-for="payment in order.payments"
                                :key="payment.id"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[13.5px] font-bold text-[#282338] tabular-nums">
                                            {{ payment.paymentReference }}
                                        </span>
                                        <PortalStatusBadge
                                            :status="payment.statusValue"
                                            :label="payment.statusLabel"
                                        />
                                    </div>
                                    <p class="mt-0.5 text-[12.5px] text-[#625D6D] tabular-nums">
                                        {{ payment.gatewayLabel }}
                                        <template v-if="payment.timestampPrefix && payment.timestampLabel">
                                            &middot; {{ payment.timestampPrefix }} {{ payment.timestampLabel }}
                                        </template>
                                    </p>
                                </div>
                                <span class="text-[14.5px] font-bold text-[#282338] tabular-nums">
                                    {{ payment.amountLabel }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <section v-if="order.refunds.length > 0" class="p-5 sm:p-6">
                        <h2 class="text-[12px] font-bold uppercase tracking-wider text-[#625D6D]">
                            Refund Records
                        </h2>
                        <div class="mt-4 divide-y divide-[#EEEAF2]">
                            <div
                                v-for="refund in order.refunds"
                                :key="refund.id"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[13.5px] font-bold text-[#282338] tabular-nums">
                                            {{ refund.refundReference }}
                                        </span>
                                        <PortalStatusBadge
                                            :status="refund.statusValue"
                                            :label="refund.statusLabel"
                                        />
                                    </div>
                                    <p class="mt-0.5 text-[12.5px] text-[#625D6D]">
                                        {{ refund.methodLabel }}
                                        <template v-if="refund.reason"> &middot; {{ refund.reason }}</template>
                                    </p>
                                </div>
                                <span class="text-[14.5px] font-bold text-[#282338] tabular-nums">
                                    {{ refund.amountLabel }}
                                </span>
                            </div>
                        </div>
                    </section>
                </Card>

                <Card class="lg:col-span-4">
                    <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                        <CardTitle>Billing &amp; Trainee Details</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-4">
                        <dl class="space-y-3 text-[13.5px]">
                            <div>
                                <dt class="text-[12.5px] font-semibold text-[#625D6D]">Customer Name</dt>
                                <dd class="mt-0.5 font-bold text-[#282338]">{{ order.billing.customerName }}</dd>
                            </div>
                            <div>
                                <dt class="text-[12.5px] font-semibold text-[#625D6D]">Email</dt>
                                <dd class="mt-0.5 break-all text-[#292536]">{{ order.billing.customerEmail }}</dd>
                            </div>
                            <div v-if="order.billing.customerPhone">
                                <dt class="text-[12.5px] font-semibold text-[#625D6D]">Phone</dt>
                                <dd class="mt-0.5 text-[#292536] tabular-nums">{{ order.billing.customerPhone }}</dd>
                            </div>
                            <div v-if="order.billing.billingAddress">
                                <dt class="text-[12.5px] font-semibold text-[#625D6D]">Billing Address</dt>
                                <dd class="mt-0.5 text-[13px] leading-relaxed text-[#403B4B]">
                                    {{ order.billing.billingAddress }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </div>
    </PortalLayout>
</template>
