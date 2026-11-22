<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn, formatDisplayFee } from '@/lib/utils';
import type { UpcomingSessionItem } from '@/types/etms';

type SeatTone = 'full' | 'low' | 'open';

defineProps<{
    sessions: UpcomingSessionItem[];
}>();

function getSeatTone(session: UpcomingSessionItem): SeatTone {
    if (session.remainingSeats <= 0 || !session.isOpenForBooking) {
        return 'full';
    }

    if (session.remainingSeats <= 5) {
        return 'low';
    }

    return 'open';
}

function getSeatDotClass(session: UpcomingSessionItem): string {
    const tone = getSeatTone(session);

    if (tone === 'full') {
        return 'bg-[#272d38]/25';
    }

    if (tone === 'low') {
        return 'bg-[#272d38]/55';
    }

    return 'bg-[#272d38]';
}

function getSeatBarClass(session: UpcomingSessionItem): string {
    const tone = getSeatTone(session);

    if (tone === 'full') {
        return 'bg-[#272d38]/25';
    }

    if (tone === 'low') {
        return 'bg-[#272d38]/55';
    }

    return 'bg-[#272d38]';
}

function getSeatLabel(session: UpcomingSessionItem): string {
    return `${session.remainingSeats} / ${session.capacity} seats`;
}

function getSeatAvailabilityPercent(session: UpcomingSessionItem): number {
    if (session.capacity <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.max(0, Math.round((session.remainingSeats / session.capacity) * 100)),
    );
}
</script>

