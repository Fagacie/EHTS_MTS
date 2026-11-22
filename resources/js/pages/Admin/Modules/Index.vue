<script setup lang="ts">
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Plus } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { EtmsSharedProps } from '@/types/etms';

interface ModuleRow {
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
    modules: Paginator<ModuleRow>;
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
}

const props = defineProps<Props>();
const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});

function applyFilters(): void {
    router.get(
        '/admin-vue/modules',
        {
            q: filters.q || undefined,
            status: filters.status || undefined,
        },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Standalone Learning Modules — Ecohydrotech Admin" />

    <AdminLayout>
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Standalone Learning Modules</h1>
                <p class="mt-1 text-sm text-neutral-500">
                    Self-paced learning modules available for independent customer purchase without attending physical sessions.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button as-child variant="outline">
                    <Link href="/admin-vue/module-entitlements">
                        Module Entitlements
                    </Link>
                </Button>
                <Button as-child class="bg-[#0F4C81] text-white hover:bg-[#0F4C81]/90">
                    <Link href="/admin-vue/modules/create">
                        <Plus :size="16" class="mr-1.5" />
                        Create standalone module
                    </Link>
                </Button>
            </div>
        </div>

        <Card>
            <CardContent class="p-4">
                <form class="mb-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="applyFilters">
                    <Input
                        v-model="filters.q"
                        class="h-10 flex-1"
                        placeholder="Search by module code or title..."
                    />
                    <select
                        v-model="filters.status"
                        class="h-10 rounded-md border border-neutral-200 bg-white px-3 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <Button type="submit" variant="outline" class="h-10">Filter</Button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-neutral-200 bg-neutral-50 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-3 py-3">Code</th>
                                <th class="px-3 py-3">Module Title</th>
                                <th class="px-3 py-3">Materials</th>
                                <th class="px-3 py-3">Purchase Price</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            <tr v-for="mod in modules.data" :key="mod.id" class="hover:bg-neutral-50/70">
                                <td class="px-3 py-3 font-mono text-xs font-semibold text-[#0F4C81]">
                                    {{ mod.code }}
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-medium text-neutral-900">{{ mod.title }}</p>
                                    <p v-if="mod.summary" class="mt-0.5 line-clamp-1 text-xs text-neutral-500">
                                        {{ mod.summary }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 font-mono text-xs">
                                    {{ mod.materials }} file(s)
                                </td>
                                <td class="px-3 py-3 font-mono text-xs">
                                    <span v-if="mod.price">{{ mod.currency }} {{ mod.price }}</span>
                                    <span v-else class="text-neutral-400">—</span>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-md border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-700">
                                        {{ mod.statusLabel }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <Link
                                        :href="mod.editUrl"
                                        class="text-xs font-semibold text-[#0F4C81] hover:underline"
                                    >
                                        Manage module &amp; materials
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="modules.data.length === 0">
                                <td colspan="6" class="px-3 py-12 text-center text-neutral-500">
                                    <BookOpen class="mx-auto mb-2 h-6 w-6 text-neutral-400" />
                                    No standalone learning modules found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </AdminLayout>
</template>
