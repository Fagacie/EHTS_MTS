<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuPortal,
    DropdownMenuRoot,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from 'reka-ui';
import {
    ArrowUpRight,
    CalendarDays,
    CheckCircle2,
    Lock,
    Megaphone,
    MoreHorizontal,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Send,
    Trash2,
    X,
    XCircle,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface SessionItem {
    id: number;
    title: string;
    sessionCode: string;
    startsAt: string | null;
    venue: string;
    module: { code: string; title: string } | null;
    instructors: string[];
    confirmedBookings: number;
    reservedSeats: number;
    capacity: number;
    price: string;
    status: string;
    statusValue: string;
    editUrl: string;
    detailUrl: string;
}

type SessionRow = SessionItem & Record<string, unknown>;

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface AdminSessionsProps extends EtmsSharedProps {
    sessions: Paginator<SessionItem>;
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
}

const props = defineProps<AdminSessionsProps>();
const filters = reactive({ ...props.filters });
const busyAction = reactive<Record<number, string | null>>({});
const dialog = ref<{ session: SessionItem; action: 'cancel' | 'notify' | 'delete' } | null>(null);
const message = ref('');

const dialogOpen = computed({
    get: () => dialog.value !== null,
    set: (open: boolean) => {
        if (!open) {
            dialog.value = null;
            message.value = '';
        }
    },
});

const hasActiveFilters = computed(
    () => filters.q.trim() !== '' || filters.status !== '',
);

const rows = computed<SessionRow[]>(() => props.sessions.data as SessionRow[]);

const columns: DataTableColumn[] = [
    { key: 'session', label: 'Session' },
    { key: 'schedule', label: 'Schedule', tabular: true },
    { key: 'price', label: 'Price', align: 'right', tabular: true },
    { key: 'status', label: 'Status' },
];

function applyFilters(): void {
    router.get(
        '/admin-vue/sessions',
        {
            q: filters.q.trim() || undefined,
            status: filters.status || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function selectStatus(statusValue: string): void {
    filters.status = statusValue;
    applyFilters();
}

function clearFilters(): void {
    filters.q = '';
    filters.status = '';
    applyFilters();
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Date TBA';
    }

    return new Intl.DateTimeFormat('en-MY', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: props.app.displayTimezone,
    }).format(new Date(value));
}

function statusTone(statusValue: string): StatusTone {
    if (['published', 'completed'].includes(statusValue)) return 'success';
    if (['draft', 'full', 'registration_closed'].includes(statusValue)) return 'warning';
    if (statusValue === 'cancelled') return 'danger';
    return 'primary';
}

function runAction(
    session: SessionItem,
    action: 'publish' | 'close-registration' | 'complete' | 'reconcile-seats',
): void {
    busyAction[session.id] = action;
    router.post(
        `/admin-vue/sessions/${session.id}/${action}`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                busyAction[session.id] = null;
            },
        },
    );
}

function openDialog(session: SessionItem, action: 'cancel' | 'notify' | 'delete'): void {
    dialog.value = { session, action };
    message.value = '';
}

function submitDialog(): void {
    if (!dialog.value) return;
    const current = dialog.value;

    if (current.action === 'delete') {
        busyAction[current.session.id] = 'delete';
        router.delete(`/admin-vue/sessions/${current.session.id}`, {
            preserveScroll: true,
            onFinish: () => {
                busyAction[current.session.id] = null;
                dialog.value = null;
            },
        });
        return;
    }

    if (message.value.trim() === '') return;

    busyAction[current.session.id] = current.action;
    router.post(
        `/admin-vue/sessions/${current.session.id}/${current.action === 'cancel' ? 'cancel' : 'notify'}`,
        current.action === 'cancel'
            ? { cancellation_reason: message.value.trim() }
            : { update_message: message.value.trim() },
        {
            preserveScroll: true,
            onFinish: () => {
                busyAction[current.session.id] = null;
                dialog.value = null;
                message.value = '';
            },
        },
    );
}

function decodeLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <Head title="Live Sessions" />

    <AdminLayout breadcrumb="Live Sessions">
        <div class="space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Live sessions</h1>
                    <p class="mt-1 text-sm text-muted">
                        Schedule, publish, and manage instructor-led training sessions.
                    </p>
                </div>

                <AppButton href="/admin-vue/sessions/create" variant="warm" size="sm" pill>
                    <Plus class="size-3.5" aria-hidden="true" />
                    <span>Create session</span>
                </AppButton>
            </header>

            <!-- Filter & Search Bar -->
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 xl:flex-row xl:items-center xl:justify-between">
                <!-- Status Filter Pills -->
                <div
                    role="navigation"
                    aria-label="Filter sessions by status"
                    class="flex flex-wrap items-center gap-1.5"
                >
                    <button
                        type="button"
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            filters.status === ''
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectStatus('')"
                    >
                        All
                    </button>

                    <button
                        v-for="statusOption in statuses"
                        :key="statusOption.value"
                        type="button"
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            filters.status === statusOption.value
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectStatus(statusOption.value)"
                    >
                        {{ statusOption.label }}
                    </button>
                </div>

                <!-- Search Form -->
                <form class="flex items-center gap-2" @submit.prevent="applyFilters">
                    <div class="relative flex-1 xl:w-72">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            v-model="filters.q"
                            type="search"
                            placeholder="Search title or code..."
                            aria-label="Search training sessions"
                            class="h-9 w-full rounded-xl border border-line-strong/80 bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-9 items-center rounded-xl bg-navy-950 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900"
                    >
                        Search
                    </button>

                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-white px-3 text-xs font-semibold text-muted transition-colors hover:bg-sand-50 hover:text-navy-950"
                        @click="clearFilters"
                    >
                        <RotateCcw class="size-3.5" aria-hidden="true" />
                        <span>Reset</span>
                    </button>
                </form>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="rows.length === 0"
                :icon="CalendarDays"
                title="No training sessions found"
                :description="
                    hasActiveFilters
                        ? 'Try clearing your search term or switching to all statuses.'
                        : 'Create your first instructor-led training session to start accepting registrations.'
                "
            >
                <template #action>
                    <div class="flex flex-wrap items-center justify-center gap-2.5">
                        <AppButton
                            v-if="hasActiveFilters"
                            variant="outline"
                            size="sm"
                            pill
                            @click="clearFilters"
                        >
                            Reset filters
                        </AppButton>
                        <AppButton href="/admin-vue/sessions/create" variant="warm" size="sm" pill>
                            <Plus class="size-3.5" aria-hidden="true" />
                            <span>Create session</span>
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <!-- Table + Pagination -->
            <template v-else>
                <DataTable
                    :columns="columns"
                    :rows="rows"
                    row-key="id"
                >
                    <template #cell-session="{ row }">
                        <Link
                            :href="row.detailUrl"
                            class="font-semibold text-navy-950 transition-colors hover:text-primary-600"
                        >
                            {{ row.title }}
                        </Link>
                        <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted">
                            <span class="tabular-nums-mono font-medium">{{ row.sessionCode }}</span>
                            <span>&middot;</span>
                            <span>{{ row.instructors.join(', ') || 'Unassigned' }}</span>
                        </p>
                    </template>

                    <template #cell-schedule="{ row }">
                        <span class="tabular text-sm text-ink">
                            {{ formatDate(row.startsAt) }}
                        </span>
                    </template>

                    <template #cell-price="{ row }">
                        <span class="tabular font-semibold text-navy-950">
                            MYR {{ row.price }}
                        </span>
                    </template>

                    <template #cell-status="{ row }">
                        <StatusBadge :tone="statusTone(row.statusValue)" dot>
                            {{ row.status }}
                        </StatusBadge>
                    </template>

                    <template #actions="{ row }">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <Link
                                :href="row.detailUrl"
                                class="inline-flex h-8 items-center gap-1 rounded-full bg-sand-50 px-3 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-navy-950 hover:text-white hover:ring-navy-950"
                            >
                                <span>Manage</span>
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            </Link>

                            <Link
                                :href="row.editUrl"
                                class="inline-flex size-8 items-center justify-center rounded-full text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                                title="Edit session"
                                aria-label="Edit session"
                            >
                                <Pencil class="size-3.5" aria-hidden="true" />
                            </Link>

                            <DropdownMenuRoot>
                                <DropdownMenuTrigger as-child>
                                    <button
                                        type="button"
                                        class="inline-flex size-8 items-center justify-center rounded-full text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                                        aria-label="More session actions"
                                    >
                                        <MoreHorizontal class="size-4" aria-hidden="true" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuPortal>
                                    <DropdownMenuContent
                                        align="end"
                                        :side-offset="4"
                                        class="z-50 w-48 rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-neutral-200"
                                    >
                                        <DropdownMenuItem
                                            v-if="row.statusValue === 'draft'"
                                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                            @select="runAction(row, 'publish')"
                                        >
                                            <Send class="size-3.5 text-success-600" aria-hidden="true" />
                                            <span>Publish session</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="row.statusValue === 'published'"
                                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                            @select="runAction(row, 'close-registration')"
                                        >
                                            <Lock class="size-3.5 text-warning-600" aria-hidden="true" />
                                            <span>Close registration</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="['published', 'full', 'registration_closed'].includes(row.statusValue)"
                                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                            @select="runAction(row, 'complete')"
                                        >
                                            <CheckCircle2 class="size-3.5 text-primary-600" aria-hidden="true" />
                                            <span>Mark completed</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                            @select="openDialog(row, 'notify')"
                                        >
                                            <Megaphone class="size-3.5 text-primary-600" aria-hidden="true" />
                                            <span>Notify participants</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator class="my-1 h-px bg-line" />

                                        <DropdownMenuItem
                                            v-if="row.statusValue !== 'cancelled' && row.statusValue !== 'completed'"
                                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-warning-700 outline-none hover:bg-warning-50"
                                            @select="openDialog(row, 'cancel')"
                                        >
                                            <XCircle class="size-3.5" aria-hidden="true" />
                                            <span>Cancel session</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-danger-700 outline-none hover:bg-danger-50"
                                            @select="openDialog(row, 'delete')"
                                        >
                                            <Trash2 class="size-3.5" aria-hidden="true" />
                                            <span>Delete session</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenuPortal>
                            </DropdownMenuRoot>
                        </div>
                    </template>
                </DataTable>

                <!-- Pagination Footer -->
                <div
                    v-if="sessions.last_page > 1 || sessions.total > 0"
                    class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
                >
                    <span class="tabular">
                        Showing {{ sessions.from ?? 0 }}–{{ sessions.to ?? 0 }} of {{ sessions.total }} sessions
                    </span>

                    <nav
                        v-if="sessions.last_page > 1"
                        class="flex flex-wrap items-center gap-1"
                        aria-label="Pagination"
                    >
                        <template v-for="link in sessions.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="tabular inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs font-semibold transition-colors"
                                :class="
                                    link.active
                                        ? 'bg-navy-950 text-white'
                                        : 'bg-white text-ink ring-1 ring-line hover:bg-sand-50'
                                "
                            >
                                {{ decodeLabel(link.label) }}
                            </Link>
                            <span
                                v-else
                                class="tabular inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs font-medium text-muted/45"
                            >
                                {{ decodeLabel(link.label) }}
                            </span>
                        </template>
                    </nav>
                </div>
            </template>
        </div>

        <!-- Reka UI Action Confirmation Dialog -->
        <DialogRoot v-model:open="dialogOpen">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/55 backdrop-blur-[2px]" />
                <DialogContent
                    v-if="dialog"
                    class="fixed top-1/2 left-1/2 z-50 w-[calc(100vw-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-neutral-200 focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <DialogTitle class="text-base font-bold text-navy-950">
                                {{
                                    dialog.action === 'cancel'
                                        ? 'Cancel training session'
                                        : dialog.action === 'notify'
                                            ? 'Notify participants'
                                            : 'Delete training session'
                                }}
                            </DialogTitle>
                            <p class="mt-1 text-xs text-muted">
                                <span class="tabular-nums-mono font-semibold">{{ dialog.session.sessionCode }}</span>
                                &middot; {{ dialog.session.title }}
                            </p>
                        </div>

                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted hover:bg-sand-100 hover:text-navy-950"
                            aria-label="Close dialog"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </DialogClose>
                    </div>

                    <div class="mt-4">
                        <textarea
                            v-if="dialog.action !== 'delete'"
                            v-model="message"
                            rows="4"
                            maxlength="800"
                            :placeholder="
                                dialog.action === 'cancel'
                                    ? 'Enter mandatory cancellation reason...'
                                    : 'Write an update message for confirmed participants...'
                            "
                            class="w-full rounded-xl border border-line-strong/80 bg-white p-3 text-sm text-ink placeholder:text-muted/70 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                        <p
                            v-else
                            class="rounded-xl bg-danger-50 p-3.5 text-sm text-danger-700 ring-1 ring-danger-600/15"
                        >
                            Deletion is only permitted when no bookings or certificates are associated with this session.
                        </p>
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2.5">
                        <DialogClose
                            class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                        >
                            Cancel
                        </DialogClose>

                        <button
                            type="button"
                            class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-white transition-colors disabled:opacity-50"
                            :class="
                                dialog.action === 'delete'
                                    ? 'bg-danger-600 hover:bg-danger-700'
                                    : 'bg-navy-950 hover:bg-navy-900'
                            "
                            :disabled="
                                (dialog.action !== 'delete' && !message.trim()) ||
                                Boolean(busyAction[dialog.session.id])
                            "
                            @click="submitDialog"
                        >
                            {{
                                busyAction[dialog.session.id]
                                    ? 'Saving...'
                                    : dialog.action === 'delete'
                                        ? 'Delete session'
                                        : 'Confirm'
                            }}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </AdminLayout>
</template>
