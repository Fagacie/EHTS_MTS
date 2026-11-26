<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Clock,
    MapPin,
    Users,
    Video,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { SessionShowProps } from '@/types/etms';

const props = defineProps<SessionShowProps>();

const seatTone = computed<StatusTone>(() => {
    if (!props.session.isOpenForBooking || props.session.isFull) return 'danger';
    if (props.session.remainingSeats <= 5) return 'warning';
    return 'success';
});

function initials(name: string): string {
    const parts = name.trim().split(/\s+/);
    return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '')).toUpperCase();
}
</script>

<template>
    <PublicLayout>
        <Head :title="session.title" />

        <!-- Breadcrumb & Title -->
        <section class="border-b border-line bg-sand-50/70">
            <div class="mx-auto max-w-7xl px-4 py-9 sm:px-6 sm:py-11 lg:px-8">
                <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-muted">
                    <Link :href="routes.sessions" class="inline-flex items-center gap-1 transition-colors hover:text-navy-950">
                        <ArrowLeft class="size-3.5" aria-hidden="true" />
                        <span>Training Sessions</span>
                    </Link>
                    <span>/</span>
                    <span class="tabular-nums-mono text-navy-900">{{ session.sessionCode }}</span>
                </div>

                <div class="mt-4 max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge :tone="seatTone" dot>
                            {{ session.isOpenForBooking && !session.isFull ? session.seatsOpenLabel : session.statusLabel }}
                        </StatusBadge>
                        <span class="tabular-nums-mono rounded-full bg-white px-3 py-0.5 text-xs font-semibold text-navy-900 ring-1 ring-line">
                            {{ session.sessionCode }}
                        </span>
                    </div>

                    <h1 class="type-page-title mt-3.5">{{ session.title }}</h1>

                    <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-medium text-ink">
                        <span class="inline-flex items-center gap-2 tabular">
                            <CalendarDays class="size-4 text-coral-500" aria-hidden="true" />
                            {{ session.dateRangeLabel }}
                        </span>
                        <span class="inline-flex items-center gap-2 tabular">
                            <Clock class="size-4 text-accent-600" aria-hidden="true" />
                            {{ session.timeLabel }} ({{ session.durationLabel }})
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <MapPin class="size-4 text-primary-600" aria-hidden="true" />
                            {{ session.venueName }}<template v-if="session.venueLocation">, {{ session.venueLocation }}</template>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main 2-Column Content -->
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-12 lg:items-start">
                <div class="space-y-10 lg:col-span-8">
                    <div
                        v-if="session.coverImageUrl"
                        class="overflow-hidden rounded-2xl bg-navy-900 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <img
                            :src="session.coverImageUrl"
                            :alt="session.title"
                            class="aspect-[16/9] w-full object-cover"
                        />
                    </div>

                    <!-- Clean 2x2 Logistics Grid -->
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-line bg-white p-5">
                            <dt class="flex items-center gap-2 text-xs font-semibold text-muted">
                                <CalendarDays class="size-4 text-coral-500" aria-hidden="true" />
                                <span>Schedule &amp; duration</span>
                            </dt>
                            <dd class="tabular mt-2 text-sm font-semibold text-navy-950">
                                {{ session.scheduleLabel }}
                            </dd>
                            <dd class="tabular mt-1 text-xs text-muted">
                                {{ session.durationLabel }}
                            </dd>
                        </div>

                        <div class="rounded-2xl border border-line bg-white p-5">
                            <dt class="flex items-center gap-2 text-xs font-semibold text-muted">
                                <MapPin class="size-4 text-primary-600" aria-hidden="true" />
                                <span>Venue</span>
                            </dt>
                            <dd class="mt-2 text-sm font-semibold text-navy-950">
                                {{ session.venueName }}
                            </dd>
                            <dd v-if="session.venueAddressLine" class="mt-1 text-xs leading-relaxed text-muted">
                                {{ session.venueAddressLine }}
                            </dd>
                        </div>

                        <div class="rounded-2xl border border-line bg-white p-5">
                            <dt class="flex items-center gap-2 text-xs font-semibold text-muted">
                                <Video class="size-4 text-accent-600" aria-hidden="true" />
                                <span>Recordings</span>
                            </dt>
                            <dd class="mt-2 text-sm font-medium text-ink">
                                {{ session.recordingPolicyLabel }}
                            </dd>
                        </div>

                        <div class="rounded-2xl border border-line bg-white p-5">
                            <dt class="flex items-center gap-2 text-xs font-semibold text-muted">
                                <Users class="size-4 text-navy-700" aria-hidden="true" />
                                <span>Seats &amp; registration</span>
                            </dt>
                            <dd class="tabular mt-2 text-sm font-semibold text-navy-950">
                                {{ session.seatsOpenLabel }}
                            </dd>
                            <dd v-if="session.registrationClosesLabel" class="tabular mt-1 text-xs text-muted">
                                Closes {{ session.registrationClosesLabel }}
                            </dd>
                        </div>
                    </dl>

                    <div v-if="session.description">
                        <h2 class="type-section-title text-navy-950">Session details</h2>
                        <div class="mt-3.5 whitespace-pre-line text-sm leading-7 text-ink/90 sm:text-[15px]">
                            {{ session.description }}
                        </div>
                    </div>

                    <div v-if="session.instructors.length > 0">
                        <h2 class="type-section-title text-navy-950">Instructors</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div
                                v-for="inst in session.instructors"
                                :key="inst.id"
                                class="flex items-start gap-3.5 rounded-2xl border border-line bg-white p-5"
                            >
                                <img
                                    v-if="inst.photoUrl"
                                    :src="inst.photoUrl"
                                    :alt="inst.displayName"
                                    class="size-13 shrink-0 rounded-full object-cover ring-2 ring-sand-100"
                                />
                                <div
                                    v-else
                                    class="flex size-13 shrink-0 items-center justify-center rounded-full bg-navy-900 text-sm font-bold text-white"
                                >
                                    {{ initials(inst.displayName) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs font-medium text-accent-700">
                                        {{ inst.roleLabel }}
                                    </span>
                                    <h3 class="mt-0.5 text-sm font-semibold text-navy-950">
                                        <Link
                                            v-if="inst.profileUrl"
                                            :href="inst.profileUrl"
                                            class="transition-colors hover:text-primary-600"
                                        >
                                            {{ inst.displayName }}
                                        </Link>
                                        <span v-else>{{ inst.displayName }}</span>
                                    </h3>
                                    <p v-if="inst.professionalTitle" class="mt-0.5 text-xs text-muted">
                                        {{ inst.professionalTitle }}
                                    </p>
                                    <p v-if="inst.qualifications" class="mt-1 text-xs text-muted">
                                        {{ inst.qualifications }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Booking Card -->
                <aside class="lg:sticky lg:top-24 lg:col-span-4">
                    <div class="rounded-2xl bg-white p-6 shadow-elevated ring-1 ring-neutral-200/80">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="tabular text-3xl font-bold tracking-tight text-navy-950">
                                {{ session.priceLabel }}
                            </p>
                            <StatusBadge :tone="seatTone" dot>
                                {{ session.isOpenForBooking && !session.isFull ? `${session.remainingSeats} left` : session.statusLabel }}
                            </StatusBadge>
                        </div>

                        <p class="mt-2 text-xs leading-relaxed text-muted">
                            Per-participant fee. Your seat is held automatically during checkout while payment completes.
                        </p>

                        <div
                            v-if="existingBooking"
                            class="mt-5 rounded-xl bg-success-50 p-4 ring-1 ring-inset ring-success-600/20"
                        >
                            <div class="flex items-start gap-2.5">
                                <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-success-600" aria-hidden="true" />
                                <div>
                                    <p class="text-xs font-semibold text-success-700">
                                        Existing booking ({{ existingBooking.statusLabel }})
                                    </p>
                                    <p class="tabular-nums-mono mt-1 text-xs text-success-700/90">
                                        Ref: {{ existingBooking.bookingReference }}
                                    </p>
                                </div>
                            </div>
                            <AppButton
                                :href="existingBooking.detailUrl"
                                variant="secondary"
                                size="md"
                                class="mt-3.5 w-full"
                            >
                                <span>Manage Booking</span>
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </AppButton>
                        </div>

                        <div v-else class="mt-6 space-y-2.5">
                            <template v-if="session.isOpenForBooking && session.bookUrl">
                                <AppButton
                                    v-if="canBookAsCustomer"
                                    :href="session.bookUrl"
                                    variant="warm"
                                    size="lg"
                                    class="w-full"
                                >
                                    <span>Reserve Seat</span>
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </AppButton>

                                <template v-else-if="!auth.user">
                                    <AppButton :href="routes.login" variant="warm" size="lg" class="w-full">
                                        <span>Sign in to Reserve</span>
                                        <ArrowRight class="size-4" aria-hidden="true" />
                                    </AppButton>
                                    <AppButton :href="routes.register" variant="outline" size="md" class="w-full">
                                        Create Learner Account
                                    </AppButton>
                                </template>

                                <p
                                    v-else
                                    class="rounded-xl bg-warning-50 px-3.5 py-2.5 text-xs font-medium text-warning-700"
                                >
                                    Session bookings require a learner (customer) account.
                                </p>
                            </template>

                            <div
                                v-else
                                class="rounded-xl bg-sand-100 px-4 py-3 text-center text-xs font-semibold text-navy-900"
                            >
                                Registration is currently {{ session.statusLabel.toLowerCase() }}.
                            </div>
                        </div>

                        <dl class="mt-6 space-y-3 border-t border-line pt-5 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Session code</dt>
                                <dd class="tabular-nums-mono font-semibold text-navy-950">{{ session.sessionCode }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Dates</dt>
                                <dd class="tabular font-semibold text-navy-950">{{ session.dateRangeLabel }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Seats open</dt>
                                <dd class="tabular font-semibold text-navy-950">{{ session.remainingSeats }} / {{ session.capacity }}</dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </section>
    </PublicLayout>
</template>
