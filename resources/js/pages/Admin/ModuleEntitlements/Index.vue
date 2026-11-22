<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, KeyRound, Plus, ShieldOff } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { EtmsSharedProps } from '@/types/etms';

interface EntitlementRow {
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

interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props extends EtmsSharedProps {
    entitlements: Paginator<EntitlementRow>;
    filters: { q: string; module_id: string; status: string };
    modules: { id: number; label: string }[];
    statuses: { value: string; label: string }[];
    customers: { id: number; label: string }[];
}

const props = defineProps<Props>();
const page = usePage();

const serverErrors = () => {
    const errors = (page.props as { errors?: Record<string, string> }).errors ?? {};
    return Object.values(errors).filter(Boolean);
};

const filters = reactive({
    q: props.filters.q ?? '',
    module_id: props.filters.module_id ?? '',
    status: props.filters.status ?? '',
});

const grantForm = reactive({
    user_id: '',
    module_id: '',
    grant_reason: '',
});
const granting = ref(false);

function applyFilters(): void {
    router.get(
        '/admin-vue/module-entitlements',
        {
            q: filters.q || undefined,
            module_id: filters.module_id || undefined,
            status: filters.status || undefined,
        },
        { preserveState: true, replace: true },
    );
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
        },
    });
}

function revokeEntitlement(id: number): void {
    const reason = window.prompt('Enter reason for revoking this module entitlement:');
    if (!reason || !reason.trim()) {
        return;
    }
    router.post(
        `/admin-vue/module-entitlements/${id}/revoke`,
        { revocation_reason: reason.trim() },
        { preserveScroll: true },
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
</script>

<template>
    <Head title="Standalone Module Entitlements — Ecohydrotech Admin" />

    <AdminLayout>
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Standalone Module Entitlements</h1>
                <p class="mt-1 text-sm text-neutral-500">
                    Manage customer access to standalone digital learning modules, independently of physical session bookings.
                </p>
            </div>
            <Button as-child variant="outline">
                <Link href="/admin-vue/modules">Back to Standalone Modules</Link>
            </Button>
        </div>

        <section
            v-if="serverErrors().length"
            role="alert"
            class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
        >
            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            <ul class="list-disc space-y-0.5 pl-4">
                <li v-for="error in serverErrors()" :key="error">{{ error }}</li>
            </ul>
        </section>

        <Card class="mb-6">
            <CardHeader class="border-b border-neutral-200 pb-3">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Plus :size="16" />
                    Grant Standalone Module Access
                </CardTitle>
            </CardHeader>
            <CardContent class="pt-4">
                <form class="grid gap-3 md:grid-cols-12" @submit.prevent="submitGrant">
                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-neutral-600">Trainee / Customer</label>
                        <select
                            v-model="grantForm.user_id"
                            required
                            class="mt-1 h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                        >
                            <option value="">Select customer…</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.label }}
                            </option>
                        </select>
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-neutral-600">Standalone Module</label>
                        <select
                            v-model="grantForm.module_id"
                            required
                            class="mt-1 h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                        >
                            <option value="">Select standalone module…</option>
                            <option v-for="mod in modules" :key="mod.id" :value="mod.id">
                                {{ mod.label }}
                            </option>
                        </select>
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-neutral-600">Grant Reason</label>
                        <div class="mt-1 flex gap-2">
                            <Input
                                v-model="grantForm.grant_reason"
                                required
                                maxlength="500"
                                placeholder="e.g. Scholarship / complimentary access"
                                class="h-10 flex-1"
                            />
                            <Button
                                type="submit"
                                :disabled="granting"
                                class="h-10 shrink-0 bg-[#0F4C81] text-white hover:bg-[#0F4C81]/90"
                            >
                                {{ granting ? 'Granting…' : 'Grant' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-4">
                <form class="mb-4 grid gap-3 sm:grid-cols-12" @submit.prevent="applyFilters">
                    <Input
                        v-model="filters.q"
                        class="h-10 sm:col-span-5"
                        placeholder="Search by customer, email, module, or order number…"
                    />
                    <select
                        v-model="filters.module_id"
                        class="h-10 rounded-md border border-neutral-200 bg-white px-3 text-sm sm:col-span-4"
                    >
                        <option value="">All standalone modules</option>
                        <option v-for="mod in modules" :key="mod.id" :value="String(mod.id)">
                            {{ mod.label }}
                        </option>
                    </select>
                    <select
                        v-model="filters.status"
                        class="h-10 rounded-md border border-neutral-200 bg-white px-3 text-sm sm:col-span-2"
                    >
                        <option value="">All statuses</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <Button type="submit" variant="outline" class="h-10 sm:col-span-1">Filter</Button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-neutral-200 bg-neutral-50 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-3 py-3">Customer</th>
                                <th class="px-3 py-3">Standalone Module</th>
                                <th class="px-3 py-3">Source / Order</th>
                                <th class="px-3 py-3">Granted</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            <tr v-for="row in entitlements.data" :key="row.id" class="hover:bg-neutral-50/70">
                                <td class="px-3 py-3">
                                    <p class="font-medium text-neutral-900">{{ row.customer ?? '—' }}</p>
                                    <p class="text-xs text-neutral-500">{{ row.email ?? '' }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-mono text-xs font-semibold text-[#0F4C81]">{{ row.moduleCode }}</p>
                                    <p class="text-xs text-neutral-700">{{ row.moduleTitle }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs text-neutral-700">
                                    <p>{{ row.source }}</p>
                                    <p v-if="row.orderNumber" class="font-mono text-neutral-500">{{ row.orderNumber }}</p>
                                </td>
                                <td class="px-3 py-3 text-xs text-neutral-600">
                                    {{ formatDate(row.grantedAt) }}
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-md border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-700">
                                        {{ row.status }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <button
                                        v-if="row.canRevoke"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md border border-red-300 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-50"
                                        @click="revokeEntitlement(row.id)"
                                    >
                                        <ShieldOff :size="13" />
                                        Revoke
                                    </button>
                                    <span v-else class="text-xs text-neutral-400">—</span>
                                </td>
                            </tr>
                            <tr v-if="entitlements.data.length === 0">
                                <td colspan="6" class="px-3 py-12 text-center text-neutral-500">
                                    <KeyRound class="mx-auto mb-2 h-6 w-6 text-neutral-400" />
                                    No standalone module entitlements found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </AdminLayout>
</template>
