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
import type { PortalBookingsIndexProps } from '@/types/etms';

defineProps<PortalBookingsIndexProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head title="My Bookings" />

    <PortalLayout breadcrumb="My Bookings">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        My Bookings
                    </h1>
                    <p class="mt-1 text-[13.5px] text-[#625D6D]">
                        Track upcoming physical training reservations, seat holds, and completed session records.
                    </p>
                </div>

                <Button as-child class="shrink-0">
                    <Link :href="routes.sessions">
                        <span>Explore Sessions</span>
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>
                </Button>
            </header>

            <Card class="p-4 sm:px-5">
                <div
                    class="flex flex-wrap items-center gap-2"
                    role="navigation"
                    aria-label="Filter bookings by status"
                >
                    <span class="mr-1 text-[12.5px] font-bold text-[#625D6D]">Status:</span>

                    <Link
                        :href="routes.portalBookings"
                        preserve-scroll
                        :class="
                            cn(
                                'inline-flex min-h-8.5 items-center rounded-[8px] px-3 py-1.5 text-[12.5px] font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                statusFilter === ''
                                    ? 'bg-[#7057D9] text-white'
                                    : 'border border-[#E5E1EB] bg-white text-[#403B4B] hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338]',
                            )
                        "
                    >
                        All Bookings
                    </Link>

                    <Link
                        v-for="statusOption in statuses"
                        :key="statusOption.value"
                        :href="`${routes.portalBookings}?status=${encodeURIComponent(statusOption.value)}`"
                        preserve-scroll
                        :class="
                            cn(
                                'inline-flex min-h-8.5 items-center rounded-[8px] px-3 py-1.5 text-[12.5px] font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                statusFilter === statusOption.value
                                    ? 'bg-[#7057D9] text-white'
                                    : 'border border-[#E5E1EB] bg-white text-[#403B4B] hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338]',
                            )
                        "
                    >
                        {{ statusOption.label }}
                    </Link>

                    <Link
                        v-if="statusFilter !== ''"
                        :href="routes.portalBookings"
                        preserve-scroll
                        class="ml-auto inline-flex min-h-8.5 items-center px-2 text-[12.5px] font-bold text-[#5942B5] hover:text-[#7057D9] hover:underline underline-offset-4"
                    >
                        Reset filter
                    </Link>
                </div>
            </Card>

            <Card
                v-if="bookings.length === 0"
                class="flex flex-col items-center justify-center px-6 py-12 text-center"
            >
                <p class="text-[16px] font-bold text-[#282338]">
                    No bookings found
                </p>
                <p class="mt-1.5 max-w-md text-[13.5px] text-[#625D6D]">
                    {{
                        statusFilter !== ''
                            ? 'No bookings match the selected status filter.'
                            : 'You have not booked any physical training sessions yet.'
                    }}
                </p>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <Button v-if="statusFilter !== ''" as-child variant="outline">
                        <Link :href="routes.portalBookings" preserve-scroll>
                            View All Bookings
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="routes.sessions">
                            Browse Scheduled Sessions
                        </Link>
                    </Button>
                </div>
            </Card>

            <template v-else>
                <div class="space-y-3.5 md:hidden">
                    <Card
                        v-for="booking in bookings"
                        :key="booking.id"
                        class="space-y-3 p-4"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <Link
                                :href="booking.detailUrl"
                                class="text-[13px] font-bold text-[#5942B5] tabular-nums hover:text-[#7057D9] hover:underline underline-offset-4"
                            >
                                {{ booking.bookingReference }}
                            </Link>
                            <PortalStatusBadge
                                :status="booking.statusValue"
                                :label="booking.statusLabel"
                            />
                        </div>

                        <div class="min-w-0">
                            <h2 class="text-[15px] font-bold text-[#282338]">
                                <Link
                                    :href="booking.detailUrl"
                                    class="hover:text-[#5942B5] hover:underline underline-offset-4"
                                >
                                    {{ booking.sessionTitle }}
                                </Link>
                            </h2>
                            <p
                                v-if="booking.moduleCode || booking.sessionCode"
                                class="mt-1 text-[12.5px] text-[#625D6D] tabular-nums"
                            >
                                {{ booking.moduleCode }}
                                <template v-if="booking.moduleCode && booking.sessionCode">
                                    &middot;
                                </template>
                                {{ booking.sessionCode }}
                            </p>
                        </div>

                        <dl class="space-y-2 border-t border-[#EEEAF2] pt-3 text-[13px]">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-[#625D6D]">Schedule</dt>
                                <dd class="text-right font-semibold text-[#292536] tabular-nums">
                                    {{ booking.scheduleLabel }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-[#625D6D]">Venue</dt>
                                <dd class="text-right text-[#403B4B]">
                                    {{ booking.venueLabel }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-[#625D6D]">Amount</dt>
                                <dd class="text-right font-bold text-[#282338] tabular-nums">
                                    {{ booking.amountLabel }}
                                </dd>
                            </div>
                        </dl>

                        <p
                            v-if="booking.isHoldActive && booking.holdExpiresFullLabel"
                            class="rounded-lg border border-[#E8CFA1] bg-[#FBF2E3] px-3 py-2 text-[12.5px] font-bold text-[#8B5A17] tabular-nums"
                        >
                            Seat hold expires {{ booking.holdExpiresFullLabel }}
                        </p>

                        <Button
                            as-child
                            :variant="booking.isHoldActive ? 'default' : 'outline'"
                            class="w-full"
                        >
                            <Link :href="booking.detailUrl">
                                {{ booking.isHoldActive ? 'Complete Payment' : 'View Booking Details' }}
                            </Link>
                        </Button>
                    </Card>
                </div>

                <Card class="hidden overflow-hidden md:block">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="pl-6">Booking Reference</TableHead>
                                <TableHead>Session</TableHead>
                                <TableHead>Schedule &amp; Venue</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="pr-6 text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="booking in bookings" :key="booking.id">
                                <TableCell class="whitespace-nowrap pl-6">
                                    <Link
                                        :href="booking.detailUrl"
                                        class="text-[13.5px] font-bold text-[#282338] tabular-nums hover:text-[#5942B5] hover:underline underline-offset-4"
                                    >
                                        {{ booking.bookingReference }}
                                    </Link>
                                    <p
                                        v-if="booking.orderNumber"
                                        class="mt-0.5 text-[12px] text-[#625D6D] tabular-nums"
                                    >
                                        Order {{ booking.orderNumber }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <Link
                                        :href="booking.detailUrl"
                                        class="text-[13.5px] font-bold text-[#292536] hover:text-[#5942B5] hover:underline underline-offset-4"
                                    >
                                        {{ booking.sessionTitle }}
                                    </Link>
                                    <p
                                        v-if="booking.moduleCode || booking.sessionCode"
                                        class="mt-0.5 text-[12px] text-[#625D6D] tabular-nums"
                                    >
                                        {{ booking.moduleCode }}
                                        <template v-if="booking.moduleCode && booking.sessionCode">
                                            &middot;
                                        </template>
                                        {{ booking.sessionCode }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <p class="text-[13px] text-[#292536] tabular-nums">
                                        {{ booking.scheduleLabel }}
                                    </p>
                                    <p class="mt-0.5 text-[12px] text-[#625D6D]">
                                        {{ booking.venueLabel }}
                                    </p>
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-[13.5px] font-bold text-[#282338] tabular-nums">
                                    {{ booking.amountLabel }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap">
                                    <PortalStatusBadge
                                        :status="booking.statusValue"
                                        :label="booking.statusLabel"
                                    />
                                    <p
                                        v-if="booking.isHoldActive && booking.holdExpiresTimeLabel"
                                        class="mt-1 text-[12px] font-semibold text-[#8B5A17] tabular-nums"
                                    >
                                        Expires {{ booking.holdExpiresTimeLabel }}
                                    </p>
                                </TableCell>
                                <TableCell class="whitespace-nowrap pr-6 text-right">
                                    <Button
                                        as-child
                                        :variant="booking.isHoldActive ? 'default' : 'outline'"
                                        size="sm"
                                    >
                                        <Link :href="booking.detailUrl">
                                            {{ booking.isHoldActive ? 'Complete Payment' : 'View Details' }}
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>

                <PublicPagination
                    :pagination="pagination"
                    item-label="bookings"
                />
            </template>
        </div>
    </PortalLayout>
</template>
