<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { PortalCalendarEvent } from '@/types/etms';

const props = defineProps<{
    events: PortalCalendarEvent[];
    initialDate: string;
}>();

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as const;
const MONTH_NAMES = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
] as const;

interface ParsedDate {
    year: number;
    month: number; // 0-indexed (0..11)
    day: number;
}

function parseYmd(value: string): ParsedDate {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
    if (!match) {
        const now = new Date();
        return {
            year: now.getFullYear(),
            month: now.getMonth(),
            day: now.getDate(),
        };
    }

    return {
        year: Number(match[1]),
        month: Number(match[2]) - 1,
        day: Number(match[3]),
    };
}

function formatYmd(year: number, month: number, day: number): string {
    const m = String(month + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');

    return `${year}-${m}-${d}`;
}

const initialParsed = parseYmd(props.initialDate);
const viewYear = ref(initialParsed.year);
const viewMonth = ref(initialParsed.month);
const selectedDate = ref(formatYmd(initialParsed.year, initialParsed.month, initialParsed.day));

watch(
    () => props.initialDate,
    (next) => {
        const parsed = parseYmd(next);
        viewYear.value = parsed.year;
        viewMonth.value = parsed.month;
        selectedDate.value = formatYmd(parsed.year, parsed.month, parsed.day);
    },
);

const eventsByDate = computed<Record<string, PortalCalendarEvent[]>>(() => {
    const map: Record<string, PortalCalendarEvent[]> = {};

    for (const event of props.events) {
        const start = parseYmd(event.startDate);
        const end = parseYmd(event.endDate || event.startDate);

        const cursor = new Date(start.year, start.month, start.day);
        const endCursor = new Date(end.year, end.month, end.day);

        // Cap span loop cleanly in case of malformed multi-year ranges
        for (let guard = 0; guard < 31 && cursor <= endCursor; guard++) {
            const key = formatYmd(cursor.getFullYear(), cursor.getMonth(), cursor.getDate());
            if (!map[key]) {
                map[key] = [];
            }
            map[key].push(event);
            cursor.setDate(cursor.getDate() + 1);
        }
    }

    return map;
});

const monthHeading = computed(() => `${MONTH_NAMES[viewMonth.value]} ${viewYear.value}`);

const leadingBlanks = computed(() => {
    const firstWeekday = new Date(viewYear.value, viewMonth.value, 1).getDay();
    return (firstWeekday + 6) % 7;
});

const daysInMonth = computed(() =>
    new Date(viewYear.value, viewMonth.value + 1, 0).getDate(),
);

const calendarCells = computed(() => {
    const cells: { day: number; key: string; hasEvent: boolean; isSelected: boolean }[] = [];

    for (let day = 1; day <= daysInMonth.value; day++) {
        const key = formatYmd(viewYear.value, viewMonth.value, day);
        cells.push({
            day,
            key,
            hasEvent: Boolean(eventsByDate.value[key]?.length),
            isSelected: selectedDate.value === key,
        });
    }

    return cells;
});

const selectedDateFormatted = computed(() => {
    const parsed = parseYmd(selectedDate.value);
    return `${parsed.day} ${MONTH_NAMES[parsed.month]}`;
});

const selectedDayEvents = computed<PortalCalendarEvent[]>(() =>
    eventsByDate.value[selectedDate.value] ?? [],
);

const firstEventDateKey = computed<string | null>(() => {
    if (props.events.length === 0) {
        return null;
    }

    return props.events[0].startDate;
});

const hasJumpTarget = computed(() => {
    if (!firstEventDateKey.value) {
        return false;
    }

    const firstParsed = parseYmd(firstEventDateKey.value);
    return firstParsed.year !== viewYear.value || firstParsed.month !== viewMonth.value;
});

function prevMonth(): void {
    if (viewMonth.value === 0) {
        viewMonth.value = 11;
        viewYear.value -= 1;
    } else {
        viewMonth.value -= 1;
    }

    selectedDate.value = formatYmd(viewYear.value, viewMonth.value, 1);
}

function nextMonth(): void {
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value += 1;
    } else {
        viewMonth.value += 1;
    }

    selectedDate.value = formatYmd(viewYear.value, viewMonth.value, 1);
}

function selectDay(key: string): void {
    selectedDate.value = key;
}

