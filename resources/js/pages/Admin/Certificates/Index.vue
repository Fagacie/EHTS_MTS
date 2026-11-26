<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import {
    AlertCircle,
    Award,
    Plus,
    RotateCcw,
    Search,
    Settings,
    ShieldOff,
    X,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface CertificateItem {
    id: number;
    number: string;
    trainee: string;
    title: string;
    recordType: string;
    completionDate: string | null;
    issuedAt: string | null;
    status: string;
}

type CertificateRow = CertificateItem & Record<string, unknown>;

interface Props extends EtmsSharedProps {
    certificates: {
        data: CertificateItem[];
        from: number | null;
        to: number | null;
        total: number;
        last_page?: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
    trainees: { id: number; name: string; email: string }[];
    sessions: { id: number; session_code: string; title: string }[];
    modules: { id: number; code: string; title: string }[];
}

const props = defineProps<Props>();
const page = usePage();
const serverErrors = computed(() => {
    const errors = (page.props as { errors?: Record<string, string> }).errors ?? {};
    return Object.values(errors).filter(Boolean);
});
const filters = reactive({ ...props.filters });
const issueOpen = ref(false);
const revokeTarget = ref<CertificateItem | null>(null);
const reason = ref('');
const busy = ref(false);

const form = reactive({
    trainee_id: '',
    record_type: 'session' as 'session' | 'module',
    record_id: '',
    completion_date: '',
    notes: '',
});

const revokeModalOpen = computed({
    get: () => revokeTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            revokeTarget.value = null;
            reason.value = '';
        }
    },
});

const hasActiveFilters = computed(
    () => filters.q.trim() !== '' || filters.status !== '',
);

const rows = computed<CertificateRow[]>(() => props.certificates.data as CertificateRow[]);

const columns: DataTableColumn[] = [
    { key: 'certificate', label: 'Certificate', tabular: true },
    { key: 'trainee', label: 'Trainee' },
    { key: 'training', label: 'Training Programme' },
    { key: 'status', label: 'Status' },
];

