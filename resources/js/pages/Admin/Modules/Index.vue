<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BookOpen,
    KeyRound,
    Plus,
    RotateCcw,
    Search,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge, { type StatusTone } from '@/components/ds/StatusBadge.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface ModuleItem {
    id: number;
    code: string;
    title: string;
    slug: string;
    summary: string | null;
    materials: number;
    independent: boolean;
    price: string | null;
    currency: string;
    status: string;
    statusLabel: string;
    editUrl: string;
}

type ModuleRow = ModuleItem & Record<string, unknown>;

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props extends EtmsSharedProps {
    modules: Paginator<ModuleItem>;
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
}

const props = defineProps<Props>();
const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});

const hasActiveFilters = computed(
    () => filters.q.trim() !== '' || filters.status !== '',
);

const rows = computed<ModuleRow[]>(() => props.modules.data as ModuleRow[]);

const columns: DataTableColumn[] = [
    { key: 'module', label: 'Module' },
    { key: 'materials', label: 'Materials', tabular: true },
    { key: 'price', label: 'Price', align: 'right', tabular: true },
    { key: 'status', label: 'Status' },
];

function applyFilters(): void {
    router.get(
        '/admin-vue/modules',
        {
            q: filters.q.trim() || undefined,
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
    filters.status = '';
    applyFilters();
}

function statusTone(statusValue: string): StatusTone {
    if (statusValue === 'published') return 'success';
    if (statusValue === 'draft') return 'warning';
    if (statusValue === 'archived') return 'neutral';
    return 'primary';
}

function decodeLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <Head title="Modules" />

    <AdminLayout breadcrumb="Modules">
        <div class="space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Learning modules</h1>
                    <p class="mt-1 text-sm text-muted">
                        Manage self-paced modules, downloadable materials, and video lessons.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <AppButton href="/admin-vue/module-entitlements" variant="outline" size="sm" pill>
                        <KeyRound class="size-3.5" aria-hidden="true" />
                        <span>Module Access</span>
                    </AppButton>
                    <AppButton href="/admin-vue/modules/create" variant="warm" size="sm" pill>
                        <Plus class="size-3.5" aria-hidden="true" />
                        <span>Create module</span>
                    </AppButton>
                </div>
            </header>

            <!-- Filter & Search Bar -->
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 xl:flex-row xl:items-center xl:justify-between">
                <div
                    role="navigation"
                    aria-label="Filter modules by status"
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

                <form class="flex items-center gap-2" @submit.prevent="applyFilters">
                    <div class="relative flex-1 xl:w-72">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            v-model="filters.q"
                            type="search"
                            placeholder="Search code or title..."
                            aria-label="Search modules"
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
                :icon="BookOpen"
                title="No learning modules found"
                :description="
                    hasActiveFilters
                        ? 'Try clearing your search or status filter.'
                        : 'Create your first standalone learning module to publish materials and video lessons.'
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
                        <AppButton href="/admin-vue/modules/create" variant="warm" size="sm" pill>
                            <Plus class="size-3.5" aria-hidden="true" />
                            <span>Create module</span>
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <!-- DataTable + Pagination -->
            <template v-else>
                <DataTable
                    :columns="columns"
                    :rows="rows"
                    row-key="id"
                >
                    <template #cell-module="{ row }">
                        <Link
                            :href="row.editUrl"
                            class="font-semibold text-navy-950 transition-colors hover:text-primary-600"
                        >
                            {{ row.title }}
                        </Link>
                        <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted">
                            <span class="tabular-nums-mono font-medium text-primary-700">{{ row.code }}</span>
                            <template v-if="row.summary">
                                <span>&middot;</span>
                                <span class="line-clamp-1 max-w-md">{{ row.summary }}</span>
                            </template>
                        </p>
                    </template>

                    <template #cell-materials="{ row }">
                        <span class="tabular text-sm text-ink">
                            {{ row.materials }} {{ row.materials === 1 ? 'file' : 'files' }}
                        </span>
                    </template>

                    <template #cell-price="{ row }">
                        <span v-if="row.price" class="tabular font-semibold text-navy-950">
                            {{ row.currency }} {{ row.price }}
                        </span>
                        <span v-else class="text-muted">—</span>
                    </template>

                    <template #cell-status="{ row }">
                        <StatusBadge :tone="statusTone(row.status)" dot>
                            {{ row.statusLabel }}
                        </StatusBadge>
                    </template>

                    <template #actions="{ row }">
                        <Link
                            :href="row.editUrl"
                            class="inline-flex h-8 items-center gap-1 rounded-full bg-sand-50 px-3 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-navy-950 hover:text-white hover:ring-navy-950"
                        >
                            <span>Manage</span>
                            <ArrowUpRight class="size-3.5" aria-hidden="true" />
                        </Link>
                    </template>
                </DataTable>

                <!-- Pagination Footer -->
                <div
                    v-if="modules.last_page > 1 || modules.total > 0"
                    class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
                >
                    <span class="tabular">
                        Showing {{ modules.from ?? 0 }}–{{ modules.to ?? 0 }} of {{ modules.total }} modules
                    </span>

                    <nav
                        v-if="modules.last_page > 1"
                        class="flex flex-wrap items-center gap-1"
                        aria-label="Pagination"
                    >
                        <template v-for="link in modules.links" :key="link.label">
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
    </AdminLayout>
</template>
