<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CalendarDays,
    CheckCircle2,
    ClipboardCheck,
    Clock,
    ExternalLink,
    FileText,
    MapPin,
    Users,
    Video,
} from 'lucide-vue-next';
import AdminCalendarCard from '@/components/ds/AdminCalendarCard.vue';
import KpiCard from '@/components/ds/KpiCard.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorDashboardProps } from '@/types/etms';

const props = defineProps<InstructorDashboardProps>();

type SessionFilter = 'all' | 'upcoming' | 'completed';
const sessionFilter = ref<SessionFilter>('all');

const featuredSession = computed(() => {
    const active = props.assignedSessions.find(
        (s) => s.statusValue !== 'completed' && s.statusValue !== 'cancelled',
    );
    return active ?? props.assignedSessions[0] ?? null;
});

const featuredFillPct = computed(() => {
    const s = featuredSession.value;
    if (!s || s.capacity <= 0) return 0;
    return Math.min(100, Math.round((s.confirmedParticipantsCount / s.capacity) * 100));
});

const filteredSessions = computed(() => {
    if (sessionFilter.value === 'upcoming') {
        return props.assignedSessions.filter(
            (s) => s.statusValue !== 'completed' && s.statusValue !== 'cancelled',
        );
    }
    if (sessionFilter.value === 'completed') {
        return props.assignedSessions.filter((s) => s.statusValue === 'completed');
    }
    return props.assignedSessions;
});

function sessionStatusTone(statusValue: string): 'success' | 'warning' | 'danger' | 'neutral' {
    if (statusValue === 'published' || statusValue === 'completed') return 'success';
    if (statusValue === 'full' || statusValue === 'registration_closed') return 'warning';
    if (statusValue === 'cancelled') return 'danger';
    return 'neutral';
}
</script>

