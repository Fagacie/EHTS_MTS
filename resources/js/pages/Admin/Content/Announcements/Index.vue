<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Eye,
    Mail,
    Megaphone,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Send,
    Trash2,
    X,
} from 'lucide-vue-next';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import AdminLayout from '@/layouts/AdminLayout.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface AnnouncementRow {
    [key: string]: unknown;
    id: number;
    title: string;
    body: string;
    audienceType: string;
    audienceLabel: string;
    trainingSessionId: number | null;
    moduleId: number | null;
    targetRole: string | null;
    sendEmail: boolean;
    sendInApp: boolean;
    status: string;
    statusLabel: string;
    recipients: number;
    sentAt: string | null;
}

interface Option {
    value: string;
    label: string;
}

interface EntityOption {
    id: number;
    label: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props extends EtmsSharedProps {
    announcements: {
        data: AnnouncementRow[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginationLink[];
    };
    filters: { q: string; status: string };
    statuses: Option[];
    roles: Option[];
    sessions: EntityOption[];
    modules: EntityOption[];
}

const props = defineProps<Props>();
const page = usePage<EtmsSharedProps>();

const validationErrors = computed<Record<string, string>>(
    () => (page.props.errors as Record<string, string>) ?? {},
);

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});

const hasActiveFilters = computed(() => Boolean(filters.q || filters.status));

const columns: DataTableColumn[] = [
    { key: 'announcement', label: 'Announcement' },
    { key: 'audience', label: 'Audience' },
    { key: 'channels', label: 'Channels' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function apply(): void {
    router.get(
        '/admin-vue/announcements',
        {
            ...(filters.q ? { q: filters.q } : {}),
            ...(filters.status ? { status: filters.status } : {}),
        },
        { preserveState: true, replace: true },
    );
}

function setStatusFilter(val: string): void {
    filters.status = val;
    apply();
}

function clearFilters(): void {
    filters.q = '';
    filters.status = '';
    apply();
}

function statusTone(status: string): 'success' | 'warning' | 'danger' | 'neutral' {
    if (status === 'sent') return 'success';
    if (status === 'scheduled' || status === 'sending') return 'warning';
    if (status === 'failed') return 'danger';
    return 'neutral';
}

/* Create / Edit Dialog */
const modalOpen = ref(false);
const editing = ref<AnnouncementRow | null>(null);
const busy = ref(false);

const form = reactive<{
    title: string;
    body: string;
    audience_type: string;
    training_session_id: number | '';
    module_id: number | '';
    target_role: string;
    send_email: boolean;
    send_in_app: boolean;
}>({
    title: '',
    body: '',
    audience_type: 'all_customers',
    training_session_id: '',
    module_id: '',
    target_role: '',
    send_email: true,
    send_in_app: true,
});

function beginCreate(): void {
    editing.value = null;
    Object.assign(form, {
        title: '',
        body: '',
        audience_type: 'all_customers',
        training_session_id: '',
        module_id: '',
        target_role: '',
        send_email: true,
        send_in_app: true,
    });
    modalOpen.value = true;
}

function beginEdit(a: AnnouncementRow): void {
    editing.value = a;
    Object.assign(form, {
        title: a.title,
        body: a.body,
        audience_type: a.audienceType,
        training_session_id: a.trainingSessionId ?? '',
        module_id: a.moduleId ?? '',
        target_role: a.targetRole ?? '',
        send_email: a.sendEmail,
        send_in_app: a.sendInApp,
    });
    modalOpen.value = true;
}

function save(): void {
    busy.value = true;
    const data = {
        ...form,
        training_session_id: form.training_session_id === '' ? null : form.training_session_id,
        module_id: form.module_id === '' ? null : form.module_id,
        target_role: form.target_role || null,
    };

    const url = editing.value
        ? `/admin-vue/announcements/${editing.value.id}`
        : '/admin-vue/announcements';

    router.post(url, data, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
        },
        onSuccess: () => {
            modalOpen.value = false;
        },
    });
}

/* View Dialog */
const viewing = ref<AnnouncementRow | null>(null);

/* Confirm Send Dialog */
const sendingTarget = ref<AnnouncementRow | null>(null);
function confirmSend(): void {
    if (!sendingTarget.value) return;
    busy.value = true;
    router.post(
        `/admin-vue/announcements/${sendingTarget.value.id}/send`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                busy.value = false;
                sendingTarget.value = null;
            },
        },
    );
}