function jumpToNextSession(): void {
    const target = firstEventDateKey.value ?? props.initialDate;
    const parsed = parseYmd(target);
    viewYear.value = parsed.year;
    viewMonth.value = parsed.month;
    selectedDate.value = formatYmd(parsed.year, parsed.month, parsed.day);
}
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="flex flex-row items-center justify-between gap-2 space-y-0 pb-3">
            <CardTitle class="text-[14.5px]">{{ monthHeading }}</CardTitle>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    aria-label="Previous month"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-[8px] border border-[#E5E1EB] bg-white text-[#403B4B] transition-colors hover:border-[#B9ADE9] hover:bg-[#FAF9FE] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    @click="prevMonth"
                >
                    <ChevronLeft class="h-4 w-4" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    aria-label="Next month"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-[8px] border border-[#E5E1EB] bg-white text-[#403B4B] transition-colors hover:border-[#B9ADE9] hover:bg-[#FAF9FE] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    @click="nextMonth"
                >
                    <ChevronRight class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </CardHeader>

        <CardContent class="flex flex-1 flex-col justify-between pt-0">
            <div>
                <div class="grid grid-cols-7 gap-1 text-center">
                    <div
                        v-for="weekday in WEEKDAYS"
                        :key="weekday"
                        class="py-1.5 text-[11px] font-semibold text-[#625D6D]"
                    >
                        {{ weekday }}
                    </div>

                    <div
                        v-for="blank in leadingBlanks"
                        :key="`blank-${blank}`"
                        aria-hidden="true"
                    />

                    <button
                        v-for="cell in calendarCells"
                        :key="cell.key"
                        type="button"
                        :aria-label="`${cell.day} ${MONTH_NAMES[viewMonth]} ${viewYear}${cell.hasEvent ? ' (has training session)' : ''}`"
                        :aria-pressed="cell.isSelected"
                        :class="
                            cn(
                                'relative flex h-9 items-center justify-center rounded-[7px] text-[12.5px] tabular-nums transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/45',
                                cell.isSelected
                                    ? 'bg-[#7057D9] font-extrabold text-white'
                                    : cell.hasEvent
                                        ? 'font-bold text-[#282338] hover:bg-[#F1EDFB]'
                                        : 'font-medium text-[#403B4B] hover:bg-[#F1EDFB]',
                            )
                        "
                        @click="selectDay(cell.key)"
                    >
                        <span>{{ cell.day }}</span>
                        <span
                            v-if="cell.hasEvent"
                            :class="
                                cn(
                                    'absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full',
                                    cell.isSelected ? 'bg-white' : 'bg-[#7057D9]',
                                )
                            "
                            aria-hidden="true"
                        />
                    </button>
                </div>
            </div>

            <div class="mt-4 border-t border-[#F0EDF4] pt-3">
                <div v-if="selectedDayEvents.length > 0" class="space-y-2.5">
                    <div
                        v-for="event in selectedDayEvents"
                        :key="`${selectedDate}-${event.id}`"
                        class="rounded-[9px] border border-[#E5E1EB] bg-[#FAF9FC] p-2.5"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11.5px] font-bold text-[#5942B5] tabular-nums">
                                {{ selectedDateFormatted }} &middot; {{ event.timeLabel }}
                            </span>
                            <PortalStatusBadge
                                :status="event.statusValue"
                                :label="event.statusLabel"
                            />
                        </div>
                        <p class="mt-1 line-clamp-2 text-[12.5px] font-bold leading-snug text-[#282338]">
                            {{ event.sessionTitle }}
                        </p>
                        <div class="mt-1.5 flex items-center justify-between gap-2 text-[11.5px] text-[#625D6D]">
                            <span class="truncate">{{ event.venueName }}</span>
                            <Link
                                :href="event.detailUrl"
                                class="shrink-0 font-bold text-[#5942B5] hover:text-[#7057D9] hover:underline underline-offset-2"
                            >
                                View &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[12px] text-[#625D6D]">
                        {{ selectedDateFormatted }} &middot; No scheduled training
                    </p>
                    <button
                        v-if="hasJumpTarget"
                        type="button"
                        class="text-[11.5px] font-bold text-[#5942B5] hover:text-[#7057D9] hover:underline"
                        @click="jumpToNextSession"
                    >
                        Jump to session &rarr;
                    </button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
