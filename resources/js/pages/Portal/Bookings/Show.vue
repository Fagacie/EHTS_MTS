<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ArrowUpRight,
    Calendar,
    Check,
    Clock,
    Download,
    ExternalLink,
    FileText,
    MapPin,
    Receipt,
    Video,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import UserAvatar from '@/components/ds/UserAvatar.vue';
import { useRoutes } from '@/composables/useRoutes';
import FocusedLayout from '@/layouts/FocusedLayout.vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalBookingShowProps } from '@/types/etms';

const props = defineProps<PortalBookingShowProps>();

const { routes } = useRoutes();
const paying = ref(false);

const steps = [
    { key: 'reserved', label: 'Reserved' },
    { key: 'confirmed', label: 'Confirmed' },
    { key: 'attended', label: 'Attended' },
    { key: 'completed', label: 'Completed' },
];

const activeStepIndex = computed(() => {
    if (props.booking.isHoldActive) return 0;
    if (props.booking.attendance) return 3;
    if (props.booking.isConfirmed) return 1;
    return 0;
});

const hasMaterials = computed(
    () => Boolean(props.booking.session.materials && props.booking.session.materials.length > 0),
);

function statusTone(statusValue: string): StatusTone {
    if (['confirmed', 'paid', 'completed', 'present', 'verified', 'approved'].includes(statusValue)) return 'success';
    if (['pending_payment', 'pending', 'submitted'].includes(statusValue)) return 'warning';
    if (['cancelled', 'expired', 'refunded', 'absent', 'rejected'].includes(statusValue)) return 'danger';
    return 'primary';
}

