<script setup lang="ts">
import { computed } from 'vue';
import { ArrowRight, CalendarDays, Check, Clock, MapPin } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import type { PortalUpcomingBooking } from '@/types/etms';

const props = defineProps<{
    booking: PortalUpcomingBooking | null;
    browseUrl: string;
    modulesUrl: string;
}>();

const steps = [
    { key: 'reserved', label: 'Seat Reserved' },
    { key: 'confirmed', label: 'Payment Confirmed' },
    { key: 'attended', label: 'Attend Session' },
    { key: 'certified', label: 'Certificate' },
];

const activeStepIndex = computed(() => {
    const status = props.booking?.statusValue ?? '';
    if (status === 'pending_payment') return 0;
    if (status === 'confirmed') return 1;
    if (status === 'completed') return 3;
    return 1;
});

const statusTone = computed<StatusTone>(() => {
    const status = props.booking?.statusValue ?? '';
    if (status === 'confirmed') return 'success';
    if (status === 'pending_payment') return 'warning';
    if (status === 'cancelled' || status === 'expired') return 'danger';
    return 'primary';
});
</script>

<template>
    <div class="flex h-full flex-col justify-between rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80">
        <template v-if="booking">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-sand-100 px-3 py-1 text-xs font-semibold text-navy-950">
                            Next Session
                        </span>
                        <span class="tabular-nums-mono text-xs font-medium text-muted">
                            {{ booking.bookingReference }}
                        </span>
                    </div>
                    <StatusBadge :tone="statusTone" dot>
                        {{ booking.statusLabel }}
                    </StatusBadge>
                </div>

                <h2 class="mt-4 text-lg font-bold tracking-tight text-navy-950 sm:text-xl">
                    {{ booking.sessionTitle }}
                </h2>
                <p v-if="booking.moduleTitle" class="mt-1 text-xs font-medium text-muted">
                    {{ booking.moduleTitle }}
                </p>

                <dl class="mt-4 grid gap-2.5 text-sm text-ink sm:grid-cols-3">
                    <div class="flex items-center gap-2 rounded-xl bg-sand-50 px-3 py-2">
                        <CalendarDays class="size-4 shrink-0 text-coral-500" aria-hidden="true" />
                        <dd class="tabular truncate text-xs font-semibold text-navy-950">
                            {{ booking.dateRangeLabel }}
                        </dd>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl bg-sand-50 px-3 py-2">
                        <Clock class="size-4 shrink-0 text-accent-600" aria-hidden="true" />
                        <dd class="tabular truncate text-xs font-semibold text-navy-950">
                            {{ booking.timeRangeLabel }}
                        </dd>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl bg-sand-50 px-3 py-2">
                        <MapPin class="size-4 shrink-0 text-primary-600" aria-hidden="true" />
                        <dd class="truncate text-xs font-semibold text-navy-950">
                            {{ booking.venueName }}
                        </dd>
                    </div>
                </dl>

                <!-- Horizontal Progress Stepper -->
                <div class="mt-6">
                    <ol class="grid grid-cols-4 gap-2" aria-label="Enrollment progress">
                        <li
                            v-for="(step, idx) in steps"
                            :key="step.key"
                            class="relative flex flex-col"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold transition-colors"
                                    :class="
                                        idx < activeStepIndex
                                            ? 'bg-success-600 text-white'
                                            : idx === activeStepIndex
                                                ? 'bg-navy-950 text-white ring-4 ring-primary-100'
                                                : 'bg-sand-100 text-muted'
                                    "
                                >
                                    <Check v-if="idx < activeStepIndex" class="size-3.5" aria-hidden="true" />
                                    <span v-else class="tabular">{{ idx + 1 }}</span>
                                </span>
                                <span
                                    v-if="idx < steps.length - 1"
                                    class="h-0.5 flex-1 rounded-full"
                                    :class="idx < activeStepIndex ? 'bg-success-600' : 'bg-line'"
                                    aria-hidden="true"
                                />
                            </div>
                            <span
                                class="mt-2 text-[11px] leading-tight font-medium"
                                :class="idx <= activeStepIndex ? 'text-navy-950 font-semibold' : 'text-muted'"
                            >
                                {{ step.label }}
                            </span>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                <span class="text-xs text-muted">
                    {{ booking.venueFull }}
                </span>
                <AppButton :href="booking.detailUrl" variant="primary" size="sm" pill>
                    <span>View Booking</span>
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </AppButton>
            </div>
        </template>

        <!-- Empty State when no upcoming session is booked -->
        <template v-else>
            <div class="flex h-full flex-col justify-between">
                <div>
                    <span class="rounded-full bg-sand-100 px-3 py-1 text-xs font-semibold text-navy-950">
                        Getting Started
                    </span>
                    <h2 class="mt-4 text-lg font-bold tracking-tight text-navy-950">
                        No upcoming live session booked yet
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed text-muted">
                        Reserve a seat in an upcoming field workshop or open your self-paced training modules.
                    </p>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-2.5">
                    <AppButton :href="browseUrl" variant="warm" size="sm" pill>
                        <span>Browse Live Sessions</span>
                        <ArrowRight class="size-3.5" aria-hidden="true" />
                    </AppButton>
                    <AppButton :href="modulesUrl" variant="outline" size="sm" pill>
                        <span>My Modules</span>
                    </AppButton>
                </div>
            </div>
        </template>
    </div>
</template>
