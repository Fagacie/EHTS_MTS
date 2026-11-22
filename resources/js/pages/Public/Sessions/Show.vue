<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, Calendar, Check, Clock, ExternalLink, MapPin, Users } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useAuth } from '@/composables/useAuth';
import { useRoutes } from '@/composables/useRoutes';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { cn, formatDisplayFee, getInitials } from '@/lib/utils';
import type { SessionShowProps } from '@/types/etms';

const props = defineProps<SessionShowProps>();

const { routes } = useRoutes();
const { isAuthenticated } = useAuth();

const seatDotClass = computed(() => {
    if (props.session.remainingSeats <= 0 || !props.session.isOpenForBooking) {
        return 'bg-[#272d38]/25';
    }

    if (props.session.remainingSeats <= 5) {
        return 'bg-[#272d38]/55';
    }

    return 'bg-[#272d38]';
});

const seatBarClass = computed(() => seatDotClass.value);

const seatAvailabilityPercent = computed(() => {
    if (props.session.capacity <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.max(0, Math.round((props.session.remainingSeats / props.session.capacity) * 100)),
    );
});
</script>

<template>
    <Head :title="session.title" />

    <PublicLayout>
        <section class="border-b border-[#272d38]/10 bg-white py-6 lg:py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink :href="routes.sessions">Sessions</BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage class="line-clamp-1">{{ session.title }}</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </section>

        <section class="bg-white py-10 lg:py-14">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-12 lg:items-start">
                    <div class="space-y-10 lg:col-span-8">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="text-[14px] text-[#272d38]/65">
                                    {{ session.sessionCode }} &middot; In-Person Physical Session
                                </p>
                                <Badge variant="secondary">
                                    {{ session.statusLabel }}
                                </Badge>
                            </div>

                            <h1 class="mt-2.5 text-3xl font-bold text-[#272d38] sm:text-4xl">
                                {{ session.title }}
                            </h1>

                            <dl class="mt-8 grid gap-4 rounded-xl border border-[#272d38]/12 bg-white p-5 shadow-[0_1px_2px_rgba(39,45,56,0.04)] sm:grid-cols-2 sm:p-6">
                                <div class="flex items-start gap-3">
                                    <Calendar class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <div>
                                        <dt class="text-[13px] font-bold text-[#272d38]/65">
                                            Date &amp; Time (MYT)
                                        </dt>
                                        <dd class="mt-1 text-[15px] font-bold text-[#272d38] tabular-nums">
                                            {{ session.dateRangeLabel }}
                                        </dd>
                                        <dd class="mt-0.5 text-[14px] text-[#272d38]/70 tabular-nums">
                                            {{ session.scheduleLabel }}
                                        </dd>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <div>
                                        <dt class="text-[13px] font-bold text-[#272d38]/65">
                                            Venue &amp; City
                                        </dt>
                                        <dd class="mt-1 text-[15px] font-bold text-[#272d38]">
                                            {{ session.venueName }}
                                        </dd>
                                        <dd v-if="session.venueAddressLine || session.venueLocation" class="mt-0.5 text-[14px] text-[#272d38]/70">
                                            {{ session.venueAddressLine ?? session.venueLocation }}
                                        </dd>
                                        <dd v-if="session.venueMapUrl" class="mt-1.5">
                                            <a
                                                :href="session.venueMapUrl"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-[14px] font-bold text-[#272d38] hover:underline underline-offset-4"
                                            >
                                                <span>Open map directions</span>
                                                <ExternalLink class="h-3.5 w-3.5" aria-hidden="true" />
                                            </a>
                                        </dd>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 border-t border-[#272d38]/10 pt-4">
                                    <Clock class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <div>
                                        <dt class="text-[13px] font-bold text-[#272d38]/65">
                                            Duration &amp; Cut-off
                                        </dt>
                                        <dd class="mt-1 text-[15px] font-bold text-[#272d38] tabular-nums">
                                            {{ session.durationLabel }}
                                        </dd>
                                        <dd v-if="session.registrationClosesLabel" class="mt-0.5 text-[14px] text-[#272d38]/70 tabular-nums">
                                            Closes {{ session.registrationClosesLabel }}
                                        </dd>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 border-t border-[#272d38]/10 pt-4">
                                    <Users class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <div>
                                        <dt class="text-[13px] font-bold text-[#272d38]/65">
                                            Lead Instructors
                                        </dt>
                                        <dd v-if="session.instructors.length > 0" class="mt-1 space-y-1">
                                            <div v-for="instructor in session.instructors" :key="instructor.id">
                                                <Link
                                                    v-if="instructor.profileUrl"
                                                    :href="instructor.profileUrl"
                                                    class="text-[15px] font-bold text-[#272d38] hover:underline underline-offset-4"
                                                >
                                                    {{ instructor.displayName }}
                                                </Link>
                                                <span v-else class="text-[15px] font-bold text-[#272d38]">
                                                    {{ instructor.displayName }}
                                                </span>
                                            </div>
                                        </dd>
                                        <dd v-else class="mt-1 text-[14px] text-[#272d38]/65">
                                            Faculty announcement pending
                                        </dd>
                                    </div>
                                </div>
                            </dl>
                        </div>

                        <div v-if="session.description" class="border-t border-[#272d38]/10 pt-8">
                            <h2 class="text-[22px] font-bold text-[#272d38]">
                                Session Overview
                            </h2>
                            <div class="mt-4 whitespace-pre-line text-[16px] leading-relaxed text-[#272d38]/75">
                                {{ session.description }}
                            </div>
                        </div>

                        <div class="border-t border-[#272d38]/10 pt-8">
                            <h2 class="text-[22px] font-bold text-[#272d38]">
                                What Physical Attendance Includes
                            </h2>
                            <ul class="mt-5 grid gap-3.5 sm:grid-cols-2">
                                <li class="flex items-start gap-2.5 text-[15px] leading-relaxed text-[#272d38]">
                                    <Check class="mt-1 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>Instructor-led practical exercises &amp; technical walkthroughs</span>
                                </li>
                                <li class="flex items-start gap-2.5 text-[15px] leading-relaxed text-[#272d38]">
                                    <Check class="mt-1 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>Verified attendance &amp; QR-verified certificate eligibility</span>
                                </li>
                                <li class="flex items-start gap-2.5 text-[15px] leading-relaxed text-[#272d38]">
                                    <Check class="mt-1 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>{{ session.recordingPolicyLabel }}</span>
                                </li>
                                <li class="flex items-start gap-2.5 text-[15px] leading-relaxed text-[#272d38]">
                                    <Check class="mt-1 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>Printable invoice &amp; receipt for individual or corporate claims</span>
                                </li>
                            </ul>
                        </div>

                        <div v-if="session.instructors.length > 0" class="border-t border-[#272d38]/10 pt-8">
                            <h2 class="text-[22px] font-bold text-[#272d38]">
                                Assigned Instructors
                            </h2>
                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                <Card
                                    v-for="instructor in session.instructors"
                                    :key="instructor.id"
                                    class="flex items-center gap-4 p-4"
                                >
                                    <img
                                        v-if="instructor.photoUrl"
                                        :src="instructor.photoUrl"
                                        :alt="instructor.displayName"
                                        class="h-12 w-12 shrink-0 rounded-full border border-[#272d38]/12 object-cover"
                                    >
                                    <span
                                        v-else
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#272d38] text-sm font-bold text-white"
                                    >
                                        {{ getInitials(instructor.displayName) }}
                                    </span>

                                    <div class="min-w-0">
                                        <Link
                                            v-if="instructor.profileUrl"
                                            :href="instructor.profileUrl"
                                            class="block truncate text-[16px] font-bold text-[#272d38] hover:underline underline-offset-4"
                                        >
                                            {{ instructor.displayName }}
                                        </Link>
                                        <p v-else class="truncate text-[16px] font-bold text-[#272d38]">
                                            {{ instructor.displayName }}
                                        </p>
                                        <p class="mt-0.5 truncate text-[14px] text-[#272d38]/65">
                                            {{ instructor.professionalTitle ?? instructor.roleLabel }}
                                        </p>
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </div>

                    <aside class="space-y-6 lg:sticky lg:top-24 lg:col-span-4">
                        <Card class="p-6">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[14px] font-bold text-[#272d38]">
                                    Physical Seat Booking
                                </p>
                                <span class="text-[14px] font-semibold text-[#272d38]/65">
                                    {{ session.statusLabel }}
                                </span>
                            </div>

                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-[#272d38] tabular-nums">
                                    {{ formatDisplayFee(session.priceLabel) }}
                                </span>
                                <span class="text-[14px] text-[#272d38]/65">per seat</span>
                            </div>

                            <div class="mt-5 rounded-xl border border-[#272d38]/12 bg-[#272d38]/[0.025] p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[14px] text-[#272d38]/70">Seat Availability</span>
                                    <span class="inline-flex items-center gap-2 text-[14px] font-bold text-[#272d38] tabular-nums">
                                        <span :class="cn('h-1.5 w-1.5 rounded-full', seatDotClass)" aria-hidden="true" />
                                        <span>{{ session.seatsOpenLabel }}</span>
                                    </span>
                                </div>
                                <div class="mt-2.5 h-[3px] w-full overflow-hidden rounded-full bg-[#272d38]/12" aria-hidden="true">
                                    <div
                                        :class="cn('h-full rounded-full transition-all duration-150', seatBarClass)"
                                        :style="{ width: `${seatAvailabilityPercent}%` }"
                                    />
                                </div>
                            </div>

                            <ul class="mt-5 space-y-2.5 text-[14px] text-[#272d38]">
                                <li class="flex items-start gap-2">
                                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>Temporary seat hold during checkout</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>QR-verified certificate upon verified attendance</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>Individual &amp; corporate invoicing receipts</span>
                                </li>
                            </ul>

                            <div class="mt-6">
                                <template v-if="existingBooking">
                                    <div class="rounded-xl border border-[#272d38]/15 bg-[#272d38]/[0.03] p-4">
                                        <p class="text-[15px] font-bold text-[#272d38]">
                                            Active booking ({{ existingBooking.statusLabel }})
                                        </p>
                                        <p class="mt-1 text-[14px] text-[#272d38]/70 tabular-nums">
                                            Ref: {{ existingBooking.bookingReference }}
                                        </p>
                                        <Button as-child class="mt-3 w-full">
                                            <Link :href="existingBooking.detailUrl">
                                                View My Booking
                                            </Link>
                                        </Button>
                                    </div>
                                </template>

                                <template v-else-if="session.isOpenForBooking">
                                    <template v-if="isAuthenticated && canBookAsCustomer && session.bookUrl">
                                        <Button as-child class="w-full">
                                            <Link :href="session.bookUrl">
                                                Reserve Seat ({{ formatDisplayFee(session.priceLabel) }})
                                            </Link>
                                        </Button>
                                    </template>
                                    <template v-else-if="isAuthenticated">
                                        <p class="rounded-lg border border-[#272d38]/12 bg-[#272d38]/[0.03] px-3.5 py-3 text-[14px] text-[#272d38]/70">
                                            Seat reservations require a trainee account.
                                        </p>
                                    </template>
                                    <template v-else>
                                        <Button v-if="session.bookUrl" as-child class="w-full">
                                            <a :href="session.bookUrl">
                                                Sign in to Reserve Seat ({{ formatDisplayFee(session.priceLabel) }})
                                            </a>
                                        </Button>
                                        <Button v-else as-child class="w-full">
                                            <Link :href="routes.login">
                                                Sign in to Reserve Seat ({{ formatDisplayFee(session.priceLabel) }})
                                            </Link>
                                        </Button>
                                    </template>
                                </template>

                                <template v-else>
                                    <Button type="button" variant="outline" class="w-full" disabled>
                                        {{ session.isFull ? 'Session Full — No Seats Remaining' : 'Registration Closed' }}
                                    </Button>
                                </template>
                            </div>
                        </Card>
                    </aside>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
