<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock,
    MapPin,
    Plus,
    Users,
} from 'lucide-vue-next';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';

export interface AdminCalendarSession {
    id: number;
    title: string;
    sessionCode: string;
    startDate: string;
    timeLabel: string;
    venue: string;
    instructors: string[];
    confirmedBookings: number;
    capacity: number;
    statusValue: string;
    status: string;
    detailUrl: string;
}

const props = withDefaults(
    defineProps<{
        sessions: AdminCalendarSession[];
        initialDate?: string;
        title?: string;
        allSessionsHref?: string;
        createSessionHref?: string | null;
    }>(),
    {
        title: 'Training schedule calendar',
        allSessionsHref: '/admin-vue/sessions',
        createSessionHref: '/admin-vue/sessions/create',
    },
);

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

const todayDate = parseYmd(props.initialDate);
const todayYmd = formatYmd(todayDate);

// Pick the earliest upcoming session (or today) as initial selection so the calendar opens on active dates
const nextUpcomingSession = computed(() =>
    props.sessions.find((s) => s.startDate >= todayYmd) ?? props.sessions[0] ?? null,
);

const initialFocusDate = parseYmd(nextUpcomingSession.value?.startDate ?? todayYmd);
const viewYear = ref(initialFocusDate.getFullYear());
const viewMonth = ref(initialFocusDate.getMonth());
const selectedYmd = ref(nextUpcomingSession.value?.startDate ?? todayYmd);

const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const monthTitle = computed(() =>
    new Date(viewYear.value, viewMonth.value, 1).toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    }),
);

const sessionsByDate = computed(() => {
    const map = new Map<string, AdminCalendarSession[]>();
    for (const s of props.sessions) {
        if (!s.startDate) continue;
        const list = map.get(s.startDate) ?? [];
        list.push(s);
        map.set(s.startDate, list);
    }
    return map;
});

interface DayCell {
    ymd: string;
    day: number;
    inMonth: boolean;
    isToday: boolean;
    sessions: AdminCalendarSession[];
}

const calendarCells = computed<DayCell[]>(() => {
    const firstOfMonth = new Date(viewYear.value, viewMonth.value, 1);
    const startOffset = firstOfMonth.getDay();
    const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate();
    const daysInPrev = new Date(viewYear.value, viewMonth.value, 0).getDate();

    const cells: DayCell[] = [];

    for (let i = startOffset - 1; i >= 0; i--) {
        const d = daysInPrev - i;
        const ymd = formatYmd(new Date(viewYear.value, viewMonth.value - 1, d));
        cells.push({
            ymd,
            day: d,
            inMonth: false,
            isToday: ymd === todayYmd,
            sessions: sessionsByDate.value.get(ymd) ?? [],
        });
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const ymd = formatYmd(new Date(viewYear.value, viewMonth.value, d));
        cells.push({
            ymd,
            day: d,
            inMonth: true,
            isToday: ymd === todayYmd,
            sessions: sessionsByDate.value.get(ymd) ?? [],
        });
    }

    while (cells.length < 42) {
        const d = cells.length - (startOffset + daysInMonth) + 1;
        const ymd = formatYmd(new Date(viewYear.value, viewMonth.value + 1, d));
        cells.push({
            ymd,
            day: d,
            inMonth: false,
            isToday: ymd === todayYmd,
            sessions: sessionsByDate.value.get(ymd) ?? [],
        });
    }

    return cells;
});

const sessionsInViewMonth = computed(() =>
    calendarCells.value.filter((c) => c.inMonth).reduce((sum, c) => sum + c.sessions.length, 0),
);

const selectedSessions = computed(() => sessionsByDate.value.get(selectedYmd.value) ?? []);

