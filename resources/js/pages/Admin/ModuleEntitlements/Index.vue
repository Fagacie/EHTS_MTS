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
    BookOpen,
    KeyRound,
    Plus,
    RotateCcw,
    Search,
    ShieldOff,
    X,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface EntitlementItem {
    id: number;
    customer: string | null;
    email: string | null;
    moduleCode: string | null;
    moduleTitle: string | null;
    source: string;
    orderNumber: string | null;
    status: string;
    grantedAt: string | null;
    canRevoke: boolean;
}

type EntitlementRow = EntitlementItem & Record<string, unknown>;

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from?: number | null;
    to?: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props extends EtmsSharedProps {
    entitlements: Paginator<EntitlementItem>;
    filters: { q: string; module_id: string; status: string };
    modules: { id: number; label: string }[];
    statuses: { value: string; label: string }[];
    customers: { id: number; label: string }[];
}

const props = defineProps<Props>();
const page = usePage();

const serverErrors = computed(() => {
    const errors = (page.props as { errors?: Record<string, string> }).errors ?? {};
    return Object.values(errors).filter(Boolean);
});

const filters = reactive({
    q: props.filters.q ?? '',
    module_id: props.filters.module_id ?? '',
    status: props.filters.status ?? '',
});

const hasActiveFilters = computed(
    () => filters.q.trim() !== '' || filters.module_id !== '' || filters.status !== '',
);

const rows = computed<EntitlementRow[]>(() => props.entitlements.data as EntitlementRow[]);

const columns: DataTableColumn[] = [
    { key: 'customer', label: 'Customer' },
    { key: 'module', label: 'Module' },
    { key: 'source', label: 'Source / Order' },
    { key: 'granted', label: 'Granted', tabular: true },
    { key: 'status', label: 'Status' },
];

// Grant Access Modal
const grantModalOpen = ref(false);
const grantForm = reactive({
    user_id: '',
    module_id: '',
    grant_reason: '',
});
const granting = ref(false);

// Revoke Access Modal
const revokeTarget = ref<EntitlementItem | null>(null);
const revokeReason = ref('');
const revoking = ref(false);

const revokeModalOpen = computed({
    get: () => revokeTarget.value !== null,
    set: (open: boolean) => {
        if (!open) {
            revokeTarget.value = null;
            revokeReason.value = '';
        }
    },
});

