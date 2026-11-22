<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, ArrowRight, CheckCircle2, Receipt } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useRoutes } from '@/composables/useRoutes';
import FocusedLayout from '@/layouts/FocusedLayout.vue';
import type { CheckoutReturnProps } from '@/types/etms';

defineProps<CheckoutReturnProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head :title="`Receipt — ${order.orderNumber}`" />

    <FocusedLayout>
        <div class="mx-auto max-w-xl">
            <div class="overflow-hidden rounded-lg border border-neutral-200 bg-white">
                <div
                    v-if="order.isPaid"
                    class="border-b border-neutral-200 bg-emerald-50/60 px-6 py-6 text-center"
                >
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <CheckCircle2 class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <h1 class="mt-3 text-xl font-semibold tracking-tight text-neutral-900">
                        Payment Confirmed
                    </h1>
                    <p class="mt-1 text-sm text-neutral-600">
                        {{
                            order.isModulePurchase
                                ? 'Your standalone learning module access is now active.'
                                : 'Your physical training session booking is confirmed.'
                        }}
                    </p>
                    <p class="mt-4 text-3xl font-bold tracking-tight text-neutral-900 tabular-nums">
                        {{ order.totalLabel }}
                    </p>
                </div>

                <div
                    v-else-if="order.isPendingPayment"
                    class="border-b border-neutral-200 bg-amber-50/60 px-6 py-6 text-center"
                >
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <AlertCircle class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <h1 class="mt-3 text-xl font-semibold tracking-tight text-neutral-900">
                        Awaiting Payment Confirmation
                    </h1>
                    <p class="mt-1 text-sm text-neutral-600">
                        {{
                            redirectReportedPaid
                                ? 'We are awaiting verified gateway confirmation before activating your access.'
                                : 'This order is waiting for verified payment confirmation.'
                        }}
                    </p>
                </div>

                <div
                    v-else
                    class="border-b border-neutral-200 bg-red-50/60 px-6 py-6 text-center"
                >
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-red-100 text-red-700">
                        <AlertCircle class="h-6 w-6" aria-hidden="true" />
                    </div>
                    <h1 class="mt-3 text-xl font-semibold tracking-tight text-neutral-900">
                        Payment {{ order.statusLabel }}
                    </h1>
                    <p class="mt-1 text-sm text-neutral-600">
                        This transaction was not completed ({{ order.statusLabel }}).
                    </p>
                </div>

                <div class="px-6 py-5">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-neutral-500">
                            <Receipt class="h-3.5 w-3.5" aria-hidden="true" />
                            <span>{{ order.isPaid ? 'Official Receipt' : 'Transaction Summary' }}</span>
                        </span>
                        <PortalStatusBadge
                            :status="order.statusValue"
                            :label="order.statusLabel"
                        />
                    </div>

                    <dl class="divide-y divide-neutral-100 text-sm">
                        <div class="flex items-center justify-between gap-4 py-3">
                            <dt class="text-neutral-500">Order Number</dt>
                            <dd class="font-mono font-medium text-neutral-900 tabular-nums">
                                {{ order.orderNumber }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4 py-3">
                            <dt class="text-neutral-500">Order Type</dt>
                            <dd class="font-medium text-neutral-900">
                                {{ order.orderTypeLabel }}
                            </dd>
                        </div>

                        <div v-if="payment?.paymentReference" class="flex items-center justify-between gap-4 py-3">
                            <dt class="text-neutral-500">Payment Reference</dt>
                            <dd class="font-mono text-neutral-800 tabular-nums">
                                {{ payment.paymentReference }}
                            </dd>
                        </div>

                        <div v-if="payment?.paidAtLabel" class="flex items-center justify-between gap-4 py-3">
                            <dt class="text-neutral-500">Payment Date</dt>
                            <dd class="font-medium text-neutral-900 tabular-nums">
                                {{ payment.paidAtLabel }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4 py-3">
                            <dt class="font-semibold text-neutral-900">Total Amount</dt>
                            <dd class="font-bold text-neutral-900 tabular-nums">
                                {{ order.totalLabel }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="flex flex-col-reverse gap-2.5 border-t border-neutral-200 bg-neutral-50/70 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <Button as-child variant="outline" class="h-10 rounded-[6px]">
                        <Link :href="order.detailUrl">
                            View Order Details
                        </Link>
                    </Button>

                    <Button
                        v-if="order.isPaid"
                        as-child
                        class="h-10 rounded-[6px] bg-[#0F4C81] text-white hover:bg-[#0F4C81]/90"
                    >
                        <Link :href="order.isModulePurchase ? routes.portalModules : routes.portalBookings">
                            <span>{{ order.isModulePurchase ? 'Go to My Modules' : 'View My Bookings' }}</span>
                            <ArrowRight class="h-4 w-4" aria-hidden="true" />
                        </Link>
                    </Button>

                    <Button
                        v-else-if="order.isPendingPayment"
                        as-child
                        class="h-10 rounded-[6px] bg-[#0F4C81] text-white hover:bg-[#0F4C81]/90"
                    >
                        <Link :href="refreshUrl">
                            Refresh Status
                        </Link>
                    </Button>

                    <Button
                        v-else
                        as-child
                        class="h-10 rounded-[6px] bg-[#0F4C81] text-white hover:bg-[#0F4C81]/90"
                    >
                        <Link :href="routes.sessions">
                            Browse Training
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="mt-4 text-center">
                <Link
                    :href="routes.portalDashboard"
                    class="text-sm font-medium text-neutral-500 transition-colors duration-150 hover:text-neutral-900"
                >
                    Return to Trainee Dashboard
                </Link>
            </div>
        </div>
    </FocusedLayout>
</template>
