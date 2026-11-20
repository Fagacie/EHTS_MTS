<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, CheckCircle2 } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { CheckoutReturnProps } from '@/types/etms';

defineProps<CheckoutReturnProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head :title="`Payment Status — ${order.orderNumber}`" />

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

            <header>
                <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] tabular-nums sm:text-[26px]">
                    Order {{ order.orderNumber }}
                </h1>
                <p class="mt-1 text-[13.5px] text-[#625D6D]">
                    Payment status update for your {{ order.orderTypeLabel }}.
                </p>
            </header>

            <Card
                v-if="order.isPaid"
                role="status"
                class="flex flex-col gap-4 border-[#B8DFC9] bg-[#E8F4ED] p-4 shadow-none sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-[#28734F]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536]">Payment Confirmed</p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            Thank you! Your payment has been verified and confirmed. Your booking or learning module access is now active.
                        </p>
                    </div>
                </div>

                <Button as-child size="sm" class="shrink-0">
                    <Link :href="order.isModulePurchase ? routes.portalModules : routes.portalBookings">
                        {{ order.isModulePurchase ? 'Go to My Learning' : 'View My Bookings' }}
                    </Link>
                </Button>
            </Card>

            <Card
                v-else-if="order.isPendingPayment"
                role="status"
                class="flex flex-col gap-4 border-[#E8CFA1] bg-[#FBF2E3] p-4 shadow-none sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#8B5A17]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536]">
                            Awaiting Server-Side Payment Confirmation
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            {{
                                redirectReportedPaid
                                    ? 'Your payment gateway redirect reported a completed transaction. We are awaiting verified webhook confirmation before activating your seat or module entitlement.'
                                    : 'This order is still waiting for verified payment confirmation from the payment gateway.'
                            }}
                        </p>
                    </div>
                </div>

                <Button as-child variant="outline" size="sm" class="shrink-0">
                    <Link :href="refreshUrl">
                        Refresh Status
                    </Link>
                </Button>
            </Card>

            <Card
                v-else
                role="status"
                class="flex flex-col gap-4 border-[#ECC4CA] bg-[#FBECEE] p-4 shadow-none sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#A33C48]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536]">
                            Payment {{ order.statusLabel }}
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            This transaction was not completed ({{ order.statusLabel }}). Please inspect your order details or browse available programmes to register again.
                        </p>
                    </div>
                </div>

                <Button as-child size="sm" class="shrink-0">
                    <Link :href="routes.catalogue">
                        Browse Training
                    </Link>
                </Button>
            </Card>

            <Card>
                <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                    <CardTitle>Transaction Overview</CardTitle>
                </CardHeader>
                <CardContent class="pt-4">
                    <dl class="grid gap-5 text-[13.5px] sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-[12.5px] font-semibold text-[#625D6D]">Order Number</dt>
                            <dd class="mt-1 font-bold text-[#282338] tabular-nums">
                                {{ order.orderNumber }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[12.5px] font-semibold text-[#625D6D]">Order Type</dt>
                            <dd class="mt-1 font-semibold text-[#292536]">
                                {{ order.orderTypeLabel }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[12.5px] font-semibold text-[#625D6D]">Total Amount</dt>
                            <dd class="mt-1 font-bold text-[#282338] tabular-nums">
                                {{ order.totalLabel }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[12.5px] font-semibold text-[#625D6D]">Latest Payment Reference</dt>
                            <dd class="mt-1 text-[#292536] tabular-nums">
                                {{ payment?.paymentReference ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
                <CardFooter class="flex flex-wrap items-center justify-end gap-3 border-t border-[#EEEAF2] px-6 py-4">
                    <Button as-child variant="outline">
                        <Link :href="order.detailUrl">
                            View Order Details
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="routes.portalDashboard">
                            Return to Dashboard
                        </Link>
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </PortalLayout>
</template>