function applyFilters(): void {
    router.get(
        '/admin-vue/module-entitlements',
        {
            q: filters.q.trim() || undefined,
            module_id: filters.module_id || undefined,
            status: filters.status || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function selectStatus(statusValue: string): void {
    filters.status = statusValue;
    applyFilters();
}

function clearFilters(): void {
    filters.q = '';
    filters.module_id = '';
    filters.status = '';
    applyFilters();
}

function submitGrant(): void {
    if (!grantForm.user_id || !grantForm.module_id || !grantForm.grant_reason.trim()) {
        return;
    }
    granting.value = true;
    router.post('/admin-vue/module-entitlements', grantForm, {
        preserveScroll: true,
        onFinish: () => {
            granting.value = false;
        },
        onSuccess: () => {
            grantForm.user_id = '';
            grantForm.module_id = '';
            grantForm.grant_reason = '';
            grantModalOpen.value = false;
        },
    });
}

function openRevokeModal(item: EntitlementItem): void {
    revokeTarget.value = item;
    revokeReason.value = '';
}

function submitRevoke(): void {
    if (!revokeTarget.value || !revokeReason.value.trim()) {
        return;
    }
    revoking.value = true;
    router.post(
        `/admin-vue/module-entitlements/${revokeTarget.value.id}/revoke`,
        { revocation_reason: revokeReason.value.trim() },
        {
            preserveScroll: true,
            onFinish: () => {
                revoking.value = false;
            },
            onSuccess: () => {
                revokeTarget.value = null;
                revokeReason.value = '';
            },
        },
    );
}

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function statusTone(status: string): StatusTone {
    const s = status.toLowerCase();
    if (s.includes('active')) return 'success';
    if (s.includes('revok')) return 'danger';
    if (s.includes('expir') || s.includes('pend')) return 'warning';
    return 'neutral';
}

function decodeLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <Head title="Module Access" />

    <AdminLayout breadcrumb="Module Access">
        <div class="space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Module access</h1>
                    <p class="mt-1 text-sm text-muted">
                        Grant, review, or revoke customer entitlements to standalone learning modules.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <AppButton href="/admin-vue/modules" variant="outline" size="sm" pill>
                        <BookOpen class="size-3.5" aria-hidden="true" />
                        <span>Modules</span>
                    </AppButton>
                    <AppButton variant="warm" size="sm" pill @click="grantModalOpen = true">
                        <Plus class="size-3.5" aria-hidden="true" />
                        <span>Grant access</span>
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
            <div class="space-y-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div
                        role="navigation"
                        aria-label="Filter entitlements by status"
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

                    <form class="flex flex-wrap items-center gap-2" @submit.prevent="applyFilters">
                        <select
                            v-model="filters.module_id"
                            aria-label="Filter by module"
                            class="h-9 rounded-xl border border-line-strong/80 bg-white px-3 text-xs text-ink focus:border-primary-600 focus:outline-none"
                            @change="applyFilters"
                        >
                            <option value="">All modules</option>
                            <option v-for="mod in modules" :key="mod.id" :value="String(mod.id)">
                                {{ mod.label }}
                            </option>
                        </select>

                        <div class="relative flex-1 sm:w-64">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                                aria-hidden="true"
                            />
                            <input
                                v-model="filters.q"
                                type="search"
                                placeholder="Search customer, email, order..."
                                aria-label="Search module entitlements"
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
                            @click="clearFilters"
                        >
                            <RotateCcw class="size-3.5" aria-hidden="true" />
                            <span>Reset</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="rows.length === 0"
                :icon="KeyRound"
                title="No module entitlements found"
                :description="
                    hasActiveFilters
                        ? 'Try clearing your filters or search query.'
                        : 'Module entitlements granted via checkout or manually by an administrator will appear here.'
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
                        <AppButton variant="warm" size="sm" pill @click="grantModalOpen = true">
                            <Plus class="size-3.5" aria-hidden="true" />
                            <span>Grant access</span>
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <!-- DataTable + Pagination -->
            <template v-else>
                <DataTable :columns="columns" :rows="rows" row-key="id">
                    <template #cell-customer="{ row }">
                        <p class="font-semibold text-navy-950">{{ row.customer ?? '—' }}</p>
                        <p v-if="row.email" class="mt-0.5 text-xs text-muted">{{ row.email }}</p>
                    </template>

                    <template #cell-module="{ row }">
                        <p class="font-medium text-navy-950">{{ row.moduleTitle ?? '—' }}</p>
                        <p v-if="row.moduleCode" class="tabular-nums-mono mt-0.5 text-xs text-primary-700">
                            {{ row.moduleCode }}
                        </p>
                    </template>

                    <template #cell-source="{ row }">
                        <p class="text-sm text-ink">{{ row.source }}</p>
                        <p v-if="row.orderNumber" class="tabular-nums-mono mt-0.5 text-xs text-muted">
                            {{ row.orderNumber }}
                        </p>
                    </template>

                    <template #cell-granted="{ row }">
                        <span class="tabular text-sm text-ink">
                            {{ formatDate(row.grantedAt) }}
                        </span>
                    </template>

                    <template #cell-status="{ row }">
                        <StatusBadge :tone="statusTone(row.status)" dot>
                            {{ row.status }}
                        </StatusBadge>
                    </template>

                    <template #actions="{ row }">
                        <button
                            v-if="row.canRevoke"
                            type="button"
                            class="inline-flex h-8 items-center gap-1 rounded-full bg-danger-50 px-3 text-xs font-semibold text-danger-700 ring-1 ring-inset ring-danger-600/20 transition-colors hover:bg-danger-600 hover:text-white"
                            @click="openRevokeModal(row)"
                        >
                            <ShieldOff class="size-3.5" aria-hidden="true" />
                            <span>Revoke</span>
                        </button>
                        <span v-else class="text-xs text-muted">—</span>
                    </template>
                </DataTable>

                <!-- Pagination Footer -->
                <div
                    v-if="entitlements.last_page > 1 || entitlements.total > 0"
                    class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
                >
                    <span class="tabular">
                        Showing {{ entitlements.total }} {{ entitlements.total === 1 ? 'entitlement' : 'entitlements' }}
                    </span>

                    <nav
                        v-if="entitlements.last_page > 1"
                        class="flex flex-wrap items-center gap-1"
                        aria-label="Pagination"
                    >
                        <template v-for="link in entitlements.links" :key="link.label">
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

        <!-- Reka UI Grant Access Modal -->
        <DialogRoot v-model:open="grantModalOpen">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/55 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 w-[calc(100vw-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-neutral-200 focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <DialogTitle class="text-base font-bold text-navy-950">
                                Grant module access
                            </DialogTitle>
                            <p class="mt-1 text-xs text-muted">
                                Assign direct lifetime digital access to a customer for a standalone module.
                            </p>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted hover:bg-sand-100 hover:text-navy-950"
                            aria-label="Close dialog"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </DialogClose>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submitGrant">
                        <div>
                            <label for="grant-customer" class="block text-xs font-medium text-ink">
                                Customer <span class="text-danger-600">*</span>
                            </label>
                            <select
                                id="grant-customer"
                                v-model="grantForm.user_id"
                                required
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            >
                                <option value="">Select customer...</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="grant-module" class="block text-xs font-medium text-ink">
                                Module <span class="text-danger-600">*</span>
                            </label>
                            <select
                                id="grant-module"
                                v-model="grantForm.module_id"
                                required
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            >
                                <option value="">Select module...</option>
                                <option v-for="mod in modules" :key="mod.id" :value="mod.id">
                                    {{ mod.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="grant-reason" class="block text-xs font-medium text-ink">
                                Reason <span class="text-danger-600">*</span>
                            </label>
                            <input
                                id="grant-reason"
                                v-model="grantForm.grant_reason"
                                type="text"
                                required
                                maxlength="500"
                                placeholder="e.g. Scholarship / complimentary access"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
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
                                :disabled="granting"
                                class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                            >
                                {{ granting ? 'Granting...' : 'Grant access' }}
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Reka UI Revoke Access Modal -->
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
                                Revoke module access
                            </DialogTitle>
                            <p class="mt-1 text-xs text-muted">
                                {{ revokeTarget.customer }} &middot; {{ revokeTarget.moduleTitle }}
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
                            <label for="revoke-reason" class="block text-xs font-medium text-ink">
                                Revocation reason <span class="text-danger-600">*</span>
                            </label>
                            <textarea
                                id="revoke-reason"
                                v-model="revokeReason"
                                rows="3"
                                required
                                maxlength="500"
                                placeholder="Enter reason for revoking this entitlement..."
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
                                :disabled="revoking || !revokeReason.trim()"
                                class="inline-flex h-9 items-center rounded-lg bg-danger-600 px-5 text-xs font-semibold text-white transition-colors hover:bg-danger-700 disabled:opacity-50"
                            >
                                {{ revoking ? 'Revoking...' : 'Confirm revoke' }}
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </AdminLayout>
</template>