const selectedDayHeading = computed(() => {
    const dt = parseYmd(selectedYmd.value);
    return dt.toLocaleDateString('en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
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

function goToday(): void {
    viewYear.value = todayDate.getFullYear();
    viewMonth.value = todayDate.getMonth();
    selectedYmd.value = todayYmd;
}

function selectDay(cell: DayCell): void {
    selectedYmd.value = cell.ymd;
    if (!cell.inMonth) {
        const dt = parseYmd(cell.ymd);
        viewYear.value = dt.getFullYear();
        viewMonth.value = dt.getMonth();
    }
}

function dotColor(statusValue: string): string {
    if (['published', 'completed'].includes(statusValue)) return 'bg-accent-600';
    if (['full', 'registration_closed'].includes(statusValue)) return 'bg-warm-500';
    if (statusValue === 'cancelled') return 'bg-danger-600';
    return 'bg-primary-600';
}

function statusTone(statusValue: string): StatusTone {
    if (['published', 'completed'].includes(statusValue)) return 'success';
    if (['full', 'registration_closed', 'draft'].includes(statusValue)) return 'warning';
    if (statusValue === 'cancelled') return 'danger';
    return 'primary';
}
</script>

<template>
    <div class="flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/70 px-6 py-4">
            <div class="flex items-center gap-2.5">
                <CalendarDays class="size-4 text-primary-600" aria-hidden="true" />
                <div>
                    <h2 class="text-sm font-semibold text-navy-950">{{ title }}</h2>
                    <p class="tabular text-xs text-muted">
                        {{ sessionsInViewMonth }}
                        {{ sessionsInViewMonth === 1 ? 'session' : 'sessions' }} in {{ monthTitle }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="inline-flex h-8 items-center rounded-lg px-2.5 text-xs font-semibold text-muted ring-1 ring-line transition-colors hover:bg-sand-50 hover:text-navy-950"
                    @click="goToday"
                >
                    Today
                </button>
                <button
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-muted ring-1 ring-line transition-colors hover:bg-sand-50 hover:text-navy-950"
                    aria-label="Previous month"
                    @click="prevMonth"
                >
                    <ChevronLeft class="size-4" aria-hidden="true" />
                </button>
                <span class="min-w-28 text-center text-xs font-semibold text-navy-950">
                    {{ monthTitle }}
                </span>
                <button
                    type="button"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-muted ring-1 ring-line transition-colors hover:bg-sand-50 hover:text-navy-950"
                    aria-label="Next month"
                    @click="nextMonth"
                >
                    <ChevronRight class="size-4" aria-hidden="true" />
                </button>
            </div>
        </div>

        <!-- Split Calendar Grid + Selected Date Agenda -->
        <div class="grid flex-1 divide-y divide-line/70 md:grid-cols-12 md:divide-x md:divide-y-0">
            <!-- Monthly Grid (7 cols) -->
            <div class="p-5 md:col-span-7">
                <div class="grid grid-cols-7 gap-1 text-center">
                    <span
                        v-for="wd in weekdays"
                        :key="wd"
                        class="pb-2 text-[11px] font-semibold text-muted"
                    >
                        {{ wd }}
                    </span>

                    <button
                        v-for="cell in calendarCells"
                        :key="cell.ymd"
                        type="button"
                        class="relative flex h-10 flex-col items-center justify-center rounded-xl text-xs transition-colors duration-150"
                        :class="[
                            selectedYmd === cell.ymd
                                ? 'bg-navy-950 font-bold text-white shadow-xs'
                                : cell.isToday
                                    ? 'bg-primary-50 font-bold text-primary-700 ring-1 ring-inset ring-primary-600/25'
                                    : cell.inMonth
                                        ? 'font-medium text-navy-950 hover:bg-sand-100'
                                        : 'text-muted/45 hover:bg-sand-50',
                        ]"
                        @click="selectDay(cell)"
                    >
                        <span class="tabular leading-none">{{ cell.day }}</span>
                        <span
                            v-if="cell.sessions.length > 0"
                            class="mt-1 flex items-center gap-0.5"
                            aria-hidden="true"
                        >
                            <span
                                v-for="ev in cell.sessions.slice(0, 3)"
                                :key="ev.id"
                                class="size-1.5 rounded-full"
                                :class="selectedYmd === cell.ymd ? 'bg-warm-400' : dotColor(ev.statusValue)"
                            />
                        </span>
                    </button>
                </div>
            </div>

            <!-- Selected Date Details (5 cols) -->
            <div class="flex flex-col justify-between bg-sand-50/45 p-5 md:col-span-5">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-navy-950">
                            {{ selectedDayHeading }}
                        </p>
                        <span class="tabular text-[11px] font-medium text-muted">
                            {{ selectedSessions.length }}
                            {{ selectedSessions.length === 1 ? 'session' : 'sessions' }}
                        </span>
                    </div>

                    <div v-if="selectedSessions.length > 0" class="mt-3.5 space-y-2.5">
                        <Link
                            v-for="session in selectedSessions"
                            :key="session.id"
                            :href="session.detailUrl"
                            class="group block rounded-xl bg-white p-3.5 ring-1 ring-line/80 transition-all hover:ring-primary-600/35"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="line-clamp-2 text-xs font-semibold text-navy-950 group-hover:text-primary-600">
                                    {{ session.title }}
                                </p>
                                <ArrowUpRight
                                    class="size-3.5 shrink-0 text-muted transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-primary-600"
                                    aria-hidden="true"
                                />
                            </div>

                            <div class="mt-2 space-y-1 text-[11.5px] text-muted">
                                <p class="tabular flex items-center gap-1.5">
                                    <Clock class="size-3 shrink-0 text-accent-600" aria-hidden="true" />
                                    <span>{{ session.timeLabel }}</span>
                                </p>
                                <p class="flex items-center gap-1.5 truncate">
                                    <MapPin class="size-3 shrink-0 text-primary-600" aria-hidden="true" />
                                    <span class="truncate">{{ session.venue || 'Venue TBA' }}</span>
                                </p>
                            </div>

                            <div class="mt-2.5 flex items-center justify-between gap-2 border-t border-line/70 pt-2">
                                <span class="tabular inline-flex items-center gap-1 text-[11px] font-semibold text-ink">
                                    <Users class="size-3 text-muted" aria-hidden="true" />
                                    {{ session.confirmedBookings }} / {{ session.capacity }}
                                </span>
                                <StatusBadge :tone="statusTone(session.statusValue)" dot>
                                    {{ session.status }}
                                </StatusBadge>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="mt-6 rounded-xl border border-dashed border-line-strong/70 bg-white/70 px-4 py-6 text-center">
                        <p class="text-xs font-semibold text-navy-950">No sessions scheduled</p>
                        <p class="mt-1 text-[11.5px] text-muted">
                            Pick a highlighted date or schedule a new training session.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-2 border-t border-line/70 pt-3">
                    <Link
                        :href="allSessionsHref"
                        class="text-xs font-semibold text-muted transition-colors hover:text-navy-950"
                    >
                        All sessions
                    </Link>
                    <Link
                        v-if="createSessionHref"
                        :href="createSessionHref"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 transition-colors hover:text-primary-700"
                    >
                        <Plus class="size-3.5" aria-hidden="true" />
                        <span>Schedule</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