<template>
    <Head title="Instructor Dashboard — Ecohydrotech Training" />

    <InstructorLayout breadcrumb="Dashboard">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title text-ink">
                        Welcome back, {{ instructor.displayName }}
                    </h1>
                    <p class="mt-1 text-sm text-muted">
                        {{ instructor.email }} · Manage your teaching schedule, attendance, and session recordings.
                    </p>
                </div>

                <div v-if="instructor.isPublic && instructor.publicProfileUrl" class="flex items-center gap-2.5">
                    <StatusBadge tone="success" label="Public profile active" />
                    <a
                        :href="instructor.publicProfileUrl"
                        target="_blank"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-line bg-surface px-3.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                    >
                        <span>View public profile</span>
                        <ExternalLink class="size-3.5 text-muted" aria-hidden="true" />
                    </a>
                </div>
            </div>

            <!-- KPI Strip -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <KpiCard
                    label="Assigned sessions"
                    :value="kpis.assignedSessionsCount"
                    :caption="`${kpis.upcomingSessionsCount ?? 0} upcoming · ${kpis.completedSessionsCount ?? 0} completed`"
                    :icon="CalendarDays"
                    variant="dark"
                    href="/instructor/sessions"
                />
                <KpiCard
                    label="Confirmed trainees"
                    :value="kpis.totalConfirmedParticipants ?? 0"
                    caption="Across active & completed cohorts"
                    :icon="Users"
                    href="/instructor/sessions"
                />
                <KpiCard
                    label="Attendance pending"
                    :value="kpis.pendingAttendanceCount"
                    caption="Sessions awaiting submission"
                    :icon="Clock"
                    href="/instructor/sessions"
                />
                <KpiCard
                    label="Recordings & materials"
                    :value="kpis.submittedRecordingsCount"
                    :caption="`${kpis.submittedMaterialsCount ?? 0} teaching materials uploaded`"
                    :icon="Video"
                    href="/instructor/sessions"
                />
            </section>

            <!-- Next Session + Action Items & Teaching Calendar -->
            <section class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                <!-- Left: Featured Session & Pending Actions -->
                <div class="flex flex-col gap-5 lg:col-span-5">
                    <!-- Featured / Next Session Card -->
                    <div class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-ink">Next session</h2>
                            <StatusBadge
                                v-if="featuredSession"
                                :tone="sessionStatusTone(featuredSession.statusValue)"
                                :label="featuredSession.statusLabel"
                            />
                        </div>

                        <div v-if="!featuredSession" class="py-8 text-center">
                            <CalendarDays class="mx-auto size-7 text-muted/60" aria-hidden="true" />
                            <p class="mt-2 text-sm font-medium text-ink">No sessions assigned yet</p>
                            <p class="mt-0.5 text-xs text-muted">
                                Assigned cohorts will appear here once scheduled by an administrator.
                            </p>
                        </div>

                        <div v-else class="mt-3.5 space-y-4">
                            <div>
                                <p class="tabular-nums-mono text-xs font-semibold text-primary-600">
                                    {{ featuredSession.sessionCode }} · {{ featuredSession.roleLabel }}
                                </p>
                                <h3 class="mt-1 text-base font-semibold text-ink">
                                    <Link :href="featuredSession.showUrl" class="hover:text-primary-600">
                                        {{ featuredSession.title }}
                                    </Link>
                                </h3>
                                <div class="mt-2 space-y-1 text-xs text-muted">
                                    <p class="flex items-center gap-1.5">
                                        <Clock class="size-3.5 shrink-0" aria-hidden="true" />
                                        <span>{{ featuredSession.scheduleLabel }}</span>
                                    </p>
                                    <p class="flex items-center gap-1.5">
                                        <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                                        <span>{{ featuredSession.venueName }} ({{ featuredSession.venueCity }})</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Seat Fill Progress -->
                            <div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted">Confirmed seats</span>
                                    <span class="tabular font-semibold text-ink">
                                        {{ featuredSession.confirmedParticipantsCount }} / {{ featuredSession.capacity }}
                                    </span>
                                </div>
                                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-sand-100">
                                    <div
                                        class="h-full rounded-full bg-navy-950 transition-all"
                                        :style="{ width: `${featuredFillPct}%` }"
                                    />
                                </div>
                            </div>

                            <!-- Direct Teaching Actions -->
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <Link
                                    :href="featuredSession.attendanceUrl"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-navy-950 px-3 text-xs font-semibold text-white transition hover:bg-navy-900"
                                >
                                    <CheckCircle2 class="size-3.5" aria-hidden="true" />
                                    <span>Attendance</span>
                                </Link>
                                <Link
                                    :href="featuredSession.showUrl"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                                >
                                    <Users class="size-3.5 text-muted" aria-hidden="true" />
                                    <span>View session</span>
                                </Link>
                                <Link
                                    :href="featuredSession.completionUrl"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                                >
                                    <FileText class="size-3.5 text-muted" aria-hidden="true" />
                                    <span>Completion</span>
                                </Link>
                                <Link
                                    :href="featuredSession.recordingsUrl"
                                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                                >
                                    <Video class="size-3.5 text-muted" aria-hidden="true" />
                                    <span>Recordings</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Action Items Card -->
                    <div class="flex-1 rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-ink">Action items</h2>
                            <span
                                v-if="(actionItems?.length ?? 0) > 0"
                                class="tabular rounded-full bg-warning-50 px-2 py-0.5 text-[11px] font-semibold text-warning-700"
                            >
                                {{ actionItems?.length }} pending
                            </span>
                        </div>

                        <div
                            v-if="!actionItems || actionItems.length === 0"
                            class="mt-4 flex items-center gap-3 rounded-xl border border-line/70 bg-sand-50/70 px-3.5 py-3 text-xs text-muted"
                        >
                            <ClipboardCheck class="size-4 shrink-0 text-success-600" aria-hidden="true" />
                            <span>You're all caught up — no pending attendance or completion tasks.</span>
                        </div>

                        <ul v-else class="mt-3 divide-y divide-line/70">
                            <li v-for="item in actionItems" :key="item.id" class="py-2.5 first:pt-1 last:pb-0">
                                <Link
                                    :href="item.href"
                                    class="group flex items-center justify-between gap-3 rounded-lg transition"
                                >
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-ink group-hover:text-primary-600">
                                            {{ item.label }}
                                        </p>
                                        <p class="truncate text-xs text-muted">{{ item.detail }}</p>
                                    </div>
                                    <ArrowUpRight
                                        class="size-4 shrink-0 text-muted transition group-hover:text-primary-600"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Right: Interactive Teaching Calendar -->
                <div class="lg:col-span-7">
                    <AdminCalendarCard
                        :sessions="calendarSessions ?? []"
                        :initial-date="todayYmd"
                        title="Teaching schedule"
                        all-sessions-href="/instructor/sessions"
                        :create-session-href="null"
                    />
                </div>
            </section>

            <!-- Assigned Sessions List -->
            <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                <div class="flex flex-col gap-3 border-b border-line/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-ink">Assigned training sessions</h2>
                        <p class="mt-0.5 text-xs text-muted">
                            All physical cohorts assigned to you with quick access to details, attendance, and recordings.
                        </p>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                sessionFilter === 'all'
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-sand-100 text-muted hover:text-ink'
                            "
                            @click="sessionFilter = 'all'"
                        >
                            All ({{ assignedSessions.length }})
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                sessionFilter === 'upcoming'
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-sand-100 text-muted hover:text-ink'
                            "
                            @click="sessionFilter = 'upcoming'"
                        >
                            Upcoming
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                sessionFilter === 'completed'
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-sand-100 text-muted hover:text-ink'
                            "
                            @click="sessionFilter = 'completed'"
                        >
                            Completed
                        </button>
                    </div>
                </div>

                <div v-if="filteredSessions.length === 0" class="px-5 py-10 text-center">
                    <CalendarDays class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                    <p class="mt-2 text-sm font-medium text-ink">No sessions in this view</p>
                    <p class="mt-0.5 text-xs text-muted">
                        Switch filters above or view all assigned sessions.
                    </p>
                </div>

                <div v-else class="divide-y divide-line/70">
                    <div
                        v-for="session in filteredSessions"
                        :key="session.id"
                        class="flex flex-col gap-4 px-5 py-4 transition-colors hover:bg-sand-50/50 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="tabular-nums-mono rounded-md bg-sand-100 px-2 py-0.5 text-xs font-semibold text-navy-950">
                                    {{ session.sessionCode }}
                                </span>
                                <StatusBadge
                                    :tone="sessionStatusTone(session.statusValue)"
                                    :label="session.statusLabel"
                                />
                                <span class="text-xs font-medium text-muted">
                                    {{ session.roleLabel }}
                                </span>
                            </div>

                            <h3 class="mt-1.5 text-base font-semibold text-ink">
                                <Link :href="session.showUrl" class="transition-colors hover:text-primary-600">
                                    {{ session.title }}
                                </Link>
                            </h3>

                            <p class="mt-0.5 text-xs text-muted">
                                {{ session.scheduleLabel }} · {{ session.venueName }} ({{ session.venueCity }})
                            </p>

                            <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-muted">
                                <span class="tabular inline-flex items-center gap-1.5">
                                    <Users class="size-3.5" aria-hidden="true" />
                                    <strong class="font-medium text-ink">{{ session.confirmedParticipantsCount }}</strong> / {{ session.capacity }} seats
                                </span>
                                <span class="tabular inline-flex items-center gap-1.5">
                                    <Video class="size-3.5" aria-hidden="true" />
                                    <strong class="font-medium text-ink">{{ session.recordingsCount }}</strong> recordings
                                </span>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                            <Link
                                :href="session.showUrl"
                                class="inline-flex h-8 items-center rounded-lg border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                            >
                                View session
                            </Link>
                            <Link
                                :href="session.completionUrl"
                                class="inline-flex h-8 items-center rounded-lg border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                            >
                                Completion
                            </Link>
                            <Link
                                :href="session.recordingsUrl"
                                class="inline-flex h-8 items-center rounded-lg border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                            >
                                Recordings
                            </Link>
                            <Link
                                :href="session.attendanceUrl"
                                class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-navy-950 px-3 text-xs font-semibold text-white transition hover:bg-navy-900"
                            >
                                <CheckCircle2 class="size-3.5" aria-hidden="true" />
                                <span>Attendance</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </InstructorLayout>
</template>
