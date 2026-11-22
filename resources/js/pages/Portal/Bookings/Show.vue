<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, ArrowRight, Calendar, Clock, Download, ExternalLink, MapPin } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useRoutes } from '@/composables/useRoutes';
import FocusedLayout from '@/layouts/FocusedLayout.vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalBookingShowProps } from '@/types/etms';

const props = defineProps<PortalBookingShowProps>();

const { routes } = useRoutes();
const paying = ref(false);

function triggerOnlinePayment(): void {
    if (!props.booking.order?.payUrl || paying.value) {
        return;
    }

    paying.value = true;
    router.post(
        props.booking.order.payUrl,
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
    <Head :title="`Booking ${booking.bookingReference}`" />

    <FocusedLayout v-if="booking.isHoldActive">
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="routes.portalBookings"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-neutral-600 transition-colors duration-150 hover:text-[#0F4C81]"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Back to My Bookings</span>
                </Link>

                <span class="rounded-[6px] border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                    {{ booking.statusLabel }}
                </span>
            </div>

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        v-if="booking.session.sessionCode"
                        class="rounded-[6px] border border-neutral-200 bg-white px-2.5 py-0.5 font-mono text-xs font-medium text-neutral-700"
                    >
                        {{ booking.session.sessionCode }}
                    </span>
                    <span class="font-mono text-xs text-neutral-500 tabular-nums">
                        {{ booking.bookingReference }}
                    </span>
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-900 sm:text-3xl">
                    {{ booking.session.title }}
                </h1>
            </div>

            <div
                role="status"
                class="flex flex-col gap-4 rounded-lg border border-amber-200 bg-amber-50/70 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <Clock class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" aria-hidden="true" />
                    <div>
                        <p class="text-sm font-semibold text-neutral-900 tabular-nums">
                            Complete Payment Before {{ booking.holdExpiresLabel }}
                        </p>
                        <p class="mt-1 text-sm text-neutral-700">
                            Your seat is held for 15 minutes. Complete online payment to confirm your registration.
                        </p>
                    </div>
                </div>

                <Button
                    v-if="booking.order"
                    type="button"
                    class="h-10 shrink-0 rounded-[6px] bg-[#0F4C81] px-5 text-sm font-medium text-white transition-colors duration-150 hover:bg-[#0F4C81]/90"
                    :disabled="paying"
                    @click="triggerOnlinePayment"
                >
                    {{ paying ? 'Connecting...' : 'Pay Online Now' }}
                </Button>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-white p-6">
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div class="flex items-start gap-2.5">
                        <Calendar class="mt-0.5 h-4 w-4 shrink-0 text-neutral-400" aria-hidden="true" />
                        <div>
                            <dt class="text-xs font-medium text-neutral-500">Schedule</dt>
                            <dd class="mt-1 text-sm font-semibold text-neutral-900 tabular-nums">
                                {{ booking.session.scheduleLabel }}
                            </dd>
                            <dd class="mt-0.5 text-xs text-neutral-500 tabular-nums">
                                {{ booking.session.durationLabel }}
                            </dd>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5">
                        <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-neutral-400" aria-hidden="true" />
                        <div>
                            <dt class="text-xs font-medium text-neutral-500">Venue</dt>
                            <dd class="mt-1 text-sm font-semibold text-neutral-900">
                                {{ booking.session.venueName }}
                            </dd>
                            <dd v-if="booking.session.venueAddressLine" class="mt-0.5 text-xs text-neutral-500">
                                {{ booking.session.venueAddressLine }}
                            </dd>
                        </div>
                    </div>
                </dl>

                <div
                    v-if="booking.order"
                    class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-neutral-200 pt-5"
                >
                    <div class="text-sm text-neutral-600">
                        Order <span class="font-mono font-medium text-neutral-900">{{ booking.order.orderNumber }}</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm text-neutral-600">Total Payable</span>
                        <span class="text-xl font-bold text-neutral-900 tabular-nums">
                            {{ booking.order.totalLabel }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </FocusedLayout>

    <PortalLayout v-else :breadcrumb="booking.bookingReference">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="routes.portalBookings"
                    class="inline-flex items-center gap-1.5 text-[13.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Back to My Bookings</span>
                </Link>

                <PortalStatusBadge
                    :status="booking.statusValue"
                    :label="booking.statusLabel"
                />
            </div>

            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge v-if="booking.session.moduleCode" variant="secondary">
                            {{ booking.session.moduleCode }}
                        </Badge>
                        <Badge v-if="booking.session.sessionCode" variant="outline">
                            {{ booking.session.sessionCode }}
                        </Badge>
                    </div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        {{ booking.session.title }}
                    </h1>
                    <p class="text-[13.5px] text-[#625D6D] tabular-nums">
                        Booking reference {{ booking.bookingReference }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Button
                        v-if="booking.session.sessionDetailUrl"
                        as-child
                        variant="outline"
                    >
                        <Link :href="booking.session.sessionDetailUrl">
                            View Session Details
                        </Link>
                    </Button>
                </div>
            </header>

            <Card
                v-if="booking.isHoldActive"
                role="status"
                class="flex flex-col gap-4 border-[#E8CFA1] bg-[#FBF2E3] p-4 shadow-none sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#8B5A17]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536] tabular-nums">
                            Complete Payment Before {{ booking.holdExpiresLabel }}
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            Your seat is temporarily held while payment is pending. If payment is not completed within the hold window, the reservation is released automatically.
                        </p>
                    </div>
                </div>

                <Button
                    v-if="booking.order"
                    type="button"
                    size="sm"
                    class="shrink-0"
                    :disabled="paying"
                    @click="triggerOnlinePayment"
                >
                    {{ paying ? 'Connecting...' : 'Pay Online Now' }}
                </Button>
            </Card>

            <Card
                v-else-if="booking.isExpired"
                role="status"
                class="flex flex-col gap-4 border-[#ECC4CA] bg-[#FBECEE] p-4 shadow-none sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#A33C48]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536]">
                            Seat Reservation Expired
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            This temporary hold expired before payment was completed and your seat was released.
                        </p>
                    </div>
                </div>

                <Button
                    v-if="booking.session.bookAgainUrl"
                    as-child
                    size="sm"
                    class="shrink-0"
                >
                    <Link :href="booking.session.bookAgainUrl">
                        Book Seat Again
                    </Link>
                </Button>
            </Card>

            <Card
                v-else-if="booking.isCancelled"
                role="status"
                class="border-[#ECC4CA] bg-[#FBECEE] p-4 shadow-none sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#A33C48]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536]">
                            Booking Cancelled
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            {{ booking.cancellationReason || 'This session booking was cancelled.' }}
                        </p>
                    </div>
                </div>
            </Card>

            <Card
                v-else-if="booking.isRefunded"
                role="status"
                class="border-[#ECC4CA] bg-[#FBECEE] p-4 shadow-none sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#A33C48]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536]">
                            Booking Refunded
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            This booking has been refunded. Check your linked order record for payment and refund tracking details.
                        </p>
                    </div>
                </div>
            </Card>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Training Session Details</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <dl class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Schedule</dt>
                                    <dd class="mt-1 text-[13.5px] font-bold text-[#282338] tabular-nums">
                                        {{ booking.session.scheduleLabel }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Duration</dt>
                                    <dd class="mt-1 text-[13.5px] text-[#292536] tabular-nums">
                                        {{ booking.session.durationLabel }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Venue</dt>
                                    <dd class="mt-1 text-[13.5px] font-bold text-[#282338]">
                                        {{ booking.session.venueName }}
                                    </dd>
                                    <dd
                                        v-if="booking.session.venueAddressLine"
                                        class="mt-0.5 text-[12.5px] text-[#625D6D]"
                                    >
                                        {{ booking.session.venueAddressLine }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Assigned Instructors</dt>
                                    <dd class="mt-1 text-[13.5px] text-[#292536]">
                                        <ul
                                            v-if="booking.session.instructors.length > 0"
                                            class="space-y-2"
                                        >
                                            <li
                                                v-for="instructor in booking.session.instructors"
                                                :key="instructor.name"
                                                class="flex items-center gap-2.5"
                                            >
                                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#F1EDFB] text-[11px] font-extrabold text-[#5942B5]">
                                                    {{ instructor.initials }}
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block font-bold text-[#282338]">{{ instructor.name }}</span>
                                                    <span v-if="instructor.title" class="block text-[12.5px] text-[#625D6D]">
                                                        {{ instructor.title }}
                                                    </span>
                                                </span>
                                            </li>
                                        </ul>
                                        <span v-else class="text-[#625D6D]">To be assigned</span>
                                    </dd>
                                </div>
                            </dl>

                            <div
                                v-if="booking.session.venueNotes || booking.session.venueMapUrl"
                                class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[#EEEAF2] pt-4"
                            >
                                <p
                                    v-if="booking.session.venueNotes"
                                    class="text-[13px] text-[#403B4B]"
                                >
                                    <span class="font-bold text-[#282338]">Venue notes:</span>
                                    {{ booking.session.venueNotes }}
                                </p>

                                <Button
                                    v-if="booking.session.venueMapUrl"
                                    as="a"
                                    :href="booking.session.venueMapUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    variant="outline"
                                    size="sm"
                                >
                                    <span>Open Venue Map</span>
                                    <ExternalLink class="h-3.5 w-3.5" aria-hidden="true" />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between gap-4 space-y-0 border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Attendance Status</CardTitle>
                            <Link
                                v-if="booking.session.recordingsUrl"
                                :href="booking.session.recordingsUrl"
                                class="inline-flex items-center gap-1 text-[12.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
                            >
                                <span>Session Recordings</span>
                                <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                            </Link>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div
                                v-if="booking.attendance"
                                class="flex flex-wrap items-center justify-between gap-4"
                            >
                                <div>
                                    <p class="text-[12.5px] font-semibold text-[#625D6D]">
                                        Recorded Attendance
                                    </p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <PortalStatusBadge
                                            :status="booking.attendance.attendanceStatusValue"
                                            :label="booking.attendance.attendanceStatusLabel"
                                        />
                                        <PortalStatusBadge
                                            :status="booking.attendance.reviewStatusValue"
                                            :label="booking.attendance.reviewStatusLabel"
                                        />
                                    </div>
                                </div>

                                <p
                                    v-if="booking.attendance.remarks"
                                    class="text-[13px] text-[#403B4B]"
                                >
                                    {{ booking.attendance.remarks }}
                                </p>
                            </div>

                            <p v-else class="text-[13.5px] text-[#625D6D]">
                                Attendance has not been recorded for this session yet.
                            </p>
                        </CardContent>
                    </Card>

                    <Card v-if="booking.isConfirmed">
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>
                                Session Teaching Materials ({{ booking.session.materials?.length ?? 0 }})
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div
                                v-if="booking.session.materials && booking.session.materials.length > 0"
                                class="divide-y divide-[#EEEAF2]"
                            >
                                <div
                                    v-for="material in booking.session.materials"
                                    :key="material.id"
                                    class="flex flex-col gap-3 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[14px] font-bold text-[#282338]">
                                                {{ material.title }}
                                            </span>
                                            <Badge variant="outline">
                                                {{ material.typeLabel }}
                                            </Badge>
                                        </div>
                                        <p
                                            v-if="material.description"
                                            class="text-[13px] text-[#403B4B]"
                                        >
                                            {{ material.description }}
                                        </p>
                                        <p class="text-[12px] text-[#625D6D] tabular-nums">
                                            {{ material.originalFilename }} &middot; {{ material.fileSizeLabel }}
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <Button
                                            v-if="material.isDownloadable && material.downloadUrl"
                                            as="a"
                                            :href="material.downloadUrl"
                                            size="sm"
                                        >
                                            <Download class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span>Download</span>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-[13.5px] text-[#625D6D]">
                                No teaching materials have been published for this training session yet.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-5">
                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Booking Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <template v-if="booking.order">
                                <dl class="space-y-3 text-[13.5px]">
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-[#625D6D]">Order Number</dt>
                                        <dd class="font-bold text-[#282338] tabular-nums">
                                            {{ booking.order.orderNumber }}
                                        </dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-[#625D6D]">Payment Status</dt>
                                        <dd>
                                            <PortalStatusBadge
                                                :status="booking.order.statusValue"
                                                :label="booking.order.statusLabel"
                                            />
                                        </dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-[#625D6D]">Registration Type</dt>
                                        <dd class="font-semibold text-[#292536]">
                                            {{ booking.order.registrationTypeLabel }}
                                        </dd>
                                    </div>
                                    <div
                                        v-if="booking.order.companyName"
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <dt class="text-[#625D6D]">Organisation</dt>
                                        <dd class="text-right font-semibold text-[#292536]">
                                            {{ booking.order.companyName }}
                                        </dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 border-t border-[#EEEAF2] pt-3">
                                        <dt class="font-bold text-[#282338]">Total Fee</dt>
                                        <dd class="text-[16px] font-extrabold text-[#282338] tabular-nums">
                                            {{ booking.order.totalLabel }}
                                        </dd>
                                    </div>
                                </dl>

                                <Button as-child variant="outline" class="mt-4 w-full">
                                    <Link :href="booking.order.detailUrl">
                                        View Full Order &amp; Receipt
                                    </Link>
                                </Button>
                            </template>
                            <p v-else class="text-[13.5px] text-[#625D6D]">
                                No linked order record available for this booking.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Cancellation &amp; Refund Policy</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <p class="text-[13px] leading-relaxed text-[#403B4B]">
                                Cancellations and refund requests are handled directly by Ecohydrotech administration. Please contact our support team with your booking reference if your schedule changes.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