/* Confirm Delete Dialog */
const deletingTarget = ref<AnnouncementRow | null>(null);
function confirmDelete(): void {
    if (!deletingTarget.value) return;
    busy.value = true;
    router.delete(`/admin-vue/announcements/${deletingTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
            deletingTarget.value = null;
        },
    });
}

function cleanPaginationLabel(label: string): string {
    return label.replace(/&laquo;/g, '←').replace(/&raquo;/g, '→');
}
</script>

<template>
    <Head title="Announcements — Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title text-ink">Announcements</h1>
                    <p class="mt-1 text-sm text-muted">
                        Draft and dispatch email or in-app announcements to trainees, session cohorts, or roles.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                    @click="beginCreate"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    <span>New announcement</span>
                </button>
            </div>

            <!-- Filter Bar -->
            <section class="rounded-2xl border border-line/85 bg-surface p-4 shadow-card">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                !filters.status
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-sand-100 text-muted hover:text-ink'
                            "
                            @click="setStatusFilter('')"
                        >
                            All
                        </button>
                        <button
                            v-for="s in statuses"
                            :key="s.value"
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                filters.status === s.value
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-sand-100 text-muted hover:text-ink'
                            "
                            @click="setStatusFilter(s.value)"
                        >
                            {{ s.label }}
                        </button>
                    </div>

                    <form class="flex flex-wrap items-center gap-2" @submit.prevent="apply">
                        <label class="relative min-w-[220px] flex-1 sm:flex-initial">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted"
                                aria-hidden="true"
                            />
                            <input
                                v-model="filters.q"
                                type="search"
                                placeholder="Search announcement title..."
                                class="h-9 w-full rounded-lg border border-line bg-surface pr-3 pl-9 text-sm text-ink placeholder:text-muted/70 focus:border-primary-600 focus:outline-none"
                            />
                        </label>
                        <button
                            type="submit"
                            class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-3.5 text-sm font-medium text-white transition hover:bg-navy-900"
                        >
                            Search
                        </button>
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-line bg-surface px-3 text-sm font-medium text-muted transition hover:bg-sand-100 hover:text-ink"
                            @click="clearFilters"
                        >
                            <RotateCcw class="size-3.5" aria-hidden="true" />
                            <span>Reset</span>
                        </button>
                    </form>
                </div>
            </section>

            <!-- Announcements Table -->
            <DataTable :columns="columns" :rows="announcements.data" row-key="id">
                <template #cell-announcement="{ row }">
                    <div class="max-w-md">
                        <p class="font-medium text-ink">{{ (row as AnnouncementRow).title }}</p>
                        <p class="mt-0.5 line-clamp-1 text-xs text-muted">
                            {{ (row as AnnouncementRow).body }}
                        </p>
                    </div>
                </template>

                <template #cell-audience="{ row }">
                    <p class="text-sm text-ink">{{ (row as AnnouncementRow).audienceLabel }}</p>
                    <p class="tabular text-xs text-muted">
                        {{ (row as AnnouncementRow).recipients }} recipient{{
                            (row as AnnouncementRow).recipients === 1 ? '' : 's'
                        }}
                    </p>
                </template>

                <template #cell-channels="{ row }">
                    <div class="flex items-center gap-2 text-xs text-muted">
                        <span
                            v-if="(row as AnnouncementRow).sendEmail"
                            class="inline-flex items-center gap-1 rounded-md bg-sand-100 px-2 py-0.5 font-medium text-ink"
                        >
                            <Mail class="size-3 text-muted" aria-hidden="true" />
                            Email
                        </span>
                        <span
                            v-if="(row as AnnouncementRow).sendInApp"
                            class="inline-flex items-center gap-1 rounded-md bg-sand-100 px-2 py-0.5 font-medium text-ink"
                        >
                            <Bell class="size-3 text-muted" aria-hidden="true" />
                            In-app
                        </span>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <div>
                        <StatusBadge
                            :tone="statusTone((row as AnnouncementRow).status)"
                            :label="(row as AnnouncementRow).statusLabel"
                        />
                        <p v-if="(row as AnnouncementRow).sentAt" class="mt-1 text-[11px] text-muted">
                            {{ (row as AnnouncementRow).sentAt }}
                        </p>
                    </div>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1.5">
                        <button
                            type="button"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-surface px-2.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                            @click="viewing = row as AnnouncementRow"
                        >
                            <Eye class="size-3.5 text-muted" aria-hidden="true" />
                            <span>View</span>
                        </button>

                        <button
                            v-if="(row as AnnouncementRow).status !== 'sent'"
                            type="button"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-surface px-2.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                            @click="beginEdit(row as AnnouncementRow)"
                        >
                            <Pencil class="size-3.5 text-muted" aria-hidden="true" />
                            <span>Edit</span>
                        </button>

                        <button
                            v-if="['draft', 'scheduled', 'failed'].includes((row as AnnouncementRow).status)"
                            type="button"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-navy-950 px-2.5 text-xs font-semibold text-white transition hover:bg-navy-900"
                            @click="sendingTarget = row as AnnouncementRow"
                        >
                            <Send class="size-3.5" aria-hidden="true" />
                            <span>Send</span>
                        </button>

                        <button
                            v-if="(row as AnnouncementRow).status === 'draft'"
                            type="button"
                            aria-label="Delete draft"
                            class="inline-flex size-8 items-center justify-center rounded-lg border border-line bg-surface text-muted transition hover:border-danger-200 hover:bg-danger-50 hover:text-danger-600"
                            @click="deletingTarget = row as AnnouncementRow"
                        >
                            <Trash2 class="size-3.5" aria-hidden="true" />
                        </button>
                    </div>
                </template>

                <template #empty>
                    <div class="py-10 text-center">
                        <Megaphone class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                        <p class="mt-2 text-sm font-medium text-ink">No announcements found</p>
                        <p class="mt-0.5 text-xs text-muted">
                            Create a draft announcement to notify trainees or session participants.
                        </p>
                    </div>
                </template>
            </DataTable>

            <!-- Pagination -->
            <div
                v-if="announcements.total > 0"
                class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
            >
                <p>
                    Showing <strong class="font-medium text-ink">{{ announcements.from ?? 0 }}</strong> to
                    <strong class="font-medium text-ink">{{ announcements.to ?? 0 }}</strong> of
                    <strong class="font-medium text-ink">{{ announcements.total }}</strong> announcements
                </p>

                <nav v-if="announcements.links.length > 3" class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, idx) in announcements.links" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 font-medium transition"
                            :class="
                                link.active
                                    ? 'bg-navy-950 text-white'
                                    : 'border border-line bg-surface text-ink hover:bg-sand-100'
                            "
                        >
                            {{ cleanPaginationLabel(link.label) }}
                        </Link>
                        <span
                            v-else
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-muted/50"
                        >
                            {{ cleanPaginationLabel(link.label) }}
                        </span>
                    </template>
                </nav>
            </div>
        </div>

        <!-- Create / Edit Announcement Dialog -->
        <DialogRoot :open="modalOpen" @update:open="modalOpen = $event">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 max-h-[90vh] w-[94vw] max-w-xl -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <DialogTitle class="text-lg font-semibold text-ink">
                                {{ editing ? 'Edit announcement' : 'New announcement' }}
                            </DialogTitle>
                            <DialogDescription class="mt-1 text-sm text-muted">
                                Save as a draft first, then review and send when ready.
                            </DialogDescription>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-lg text-muted transition hover:bg-sand-100 hover:text-ink"
                            aria-label="Close"
                        >
                            <X class="size-4" />
                        </DialogClose>
                    </div>

                    <div
                        v-if="Object.keys(validationErrors).length"
                        class="mt-4 rounded-xl border border-danger-200 bg-danger-50 px-3.5 py-2.5 text-xs text-danger-700"
                    >
                        <p v-for="(msg, key) in validationErrors" :key="key">{{ msg }}</p>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="save">
                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Title</span>
                            <input
                                v-model="form.title"
                                required
                                maxlength="200"
                                placeholder="e.g., Session venue update for HEC-RAS workshop"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Message</span>
                            <textarea
                                v-model="form.body"
                                required
                                rows="5"
                                maxlength="10000"
                                placeholder="Write the announcement body..."
                                class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Audience</span>
                            <select
                                v-model="form.audience_type"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option value="all_customers">All active customers</option>
                                <option value="session_participants">Session participants and instructors</option>
                                <option value="module_entitled_users">Module entitled users</option>
                                <option value="role">Specific role</option>
                            </select>
                        </label>

                        <label
                            v-if="form.audience_type === 'session_participants'"
                            class="flex flex-col gap-1 text-xs font-medium text-muted"
                        >
                            <span>Training session</span>
                            <select
                                v-model="form.training_session_id"
                                required
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option value="">Select session</option>
                                <option v-for="s in sessions" :key="s.id" :value="s.id">
                                    {{ s.label }}
                                </option>
                            </select>
                        </label>

                        <label
                            v-if="form.audience_type === 'module_entitled_users'"
                            class="flex flex-col gap-1 text-xs font-medium text-muted"
                        >
                            <span>Training module</span>
                            <select
                                v-model="form.module_id"
                                required
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option value="">Select module</option>
                                <option v-for="m in modules" :key="m.id" :value="m.id">
                                    {{ m.label }}
                                </option>
                            </select>
                        </label>

                        <label
                            v-if="form.audience_type === 'role'"
                            class="flex flex-col gap-1 text-xs font-medium text-muted"
                        >
                            <span>Target role</span>
                            <select
                                v-model="form.target_role"
                                required
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option value="">Select role</option>
                                <option v-for="r in roles" :key="r.value" :value="r.value">
                                    {{ r.label }}
                                </option>
                            </select>
                        </label>

                        <div class="rounded-xl border border-line bg-sand-50/60 p-3.5">
                            <p class="text-xs font-semibold text-ink">Delivery channels</p>
                            <div class="mt-2.5 flex flex-wrap gap-5">
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
                                    <input
                                        v-model="form.send_email"
                                        type="checkbox"
                                        class="size-4 rounded accent-navy-950"
                                    />
                                    <span>Send email</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
                                    <input
                                        v-model="form.send_in_app"
                                        type="checkbox"
                                        class="size-4 rounded accent-navy-950"
                                    />
                                    <span>Send in-app notification</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 border-t border-line pt-4">
                            <button
                                type="button"
                                class="h-10 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                                @click="modalOpen = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="busy || (!form.send_email && !form.send_in_app)"
                                class="h-10 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-50"
                            >
                                {{ busy ? 'Saving…' : editing ? 'Save changes' : 'Create draft' }}
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- View Announcement Dialog -->
        <DialogRoot :open="Boolean(viewing)" @update:open="(v) => !v && (viewing = null)">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 max-h-[85vh] w-[92vw] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <DialogTitle class="text-lg font-semibold text-ink">
                                {{ viewing?.title }}
                            </DialogTitle>
                            <DialogDescription class="mt-1 text-xs text-muted">
                                {{ viewing?.audienceLabel }} · {{ viewing?.recipients }} recipients
                                <span v-if="viewing?.sentAt"> · Sent {{ viewing.sentAt }}</span>
                            </DialogDescription>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-lg text-muted transition hover:bg-sand-100 hover:text-ink"
                            aria-label="Close"
                        >
                            <X class="size-4" />
                        </DialogClose>
                    </div>

                    <div class="mt-4 rounded-xl border border-line bg-sand-50/60 p-4 text-sm leading-relaxed whitespace-pre-wrap text-ink">
                        {{ viewing?.body }}
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button
                            type="button"
                            class="h-9 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                            @click="viewing = null"
                        >
                            Close
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Confirm Send Dialog -->
        <DialogRoot :open="Boolean(sendingTarget)" @update:open="(v) => !v && (sendingTarget = null)">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 w-[92vw] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <DialogTitle class="text-lg font-semibold text-ink">Send announcement now?</DialogTitle>
                    <DialogDescription class="mt-1 text-sm text-muted">
                        <strong>{{ sendingTarget?.title }}</strong> will be dispatched immediately to
                        {{ sendingTarget?.audienceLabel }}.
                    </DialogDescription>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="h-10 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                            @click="sendingTarget = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="busy"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-50"
                            @click="confirmSend"
                        >
                            <Send class="size-4" aria-hidden="true" />
                            <span>{{ busy ? 'Sending…' : 'Send now' }}</span>
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Confirm Delete Dialog -->
        <DialogRoot :open="Boolean(deletingTarget)" @update:open="(v) => !v && (deletingTarget = null)">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 w-[92vw] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <DialogTitle class="text-lg font-semibold text-ink">Delete draft?</DialogTitle>
                    <DialogDescription class="mt-1 text-sm text-muted">
                        "{{ deletingTarget?.title }}" will be permanently removed.
                    </DialogDescription>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="h-10 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                            @click="deletingTarget = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="busy"
                            class="h-10 rounded-xl bg-danger-600 px-4 text-sm font-semibold text-white transition hover:bg-danger-700 disabled:opacity-50"
                            @click="confirmDelete"
                        >
                            {{ busy ? 'Deleting…' : 'Delete draft' }}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </AdminLayout>
</template>
