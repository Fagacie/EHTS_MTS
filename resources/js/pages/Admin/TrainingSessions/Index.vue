<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { CalendarDays, ExternalLink, Search, X } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
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

function applyFilters(): void {
    router.get('/admin-vue/sessions', filters, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters(): void {
    filters.q = '';
    filters.status = '';
    applyFilters();
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Date unavailable';
    }

    return new Intl.DateTimeFormat('en-MY', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: props.app.displayTimezone,
    }).format(new Date(value));
}

function runAction(session: SessionItem, action: 'publish' | 'close-registration' | 'complete' | 'reconcile-seats'): void {
    busyAction[session.id] = action;
    router.post(`/admin-vue/sessions/${session.id}/${action}`, {}, {
        preserveScroll: true,
        onFinish: () => {
            busyAction[session.id] = null;
        },
    });
}

function submitDialog(): void {
    if (!dialog.value) return;
    if (dialog.value.action === 'delete') {
        busyAction[dialog.value.session.id] = 'delete';
        router.delete(`/admin-vue/sessions/${dialog.value.session.id}`, {
            preserveScroll: true,
            onFinish: () => { busyAction[dialog.value?.session.id ?? 0] = null; dialog.value = null; },
        });
        return;
    }
    if (message.value.trim() === '') return;
    busyAction[dialog.value.session.id] = dialog.value.action;
    router.post(`/admin-vue/sessions/${dialog.value.session.id}/${dialog.value.action === 'cancel' ? 'cancel' : 'notify'}`, dialog.value.action === 'cancel' ? { cancellation_reason: message.value } : { update_message: message.value }, {
        preserveScroll: true,
        onFinish: () => { busyAction[dialog.value?.session.id ?? 0] = null; dialog.value = null; message.value = ''; },
    });
}
function deleteSession(session: SessionItem): void {
    dialog.value = { session, action: 'delete' };
    message.value = '';
}
</script>

<template>
    <AdminLayout>
        <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-[#0F4C81]">Training management</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">Training sessions</h1>
                <p class="mt-1 text-sm text-slate-500">Search and review scheduled sessions without loading the full dataset into the browser.</p>
            </div>
            <a href="/admin-vue/sessions/create" class="inline-flex items-center justify-center rounded-md bg-[#0F4C81] px-4 py-2 text-sm font-medium text-white hover:bg-[#0C3D68]">
                Create session
            </a>
        </div>

        <Card>
            <CardContent class="p-4">
                <form class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_auto_auto]" @submit.prevent="applyFilters">
                    <label class="relative">
                        <span class="sr-only">Search sessions</span>
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                        <input v-model="filters.q" type="search" placeholder="Search session title or code" class="h-10 w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 text-sm outline-none focus:border-[#0F4C81] focus:ring-2 focus:ring-[#0F4C81]/15" />
                    </label>
                    <select v-model="filters.status" class="h-10 rounded-md border border-slate-300 bg-white px-3 text-sm outline-none focus:border-[#0F4C81]">
                        <option value="">All statuses</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-700">Apply</button>
                    <button type="button" class="inline-flex h-10 items-center justify-center gap-1 rounded-md border border-slate-300 px-3 text-sm text-slate-600 hover:bg-slate-50" @click="clearFilters">
                        <X :size="14" /> Clear
                    </button>
                </form>
            </CardContent>
        </Card>

        <Card class="mt-6 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Session</th>
                            <th class="px-4 py-3 font-medium">Schedule</th>
                            <th class="px-4 py-3 font-medium">Venue</th>
                            <th class="px-4 py-3 font-medium">Seats</th>
                            <th class="px-4 py-3 font-medium">Price</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="session in sessions.data" :key="session.id" class="hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <p class="font-medium text-slate-900">{{ session.title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ session.sessionCode }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ session.instructors.join(', ') || 'Unassigned' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ formatDate(session.startsAt) }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ session.venue || 'Not set' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-600">{{ session.confirmedBookings }} / {{ session.reservedSeats }} / {{ session.capacity }}</td>
                            <td class="whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-600">MYR {{ session.price }}</td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ session.status }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a :href="session.detailUrl" class="inline-flex items-center gap-1 text-xs font-medium text-[#0F4C81] hover:underline">View details <ExternalLink :size="14" /></a>
                                    <a :href="session.editUrl" class="text-xs font-medium text-slate-700 hover:underline">Edit</a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="sessions.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">
                                <CalendarDays class="mx-auto mb-2 text-slate-400" :size="24" />
                                No training sessions match the selected filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <span>Showing {{ sessions.from ?? 0 }}–{{ sessions.to ?? 0 }} of {{ sessions.total }}</span>
                <nav class="flex gap-1" aria-label="Pagination">
                    <a
                        v-for="link in sessions.links"
                        :key="link.label"
                        :href="link.url ?? undefined"
                        :class="['rounded-md px-3 py-1.5 text-xs', link.active ? 'bg-[#0F4C81] text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50', !link.url ? 'pointer-events-none opacity-40' : '']"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </Card>
        <div v-if="dialog" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold">{{ dialog.action === 'cancel' ? 'Cancel training session' : dialog.action === 'notify' ? 'Notify participants' : 'Delete training session' }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ dialog.session.sessionCode }} · {{ dialog.session.title }}</p>
                <textarea v-if="dialog.action !== 'delete'" v-model="message" class="mt-4 min-h-28 w-full rounded-md border p-3 text-sm" :placeholder="dialog.action === 'cancel' ? 'Mandatory cancellation reason' : 'Session update message'" maxlength="800" />
                <p v-else class="mt-4 text-sm text-red-700">Deletion is permitted only when no bookings or certificates are associated.</p>
                <div class="mt-4 flex justify-end gap-2"><button type="button" class="rounded-md border px-4 py-2 text-sm" @click="dialog = null">Cancel</button><button type="button" :class="['rounded-md px-4 py-2 text-sm text-white disabled:opacity-50',dialog.action==='delete'?'bg-red-700':'bg-[#0F4C81]']" :disabled="(dialog.action !== 'delete' && !message.trim()) || !!busyAction[dialog.session.id]" @click="submitDialog">{{ busyAction[dialog.session.id] ? 'Saving…' : dialog.action === 'delete' ? 'Delete session' : 'Continue' }}</button></div>
            </div>
        </div>
    </AdminLayout>
</template>
