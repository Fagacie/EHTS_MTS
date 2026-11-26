<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight, Clock, MapPin } from 'lucide-vue-next';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import type { PortalCalendarEvent } from '@/types/etms';

const props = defineProps<{
    events: PortalCalendarEvent[];
    initialDate?: string;
}>();

function parseYmd(ymd?: string): Date {
    if (ymd && /^\d{4}-\d{2}-\d{2}$/.test(ymd)) {
        const [y, m, d] = ymd.split('-').map(Number);
        return new Date(y, m - 1, d);
    }
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

function formatYmd(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

const seedDate = parseYmd(props.initialDate);
const viewYear = ref(seedDate.getFullYear());
const viewMonth = ref(seedDate.getMonth());
const selectedYmd = ref(
    props.events[0]?.startDate ?? formatYmd(seedDate),
);

const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const monthTitle = computed(() =>
    new Date(viewYear.value, viewMonth.value, 1).toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    }),
);

const eventsByDate = computed(() => {
    const map = new Map<string, PortalCalendarEvent[]>();
    for (const ev of props.events) {
        const list = map.get(ev.startDate) ?? [];
        list.push(ev);
        map.set(ev.startDate, list);
    }
    return map;
});

interface DayCell {
    ymd: string;
    day: number;
    inMonth: boolean;
    isToday: boolean;
    events: PortalCalendarEvent[];
}

const todayYmd = formatYmd(new Date());

const calendarCells = computed<DayCell[]>(() => {
    const firstOfMonth = new Date(viewYear.value, viewMonth.value, 1);
    const startOffset = firstOfMonth.getDay();
    const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
    const daysInPrev = new Date(viewYear.value, viewMonth.value, 0).getDate();

    const cells: DayCell[] = [];

    for (let i = startOffset - 1; i >= 0; i--) {
        const d = daysInPrev - i;
        const dt = new Date(viewYear.value, viewMonth.value - 1, d);
        const ymd = formatYmd(dt);
        cells.push({
            ymd,
            day: d,
            inMonth: false,
            isToday: ymd === todayYmd,
            events: eventsByDate.value.get(ymd) ?? [],
        });
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dt = new Date(viewYear.value, viewMonth.value, d);
        const ymd = formatYmd(dt);
        cells.push({
            ymd,
            day: d,
            inMonth: true,
            isToday: ymd === todayYmd,
            events: eventsByDate.value.get(ymd) ?? [],
        });
    }

    while (cells.length < 42) {
        const d = cells.length - (startOffset + daysInMonth) + 1;
        const dt = new Date(viewYear.value, viewMonth.value + 1, d);
        const ymd = formatYmd(dt);
        cells.push({
            ymd,
            day: d,
            inMonth: false,
            isToday: ymd === todayYmd,
            events: eventsByDate.value.get(ymd) ?? [],
        });
    }

    return cells;
});

const selectedEvents = computed(
    () => eventsByDate.value.get(selectedYmd.value) ?? [],
);

const selectedDayHeading = computed(() => {
    const dt = parseYmd(selectedYmd.value);
    return dt.toLocaleDateString('en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
});

function prevMonth(): void {
    if (viewMonth.value === 0) {
        viewMonth.value = 11;
        viewYear.value -= 1;
    } else {
        viewMonth.value -= 1;
    }
}

function nextMonth(): void {
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value += 1;
    } else {
        viewMonth.value += 1;
    }
}

function dotColor(statusValue: string): string {
    if (statusValue === 'confirmed') return 'bg-success-600';
    if (statusValue === 'pending_payment') return 'bg-warning-600';
    return 'bg-primary-600';
}

function statusTone(statusValue: string): StatusTone {
    if (statusValue === 'confirmed') return 'success';
    if (statusValue === 'pending_payment') return 'warning';
    if (statusValue === 'cancelled' || statusValue === 'expired') return 'danger';
    return 'primary';
}
</script>

