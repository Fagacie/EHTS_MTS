<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft } from 'lucide-vue-next';
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

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Order Items</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div class="divide-y divide-[#EEEAF2]">
                                <div
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="flex flex-col gap-2 py-3.5 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="min-w-0">
                                        <p class="text-[14px] font-bold text-[#282338]">
                                            {{ item.title }}
                                        </p>
                                        <p class="mt-0.5 text-[12.5px] text-[#625D6D] tabular-nums">
                                            <span v-if="item.code">{{ item.code }} &middot; </span>
                                            <span>Quantity: {{ item.quantity }}</span>
                                        </p>
                                    </div>
                                    <div class="text-[14.5px] font-bold text-[#282338] tabular-nums sm:text-right">
                                        {{ item.lineTotalLabel }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t border-[#EEEAF2] pt-3.5">
                                <span class="text-[14px] font-bold text-[#282338]">Total Amount</span>
                                <span class="text-[16px] font-extrabold text-[#282338] tabular-nums">
                                    {{ order.totalLabel }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="order.bookings.length > 0 || order.entitlements.length > 0">
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Associated Booking &amp; Learning Access</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 pt-4">
                            <div
                                v-for="linkedBooking in order.bookings"
                                :key="`booking-${linkedBooking.id}`"
                                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] px-4 py-3"
                            >
                                <div>
                                    <p class="text-[13.5px] font-bold text-[#282338] tabular-nums">
                                        {{ linkedBooking.bookingReference }}
                                    </p>
                                    <p class="mt-0.5 text-[12.5px] text-[#625D6D]">
                                        Physical Session Reservation
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <PortalStatusBadge
                                        :status="linkedBooking.statusValue"
                                        :label="linkedBooking.statusLabel"
                                    />
                                    <Button as-child variant="outline" size="sm">
                                        <Link :href="linkedBooking.detailUrl">
                                            Open Booking
                                        </Link>
                                    </Button>
                                </div>
                            </div>

                            <div
                                v-for="entitlement in order.entitlements"
                                :key="`entitlement-${entitlement.id}`"
                                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] px-4 py-3"
                            >
                                <div>
                                    <p class="text-[13.5px] font-bold text-[#282338]">
                                        {{ entitlement.moduleTitle }}
                                    </p>
                                    <p class="mt-0.5 text-[12.5px] text-[#625D6D]">
                                        Digital module learning access
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <PortalStatusBadge
                                        :status="entitlement.statusValue"
                                        :label="entitlement.statusLabel"
                                    />
                                    <Button
                                        v-if="entitlement.isActive && entitlement.moduleUrl"
                                        as-child
                                        variant="outline"
                                        size="sm"
                                    >
                                        <Link :href="entitlement.moduleUrl">
                                            Open Module
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Payment History</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <p
                                v-if="order.payments.length === 0"
                                class="text-[13.5px] text-[#625D6D]"
                            >
                                No payment attempts have been recorded for this order yet.
                            </p>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="payment in order.payments"
                                    :key="payment.id"
                                    class="flex flex-col gap-3 rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[13.5px] font-bold text-[#282338] tabular-nums">
                                                {{ payment.paymentReference }}
                                            </span>
                                            <PortalStatusBadge
                                                :status="payment.statusValue"
                                                :label="payment.statusLabel"
                                            />
                                        </div>
                                        <p class="text-[12.5px] text-[#625D6D]">
                                            Channel: {{ payment.gatewayLabel }}
                                            <template v-if="payment.timestampPrefix && payment.timestampLabel">
                                                &middot; {{ payment.timestampPrefix }}
                                                <span class="tabular-nums">{{ payment.timestampLabel }}</span>
                                            </template>
                                        </p>
                                    </div>

                                    <div class="text-[14.5px] font-bold text-[#282338] tabular-nums">
                                        {{ payment.amountLabel }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="order.refunds.length > 0">
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Refund Records</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 pt-4">
                            <div
                                v-for="refund in order.refunds"
                                :key="refund.id"
                                class="flex flex-col gap-3 rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-[13.5px] font-bold text-[#282338] tabular-nums">
                                            {{ refund.refundReference }}
                                        </span>
                                        <PortalStatusBadge
                                            :status="refund.statusValue"
                                            :label="refund.statusLabel"
                                        />
                                    </div>
                                    <p class="text-[12.5px] text-[#625D6D]">
                                        {{ refund.methodLabel }}
                                        <template v-if="refund.reason">
                                            &middot; {{ refund.reason }}
                                        </template>
                                    </p>
                                </div>

                                <div class="text-[14.5px] font-bold text-[#282338] tabular-nums">
                                    {{ refund.amountLabel }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div>
                    <Card>
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
                                <div>
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Account Category</dt>
                                    <dd class="mt-0.5 text-[#292536]">{{ order.billing.customerTypeLabel }}</dd>
                                </div>
                                <div v-if="order.billing.companyName">
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Company / Organisation</dt>
                                    <dd class="mt-0.5 font-bold text-[#282338]">{{ order.billing.companyName }}</dd>
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
        </div>
    </PortalLayout>
</template>