<template>
    <div>
        <div class="hidden overflow-hidden rounded-xl border border-[#272d38]/12 bg-white shadow-[0_1px_2px_rgba(39,45,56,0.04)] md:block">
            <Table>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead class="w-48 pl-6">Date</TableHead>
                        <TableHead>Physical Session</TableHead>
                        <TableHead class="w-56">Location</TableHead>
                        <TableHead class="w-40 tabular-nums">Seats</TableHead>
                        <TableHead class="w-28 text-right tabular-nums">Fee</TableHead>
                        <TableHead class="w-36 pr-6 text-right">Action</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="session in sessions" :key="session.id">
                        <TableCell class="whitespace-nowrap pl-6 align-middle">
                            <span class="block text-[15px] font-bold text-[#272d38] tabular-nums">
                                {{ session.dateRangeLabel }}
                            </span>
                            <span class="mt-0.5 block text-[14px] text-[#272d38]/65 tabular-nums">
                                {{ session.timeLabel }} &middot; {{ session.durationLabel }}
                            </span>
                        </TableCell>

                        <TableCell class="align-middle">
                            <Link
                                :href="session.detailUrl"
                                class="rounded-md text-[17px] font-bold leading-snug text-[#272d38] transition-colors duration-150 hover:underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                            >
                                {{ session.title }}
                            </Link>
                            <p
                                v-if="session.instructorNames"
                                class="mt-0.5 text-[14px] text-[#272d38]/65"
                            >
                                {{ session.instructorNames }}
                            </p>
                        </TableCell>

                        <TableCell class="align-middle">
                            <span class="block text-[15px] font-semibold text-[#272d38]">
                                {{ session.venueName }}
                            </span>
                            <span
                                v-if="session.venueLocation"
                                class="mt-0.5 block text-[14px] text-[#272d38]/65"
                            >
                                {{ session.venueLocation }}
                            </span>
                        </TableCell>

                        <TableCell class="whitespace-nowrap align-middle">
                            <div class="inline-flex items-center gap-2">
                                <span
                                    :class="cn('h-1.5 w-1.5 shrink-0 rounded-full', getSeatDotClass(session))"
                                    aria-hidden="true"
                                />
                                <span
                                    :class="
                                        cn(
                                            'text-[14px] font-semibold tabular-nums',
                                            session.remainingSeats === 0
                                                ? 'text-[#272d38]/60'
                                                : 'text-[#272d38]',
                                        )
                                    "
                                >
                                    {{ getSeatLabel(session) }}
                                </span>
                            </div>
                            <div
                                class="mt-2 h-[3px] w-24 overflow-hidden rounded-full bg-[#272d38]/12"
                                aria-hidden="true"
                            >
                                <div
                                    :class="cn('h-full rounded-full transition-all duration-150', getSeatBarClass(session))"
                                    :style="{ width: `${getSeatAvailabilityPercent(session)}%` }"
                                />
                            </div>
                        </TableCell>

                        <TableCell class="whitespace-nowrap text-right align-middle">
                            <span class="text-[16px] font-bold text-[#272d38] tabular-nums">
                                {{ formatDisplayFee(session.priceLabel) }}
                            </span>
                        </TableCell>

                        <TableCell class="whitespace-nowrap pr-6 text-right align-middle">
                            <Button
                                v-if="session.isOpenForBooking && session.bookUrl"
                                as-child
                                variant="outline"
                                size="sm"
                                class="group whitespace-nowrap"
                            >
                                <Link :href="session.bookUrl">
                                    <span>Book seat</span>
                                    <ArrowRight
                                        class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </Button>
                            <Button
                                v-else
                                as-child
                                variant="outline"
                                size="sm"
                                class="group whitespace-nowrap"
                            >
                                <Link :href="session.detailUrl">
                                    <span>View details</span>
                                    <ArrowRight
                                        class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="divide-y divide-[#272d38]/10 overflow-hidden rounded-xl border border-[#272d38]/12 bg-white shadow-[0_1px_2px_rgba(39,45,56,0.04)] md:hidden">
            <div
                v-for="session in sessions"
                :key="session.id"
                class="space-y-3 p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[15px] font-bold text-[#272d38] tabular-nums">
                            {{ session.dateRangeLabel }}
                        </p>
                        <p class="mt-0.5 text-[14px] text-[#272d38]/65 tabular-nums">
                            {{ session.timeLabel }} &middot; {{ session.durationLabel }}
                        </p>
                    </div>

                    <div class="text-right">
                        <div class="inline-flex items-center gap-2">
                            <span
                                :class="cn('h-1.5 w-1.5 shrink-0 rounded-full', getSeatDotClass(session))"
                                aria-hidden="true"
                            />
                            <span class="text-[14px] font-semibold text-[#272d38] tabular-nums">
                                {{ getSeatLabel(session) }}
                            </span>
                        </div>
                        <div
                            class="ml-auto mt-1.5 h-[3px] w-24 overflow-hidden rounded-full bg-[#272d38]/12"
                            aria-hidden="true"
                        >
                            <div
                                :class="cn('h-full rounded-full', getSeatBarClass(session))"
                                :style="{ width: `${getSeatAvailabilityPercent(session)}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-[17px] font-bold leading-snug text-[#272d38]">
                        <Link :href="session.detailUrl" class="hover:underline underline-offset-4">
                            {{ session.title }}
                        </Link>
                    </h3>
                    <p v-if="session.instructorNames" class="mt-1 text-[14px] text-[#272d38]/65">
                        {{ session.instructorNames }}
                    </p>
                </div>

                <p class="text-[14px] text-[#272d38]/65">
                    {{ session.venueName }}<template v-if="session.venueLocation">, {{ session.venueLocation }}</template>
                </p>

                <div class="flex items-center justify-between gap-4 pt-1">
                    <span class="text-[16px] font-bold text-[#272d38] tabular-nums">
                        {{ formatDisplayFee(session.priceLabel) }}
                    </span>
                    <Button
                        v-if="session.isOpenForBooking && session.bookUrl"
                        as-child
                        variant="outline"
                        size="sm"
                        class="group whitespace-nowrap"
                    >
                        <Link :href="session.bookUrl">
                            <span>Book seat</span>
                            <ArrowRight
                                class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                aria-hidden="true"
                            />
                        </Link>
                    </Button>
                    <Button
                        v-else
                        as-child
                        variant="outline"
                        size="sm"
                        class="group whitespace-nowrap"
                    >
                        <Link :href="session.detailUrl">
                            <span>View details</span>
                            <ArrowRight
                                class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5"
                                aria-hidden="true"
                            />
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