function triggerOnlinePayment(): void {
    if (!props.booking.order?.payUrl || paying.value) return;

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
    <Head :title="booking.session.title" />

    <!-- Focused Checkout View when Payment Hold is Active -->
    <FocusedLayout v-if="booking.isHoldActive">
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="flex items-center justify-between gap-3">
                <Link
                    :href="routes.portalBookings"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    <span>Live Sessions</span>
                </Link>

                <StatusBadge tone="warning" dot>
                    {{ booking.statusLabel }}
                </StatusBadge>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                <div class="border-b border-line p-6">
                    <h1 class="type-page-title">{{ booking.session.title }}</h1>
                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
                        <span class="tabular inline-flex items-center gap-1.5 font-medium text-ink">
                            <Calendar class="size-4 text-coral-500" aria-hidden="true" />
                            {{ booking.session.scheduleLabel }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 font-medium text-ink">
                            <MapPin class="size-4 text-primary-600" aria-hidden="true" />
                            {{ booking.session.venueName }}
                        </span>
                    </div>
                </div>

                <div class="bg-warning-50/90 px-6 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-2.5">
                            <Clock class="mt-0.5 size-4.5 shrink-0 text-warning-600" aria-hidden="true" />
                            <div>
                                <p class="tabular text-sm font-bold text-navy-950">
                                    Complete payment before {{ booking.holdExpiresLabel }}
                                </p>
                                <p class="mt-0.5 text-xs text-warning-700">
                                    Your seat is temporarily held until checkout completes.
                                </p>
                            </div>
                        </div>

                        <AppButton
                            v-if="booking.order"
                            type="button"
                            variant="warm"
                            size="md"
                            pill
                            :loading="paying"
                            @click="triggerOnlinePayment"
                        >
                            {{ paying ? 'Connecting...' : 'Pay Now' }}
                        </AppButton>
                    </div>
                </div>

                <div v-if="booking.order" class="flex items-center justify-between gap-4 px-6 py-5">
                    <span class="text-sm text-muted">
                        Order <span class="tabular-nums-mono font-semibold text-navy-950">{{ booking.order.orderNumber }}</span>
                    </span>
                    <span class="tabular text-xl font-bold text-navy-950">
                        {{ booking.order.totalLabel }}
                    </span>
                </div>
            </div>
        </div>
    </FocusedLayout>

    <!-- Full Portal Session Details -->
    <PortalLayout v-else :breadcrumb="booking.session.title">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Back Link -->
            <div>
                <Link
                    :href="routes.portalBookings"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Back to Live Sessions</span>
                </Link>
            </div>

            <!-- Unified Session Header & Progress Card -->
            <section class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                <div class="p-6 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <StatusBadge :tone="statusTone(booking.statusValue)" dot>
                                    {{ booking.statusLabel }}
                                </StatusBadge>
                                <span
                                    v-if="booking.session.sessionCode"
                                    class="tabular-nums-mono text-xs font-medium text-muted"
                                >
                                    {{ booking.session.sessionCode }}
                                </span>
                            </div>

                            <h1 class="type-page-title mt-3">
                                {{ booking.session.title }}
                            </h1>
                        </div>

                        <div v-if="booking.session.recordingsUrl" class="shrink-0">
                            <AppButton
                                :href="booking.session.recordingsUrl"
                                variant="primary"
                                size="sm"
                                pill
                            >
                                <Video class="size-3.5" aria-hidden="true" />
                                <span>Watch Recordings</span>
                            </AppButton>
                        </div>
                    </div>

                    <!-- Key Logistics Strip -->
                    <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-sand-50 px-4 py-3">
                            <dt class="flex items-center gap-1.5 text-[11px] font-semibold tracking-wide text-muted uppercase">
                                <Calendar class="size-3.5 text-coral-500" aria-hidden="true" />
                                <span>Schedule</span>
                            </dt>
                            <dd class="tabular mt-1.5 text-sm font-semibold text-navy-950">
                                {{ booking.session.scheduleLabel }}
                            </dd>
                        </div>

                        <div class="rounded-xl bg-sand-50 px-4 py-3">
                            <dt class="flex items-center gap-1.5 text-[11px] font-semibold tracking-wide text-muted uppercase">
                                <Clock class="size-3.5 text-accent-600" aria-hidden="true" />
                                <span>Duration</span>
                            </dt>
                            <dd class="tabular mt-1.5 text-sm font-semibold text-navy-950">
                                {{ booking.session.durationLabel }}
                            </dd>
                        </div>

                        <div class="rounded-xl bg-sand-50 px-4 py-3">
                            <dt class="flex items-center justify-between gap-2 text-[11px] font-semibold tracking-wide text-muted uppercase">
                                <span class="inline-flex items-center gap-1.5">
                                    <MapPin class="size-3.5 text-primary-600" aria-hidden="true" />
                                    <span>Venue</span>
                                </span>
                                <a
                                    v-if="booking.session.venueMapUrl"
                                    :href="booking.session.venueMapUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-0.5 text-[11px] font-semibold text-primary-600 normal-case hover:text-primary-700"
                                >
                                    <span>Map</span>
                                    <ExternalLink class="size-3" aria-hidden="true" />
                                </a>
                            </dt>
                            <dd class="mt-1.5 text-sm font-semibold text-navy-950">
                                {{ booking.session.venueName }}
                            </dd>
                            <dd v-if="booking.session.venueAddressLine" class="mt-0.5 truncate text-xs text-muted">
                                {{ booking.session.venueAddressLine }}
                            </dd>
                        </div>
                    </dl>

                    <p v-if="booking.session.venueNotes" class="mt-3 text-xs text-muted">
                        <span class="font-semibold text-navy-950">Venue note:</span>
                        {{ booking.session.venueNotes }}
                    </p>
                </div>

                <!-- Compact Progress Footer (Active/Confirmed Bookings) -->
                <div
                    v-if="!booking.isExpired && !booking.isCancelled && !booking.isRefunded"
                    class="border-t border-line bg-sand-50/70 px-6 py-4 sm:px-7"
                >
                    <ol class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Session progress">
                        <li
                            v-for="(step, idx) in steps"
                            :key="step.key"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="flex size-5.5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                                :class="
                                    idx <= activeStepIndex
                                        ? 'bg-success-600 text-white'
                                        : 'bg-white text-muted ring-1 ring-line'
                                "
                            >
                                <Check v-if="idx <= activeStepIndex" class="size-3" aria-hidden="true" />
                                <span v-else class="tabular">{{ idx + 1 }}</span>
                            </span>
                            <span
                                class="text-xs font-semibold"
                                :class="idx <= activeStepIndex ? 'text-navy-950' : 'text-muted'"
                            >
                                {{ step.label }}
                            </span>
                        </li>
                    </ol>
                </div>

                <!-- Exception Alert Strip (Expired / Cancelled / Refunded) -->
                <div
                    v-else
                    role="status"
                    class="flex flex-col justify-between gap-3 border-t border-danger-600/15 bg-danger-50 px-6 py-4 sm:flex-row sm:items-center sm:px-7"
                >
                    <div class="flex items-start gap-2.5">
                        <AlertCircle class="mt-0.5 size-4.5 shrink-0 text-danger-600" aria-hidden="true" />
                        <p class="text-xs font-medium text-danger-700">
                            <template v-if="booking.isExpired">
                                This reservation hold expired before payment was completed.
                            </template>
                            <template v-else-if="booking.isCancelled">
                                {{ booking.cancellationReason || 'This booking was cancelled.' }}
                            </template>
                            <template v-else-if="booking.isRefunded">
                                This booking has been refunded. See your linked receipt for details.
                            </template>
                        </p>
                    </div>
                    <AppButton
                        v-if="booking.isExpired && booking.session.bookAgainUrl"
                        :href="booking.session.bookAgainUrl"
                        variant="warm"
                        size="sm"
                        pill
                    >
                        Book Again
                    </AppButton>
                </div>
            </section>

            <!-- 2-Column Content -->
            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                <!-- Left Column: Materials, Instructors, Attendance (only what's relevant) -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Teaching Materials (for confirmed bookings) -->
                    <section
                        v-if="booking.isConfirmed"
                        class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-bold text-navy-950">Session materials</h2>
                            <span
                                v-if="hasMaterials"
                                class="tabular rounded-full bg-sand-100 px-2.5 py-0.5 text-xs font-semibold text-navy-900"
                            >
                                {{ booking.session.materials?.length }}
                            </span>
                        </div>

                        <ul v-if="hasMaterials" class="mt-4 divide-y divide-neutral-100">
                            <li
                                v-for="material in booking.session.materials"
                                :key="material.id"
                                class="flex flex-col gap-3 py-3.5 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-700">
                                        <FileText class="size-4" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-navy-950">{{ material.title }}</p>
                                        <p class="tabular mt-0.5 text-xs text-muted">
                                            {{ material.typeLabel }} &middot; {{ material.fileSizeLabel }}
                                        </p>
                                    </div>
                                </div>

                                <AppButton
                                    v-if="material.isDownloadable && material.downloadUrl"
                                    :href="material.downloadUrl"
                                    external
                                    variant="outline"
                                    size="sm"
                                    pill
                                >
                                    <Download class="size-3.5" aria-hidden="true" />
                                    <span>Download</span>
                                </AppButton>
                            </li>
                        </ul>

                        <p v-else class="mt-3 text-sm text-muted">
                            Materials will appear here once published by your instructor.
                        </p>
                    </section>

                    <!-- Assigned Instructors (modern interactive cards with photo & profile action) -->
                    <section
                        v-if="booking.session.instructors.length > 0"
                        class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-bold text-navy-950">Instructors</h2>
                            <span class="tabular rounded-full bg-sand-100 px-2.5 py-0.5 text-xs font-semibold text-navy-900">
                                {{ booking.session.instructors.length }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <component
                                :is="instructor.profileUrl ? Link : 'div'"
                                v-for="instructor in booking.session.instructors"
                                :key="instructor.name"
                                :href="instructor.profileUrl || undefined"
                                class="group flex items-center justify-between gap-3.5 rounded-2xl bg-sand-50/90 p-4 transition-all duration-150"
                                :class="
                                    instructor.profileUrl
                                        ? 'cursor-pointer hover:bg-sand-100/90 focus-visible:outline-2 focus-visible:outline-primary-600'
                                        : ''
                                "
                            >
                                <div class="flex min-w-0 items-center gap-3.5">
                                    <UserAvatar
                                        :src="instructor.photoUrl"
                                        :name="instructor.name"
                                        size="md"
                                    />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-navy-950 transition-colors group-hover:text-primary-600">
                                            {{ instructor.name }}
                                        </p>
                                        <p v-if="instructor.title || instructor.roleLabel" class="mt-0.5 truncate text-xs text-muted">
                                            {{ instructor.title || instructor.roleLabel }}
                                        </p>
                                        <p v-if="instructor.qualifications" class="mt-0.5 truncate text-[11px] text-accent-700">
                                            {{ instructor.qualifications }}
                                        </p>
                                    </div>
                                </div>

                                <span
                                    v-if="instructor.profileUrl"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-navy-950 shadow-xs ring-1 ring-line transition-all duration-150 group-hover:bg-navy-950 group-hover:text-white group-hover:ring-navy-950"
                                >
                                    <span>Profile</span>
                                    <ArrowUpRight
                                        class="size-3.5 transition-transform duration-150 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                        aria-hidden="true"
                                    />
                                </span>
                            </component>
                        </div>
                    </section>

                    <!-- Attendance Record (only rendered once recorded) -->
                    <section
                        v-if="booking.attendance"
                        class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <h2 class="text-base font-bold text-navy-950">Attendance</h2>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <StatusBadge :tone="statusTone(booking.attendance.attendanceStatusValue)" dot>
                                    {{ booking.attendance.attendanceStatusLabel }}
                                </StatusBadge>
                                <StatusBadge :tone="statusTone(booking.attendance.reviewStatusValue)">
                                    {{ booking.attendance.reviewStatusLabel }}
                                </StatusBadge>
                            </div>
                            <p v-if="booking.attendance.remarks" class="text-sm text-muted">
                                {{ booking.attendance.remarks }}
                            </p>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Clean Payment & Receipt Card -->
                <aside class="lg:sticky lg:top-24 lg:col-span-4">
                    <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-bold text-navy-950">Payment</h2>
                            <StatusBadge
                                v-if="booking.order"
                                :tone="statusTone(booking.order.statusValue)"
                                dot
                            >
                                {{ booking.order.statusLabel }}
                            </StatusBadge>
                        </div>

                        <template v-if="booking.order">
                            <p class="tabular mt-3 text-2xl font-bold tracking-tight text-navy-950">
                                {{ booking.order.totalLabel }}
                            </p>

                            <dl class="mt-4 space-y-2.5 border-t border-line pt-4 text-xs">
                                <div class="flex items-center justify-between gap-2">
                                    <dt class="text-muted">Order</dt>
                                    <dd class="tabular-nums-mono font-semibold text-navy-950">
                                        {{ booking.order.orderNumber }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <dt class="text-muted">Booking ref</dt>
                                    <dd class="tabular-nums-mono font-semibold text-navy-950">
                                        {{ booking.bookingReference }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <dt class="text-muted">Registration</dt>
                                    <dd class="font-medium text-navy-950">
                                        {{ booking.order.registrationTypeLabel }}
                                    </dd>
                                </div>
                                <div v-if="booking.order.companyName" class="flex items-start justify-between gap-2">
                                    <dt class="text-muted">Organisation</dt>
                                    <dd class="text-right font-medium text-navy-950">
                                        {{ booking.order.companyName }}
                                    </dd>
                                </div>
                            </dl>

                            <AppButton
                                :href="booking.order.detailUrl"
                                variant="outline"
                                size="md"
                                class="mt-5 w-full"
                            >
                                <Receipt class="size-4" aria-hidden="true" />
                                <span>View Receipt</span>
                            </AppButton>
                        </template>

                        <p v-else class="mt-3 text-sm text-muted">
                            No linked payment record.
                        </p>

                        <p class="mt-4 border-t border-line pt-4 text-[11px] leading-relaxed text-muted">
                            Need to reschedule or request a refund? Contact administration with reference
                            <span class="tabular-nums-mono font-semibold text-navy-900">{{ booking.bookingReference }}</span>.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </PortalLayout>
</template>