<template>
    <div class="h-full overflow-hidden rounded-2xl bg-white p-5 shadow-card ring-1 ring-neutral-200/80">
        <div class="grid gap-5 sm:grid-cols-[236px_minmax(0,1fr)]">
            <!-- Left Compact Month Grid -->
            <div class="min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-bold text-navy-950">{{ monthTitle }}</h2>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            aria-label="Previous month"
                            class="inline-flex size-7 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                            @click="prevMonth"
                        >
                            <ChevronLeft class="size-4" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            aria-label="Next month"
                            class="inline-flex size-7 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                            @click="nextMonth"
                        >
                            <ChevronRight class="size-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-7 gap-0.5 text-center">
                    <span
                        v-for="wd in weekdays"
                        :key="wd"
                        class="py-1 text-[11px] font-semibold text-muted"
                    >
                        {{ wd }}
                    </span>

                    <button
                        v-for="cell in calendarCells"
                        :key="cell.ymd"
                        type="button"
                        class="tabular relative flex h-8 cursor-pointer flex-col items-center justify-center rounded-lg text-xs transition-colors"
                        :class="[
                            selectedYmd === cell.ymd
                                ? 'bg-navy-950 font-bold text-white'
                                : cell.isToday
                                    ? 'bg-primary-50 font-bold text-primary-700'
                                    : cell.inMonth
                                        ? 'font-medium text-ink hover:bg-sand-100'
                                        : 'text-muted/45 hover:bg-sand-50',
                        ]"
                        @click="selectedYmd = cell.ymd"
                    >
                        <span>{{ cell.day }}</span>
                        <span
                            v-if="cell.events.length > 0"
                            class="mt-0.5 flex items-center gap-0.5"
                            aria-hidden="true"
                        >
                            <span
                                v-for="ev in cell.events.slice(0, 3)"
                                :key="ev.id"
                                class="size-1 rounded-full"
                                :class="selectedYmd === cell.ymd ? 'bg-warm-400' : dotColor(ev.statusValue)"
                            />
                        </span>
                    </button>
                </div>
            </div>

            <!-- Right Selected-Day Agenda -->
            <div class="min-w-0 overflow-hidden border-t border-line pt-4 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-5">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold tracking-wide text-muted uppercase">
                        Agenda &middot; {{ selectedDayHeading }}
                    </p>
                    <span class="tabular text-xs font-semibold text-navy-900">
                        {{ selectedEvents.length }}
                    </span>
                </div>

                <div v-if="selectedEvents.length > 0" class="mt-3 space-y-2.5">
                    <Link
                        v-for="ev in selectedEvents"
                        :key="ev.id"
                        :href="ev.detailUrl"
                        class="block rounded-xl bg-sand-50 p-3 transition-colors hover:bg-sand-100"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="tabular-nums-mono text-[11px] font-medium text-muted">
                                {{ ev.bookingReference }}
                            </span>
                            <StatusBadge :tone="statusTone(ev.statusValue)">
                                {{ ev.statusLabel }}
                            </StatusBadge>
                        </div>
                        <p class="mt-1.5 line-clamp-2 text-sm font-semibold text-navy-950">
                            {{ ev.sessionTitle }}
                        </p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                            <span class="tabular inline-flex items-center gap-1">
                                <Clock class="size-3" aria-hidden="true" />
                                {{ ev.timeLabel }}
                            </span>
                            <span class="inline-flex items-center gap-1 truncate">
                                <MapPin class="size-3 shrink-0" aria-hidden="true" />
                                <span class="truncate">{{ ev.venueName }}</span>
                            </span>
                        </div>
                    </Link>
                </div>

                <div v-else class="mt-6 flex flex-col items-center justify-center rounded-xl bg-sand-50/70 px-4 py-8 text-center">
                    <CalendarDays class="size-5 text-muted/70" aria-hidden="true" />
                    <p class="mt-2 text-xs font-semibold text-navy-900">No sessions on this day</p>
                    <p class="mt-0.5 text-[11px] text-muted">
                        Select a highlighted date to inspect your session details.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