function apply(): void {
    router.get(
        '/admin-vue/certificates',
        {
            q: filters.q.trim() || undefined,
            status: filters.status || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function selectStatus(statusValue: string): void {
    filters.status = statusValue;
    apply();
}

function clear(): void {
    filters.q = '';
    filters.status = '';
    apply();
}

function setRecordType(type: 'session' | 'module'): void {
    if (form.record_type !== type) {
        form.record_type = type;
        form.record_id = '';
    }
}

function submitIssue(): void {
    if (!form.trainee_id || !form.record_id || !form.completion_date) return;
    busy.value = true;
    router.post('/admin-vue/certificates/issue', form, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
        },
        onSuccess: () => {
            issueOpen.value = false;
            form.trainee_id = '';
            form.record_id = '';
            form.completion_date = '';
            form.notes = '';
        },
    });
}

function submitRevoke(): void {
    if (!revokeTarget.value || !reason.value.trim()) return;
    busy.value = true;
    router.post(
        `/admin-vue/certificates/${revokeTarget.value.id}/revoke`,
        { revocation_reason: reason.value.trim() },
        {
            preserveScroll: true,
            onFinish: () => {
                busy.value = false;
                revokeTarget.value = null;
                reason.value = '';
            },
        },
    );
}

function statusTone(status: string): StatusTone {
    const s = status.toLowerCase();
    if (s.includes('issued') || s.includes('active')) return 'success';
    if (s.includes('revok')) return 'danger';
    if (s.includes('pend')) return 'warning';
    return 'neutral';
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function decodeLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <Head title="Certificates" />

    <AdminLayout breadcrumb="Certificates">
        <div class="space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Certificates</h1>
                    <p class="mt-1 text-sm text-muted">
                        Issue, verify, and manage training completion certificates.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <AppButton href="/admin-vue/certificate-settings" variant="outline" size="sm" pill>
                        <Settings class="size-3.5" aria-hidden="true" />
                        <span>Signatory settings</span>
                    </AppButton>
                    <AppButton variant="warm" size="sm" pill @click="issueOpen = true">
                        <Plus class="size-3.5" aria-hidden="true" />
                        <span>Issue certificate</span>
                    </AppButton>
                </div>
            </header>

            <!-- Server Validation Errors -->
            <section
                v-if="serverErrors.length > 0"
                role="alert"
                class="flex items-start gap-3 rounded-xl bg-danger-50 p-4 text-sm text-danger-700 ring-1 ring-danger-600/20"
            >
                <AlertCircle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <ul class="list-disc space-y-0.5 pl-4 text-xs">
                    <li v-for="error in serverErrors" :key="error">{{ error }}</li>
                </ul>
            </section>

            <!-- Filter & Search Bar -->
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 xl:flex-row xl:items-center xl:justify-between">
                <div
                    role="navigation"
                    aria-label="Filter certificates by status"
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

                <form class="flex items-center gap-2" @submit.prevent="apply">
                    <div class="relative flex-1 xl:w-72">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            v-model="filters.q"
                            type="search"
                            placeholder="Search certificate, trainee, title..."
                            aria-label="Search certificates"
                            class="h-9 w-full rounded-xl border border-line-strong/80 bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
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
                        @click="clear"
                    >
                        <RotateCcw class="size-3.5" aria-hidden="true" />
                        <span>Reset</span>
                    </button>
                </form>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="rows.length === 0"
                :icon="Award"
                title="No certificates found"
                :description="
                    hasActiveFilters
                        ? 'Try clearing your search or status filter.'
                        : 'Issued completion certificates for sessions and modules will appear here.'
                "
            >
                <template #action>
                    <div class="flex flex-wrap items-center justify-center gap-2.5">
                        <AppButton
                            v-if="hasActiveFilters"
                            variant="outline"
                            size="sm"
                            pill
                            @click="clear"
                        >
                            Reset filters
                        </AppButton>
                        <AppButton variant="warm" size="sm" pill @click="issueOpen = true">
                            <Plus class="size-3.5" aria-hidden="true" />
                            <span>Issue certificate</span>
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <!-- DataTable + Pagination -->
            <template v-else>
                <DataTable :columns="columns" :rows="rows" row-key="id">
                    <template #cell-certificate="{ row }">
                        <p class="tabular-nums-mono text-sm font-semibold text-navy-950">
                            {{ row.number }}
                        </p>
                        <p class="tabular mt-0.5 text-xs text-muted">
                            Issued {{ formatDate(row.issuedAt) }}
                        </p>
                    </template>

                    <template #cell-trainee="{ row }">
                        <span class="font-semibold text-navy-950">{{ row.trainee }}</span>
                    </template>

                    <template #cell-training="{ row }">
                        <p class="font-medium text-ink">{{ row.title }}</p>
                        <p class="mt-0.5 text-xs text-muted">
                            {{ row.recordType === 'session' ? 'Training Session' : 'Standalone Module' }}
                            <template v-if="row.completionDate">
                                &middot; Completed {{ formatDate(row.completionDate) }}
                            </template>
                        </p>
                    </template>

                    <template #cell-status="{ row }">
                        <StatusBadge :tone="statusTone(row.status)" dot>
                            {{ row.status }}
                        </StatusBadge>
                    </template>

                    <template #actions="{ row }">
                        <button
                            v-if="row.status === 'Issued'"
                            type="button"
                            class="inline-flex h-8 items-center gap-1 rounded-full bg-danger-50 px-3 text-xs font-semibold text-danger-700 ring-1 ring-inset ring-danger-600/20 transition-colors hover:bg-danger-600 hover:text-white"
                            @click="revokeTarget = row"
                        >
                            <ShieldOff class="size-3.5" aria-hidden="true" />
                            <span>Revoke</span>
                        </button>
                        <span v-else class="text-xs text-muted">—</span>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <div
                    v-if="certificates.total > 0"
                    class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
                >
                    <span class="tabular">
                        Showing {{ certificates.from ?? 0 }}–{{ certificates.to ?? 0 }} of {{ certificates.total }} certificates
                    </span>

                    <nav
                        v-if="certificates.links.length > 3"
                        class="flex flex-wrap items-center gap-1"
                        aria-label="Pagination"
                    >
                        <template v-for="link in certificates.links" :key="link.label">
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

        <!-- Reka UI Issue Certificate Modal -->
        <DialogRoot v-model:open="issueOpen">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/55 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 w-[calc(100vw-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-neutral-200 focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <DialogTitle class="text-base font-bold text-navy-950">
                                Issue certificate
                            </DialogTitle>
                            <p class="mt-1 text-xs text-muted">
                                Generate an official Ecohydrotech completion certificate for a trainee.
                            </p>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted hover:bg-sand-100 hover:text-navy-950"
                            aria-label="Close dialog"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </DialogClose>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submitIssue">
                        <div>
                            <label class="block text-xs font-medium text-ink">
                                Trainee <span class="text-danger-600">*</span>
                            </label>
                            <select
                                v-model="form.trainee_id"
                                required
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            >
                                <option value="">Select trainee...</option>
                                <option v-for="trainee in trainees" :key="trainee.id" :value="trainee.id">
                                    {{ trainee.name }} &middot; {{ trainee.email }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <span class="block text-xs font-medium text-ink">Record type</span>
                            <div class="mt-1.5 grid grid-cols-2 gap-1.5 rounded-xl bg-sand-100/90 p-1">
                                <button
                                    type="button"
                                    class="h-8 rounded-lg text-xs font-semibold transition-all"
                                    :class="
                                        form.record_type === 'session'
                                            ? 'bg-white text-navy-950 shadow-xs'
                                            : 'text-muted hover:text-navy-950'
                                    "
                                    @click="setRecordType('session')"
                                >
                                    Training Session
                                </button>
                                <button
                                    type="button"
                                    class="h-8 rounded-lg text-xs font-semibold transition-all"
                                    :class="
                                        form.record_type === 'module'
                                            ? 'bg-white text-navy-950 shadow-xs'
                                            : 'text-muted hover:text-navy-950'
                                    "
                                    @click="setRecordType('module')"
                                >
                                    Training Module
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-ink">
                                {{ form.record_type === 'session' ? 'Completed session' : 'Training module' }}
                                <span class="text-danger-600">*</span>
                            </label>
                            <select
                                v-if="form.record_type === 'session'"
                                v-model="form.record_id"
                                required
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            >
                                <option value="">Select session...</option>
                                <option v-for="session in sessions" :key="session.id" :value="session.id">
                                    {{ session.session_code }} &middot; {{ session.title }}
                                </option>
                            </select>
                            <select
                                v-else
                                v-model="form.record_id"
                                required
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            >
                                <option value="">Select module...</option>
                                <option v-for="mod in modules" :key="mod.id" :value="mod.id">
                                    {{ mod.code }} &middot; {{ mod.title }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-ink">
                                Completion date <span class="text-danger-600">*</span>
                            </label>
                            <input
                                v-model="form.completion_date"
                                type="date"
                                required
                                class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-ink">
                                Notes <span class="font-normal text-muted">(optional)</span>
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                maxlength="1000"
                                placeholder="Optional issuance remarks..."
                                class="mt-1.5 w-full rounded-lg border border-line-strong/80 bg-white p-3 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-2">
                            <DialogClose
                                class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-muted hover:bg-sand-100 hover:text-navy-950"
                            >
                                Cancel
                            </DialogClose>
                            <button
                                type="submit"
                                :disabled="busy || !form.trainee_id || !form.record_id || !form.completion_date"
                                class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                            >
                                {{ busy ? 'Issuing...' : 'Issue certificate' }}
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Reka UI Revoke Certificate Modal -->
        <DialogRoot v-model:open="revokeModalOpen">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/55 backdrop-blur-[2px]" />
                <DialogContent
                    v-if="revokeTarget"
                    class="fixed top-1/2 left-1/2 z-50 w-[calc(100vw-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-neutral-200 focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <DialogTitle class="text-base font-bold text-navy-950">
                                Revoke certificate
                            </DialogTitle>
                            <p class="mt-1 text-xs text-muted">
                                <span class="tabular-nums-mono font-semibold">{{ revokeTarget.number }}</span>
                                &middot; {{ revokeTarget.trainee }}
                            </p>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted hover:bg-sand-100 hover:text-navy-950"
                            aria-label="Close dialog"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </DialogClose>
                    </div>

                    <form class="mt-4 space-y-4" @submit.prevent="submitRevoke">
                        <div>
                            <label class="block text-xs font-medium text-ink">
                                Revocation reason <span class="text-danger-600">*</span>
                            </label>
                            <textarea
                                v-model="reason"
                                rows="3"
                                required
                                maxlength="500"
                                placeholder="Enter mandatory revocation reason..."
                                class="mt-1.5 w-full rounded-xl border border-line-strong/80 bg-white p-3 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2.5">
                            <DialogClose
                                class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-muted hover:bg-sand-100 hover:text-navy-950"
                            >
                                Cancel
                            </DialogClose>
                            <button
                                type="submit"
                                :disabled="busy || !reason.trim()"
                                class="inline-flex h-9 items-center rounded-lg bg-danger-600 px-5 text-xs font-semibold text-white transition-colors hover:bg-danger-700 disabled:opacity-50"
                            >
                                {{ busy ? 'Revoking...' : 'Confirm revoke' }}
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </AdminLayout>
</template>
