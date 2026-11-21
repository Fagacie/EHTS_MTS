<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { CreditCard, Search, X } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { EtmsSharedProps } from '@/types/etms';

interface Payment { id: number; reference: string; orderNumber: string | null; customer: string; email: string | null; amount: string; refundedAmount: string; status: string; paidAt: string | null }
interface Props extends EtmsSharedProps { payments: { data: Payment[]; from: number | null; to: number | null; total: number; links: { url: string | null; label: string; active: boolean }[] }; filters: { q: string; status: string }; statuses: { value: string; label: string }[] }
const props = defineProps<Props>();
const filters = reactive({ ...props.filters });
function apply(): void { router.get('/admin-vue/payments', filters, { preserveState: true, replace: true }); }
function clear(): void { filters.q = ''; filters.status = ''; apply(); }
function date(value: string | null): string { return value ? new Intl.DateTimeFormat('en-MY', { dateStyle: 'medium', timeStyle: 'short', timeZone: props.app.displayTimezone }).format(new Date(value)) : '—'; }
const action = ref<{ type: 'verify' | 'refund'; payment: Payment } | null>(null);
const form = reactive({ amount: '', refund_method: 'manual_bank_transfer', disbursement_reference: '', reason: '' });
const busy = ref(false);
function submitAction(): void {
    if (!action.value) return;
    busy.value = true;
    const url = action.value.type === 'verify'
        ? `/admin-vue/payments/${action.value.payment.id}/verify`
        : `/admin-vue/payments/${action.value.payment.id}/refund`;
    router.post(url, action.value.type === 'verify' ? {} : form, {
        preserveScroll: true,
        onFinish: () => { busy.value = false; action.value = null; },
    });
}
</script>
<template>
    <AdminLayout>
        <div class="mb-6"><p class="text-sm font-medium text-[#0F4C81]">Finance</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Payments</h1><p class="mt-1 text-sm text-slate-500">Billplz payment records and verified settlement status.</p></div>
        <Card><CardContent class="p-4"><form class="grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_auto_auto]" @submit.prevent="apply"><label class="relative"><span class="sr-only">Search payments</span><Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" /><input v-model="filters.q" type="search" placeholder="Payment, order or customer" class="h-10 w-full rounded-md border border-slate-300 pl-9 pr-3 text-sm outline-none focus:border-[#0F4C81]" /></label><select v-model="filters.status" class="h-10 rounded-md border border-slate-300 bg-white px-3 text-sm"><option value="">All statuses</option><option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option></select><button class="h-10 rounded-md bg-slate-900 px-4 text-sm font-medium text-white">Apply</button><button type="button" class="inline-flex h-10 items-center justify-center gap-1 rounded-md border border-slate-300 px-3 text-sm" @click="clear"><X :size="14" /> Clear</button></form></CardContent></Card>
        <Card class="mt-6 overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Paid</th><th class="px-4 py-3">Actions</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-slate-50"><td class="px-4 py-4"><p class="font-mono text-xs font-medium">{{ payment.reference }}</p><p class="text-xs text-slate-500">{{ payment.orderNumber ?? 'No order' }}</p></td><td class="px-4 py-4"><p class="font-medium">{{ payment.customer }}</p><p class="text-xs text-slate-500">{{ payment.email }}</p></td><td class="px-4 py-4 font-mono text-xs">MYR {{ payment.amount }}<p v-if="payment.refundedAmount !== '0.00'" class="text-xs text-slate-500">Refunded MYR {{ payment.refundedAmount }}</p></td><td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium">{{ payment.status }}</span></td><td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ date(payment.paidAt) }}</td><td class="px-4 py-4"><div class="flex flex-wrap gap-2"><button type="button" class="rounded-md border px-3 py-1.5 text-xs" @click="action = { type: 'verify', payment }">Verify</button><button type="button" class="rounded-md border border-amber-300 px-3 py-1.5 text-xs text-amber-800" @click="action = { type: 'refund', payment }; form.amount = payment.amount">Refund</button></div></td></tr><tr v-if="payments.data.length === 0"><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500"><CreditCard class="mx-auto mb-2 text-slate-400" :size="24" />No payments match the filters.</td></tr></tbody></table></div><div class="flex justify-between border-t border-slate-200 px-4 py-3 text-sm text-slate-500"><span>Showing {{ payments.from ?? 0 }}–{{ payments.to ?? 0 }} of {{ payments.total }}</span><nav class="flex gap-1"><a v-for="link in payments.links" :key="link.label" :href="link.url ?? undefined" :class="['rounded-md px-3 py-1.5 text-xs', link.active ? 'bg-[#0F4C81] text-white' : 'border border-slate-200', !link.url ? 'pointer-events-none opacity-40' : '']" v-html="link.label" /></nav></div></Card>
        <div v-if="action" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4"><div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"><h2 class="text-lg font-semibold">{{ action.type === 'verify' ? 'Verify payment' : 'Record refund' }}</h2><p class="mt-1 text-sm text-slate-500">{{ action.payment.reference }} · MYR {{ action.payment.amount }}</p><div v-if="action.type === 'refund'" class="mt-4 space-y-3"><input v-model="form.amount" type="number" min="0.01" step="0.01" class="h-10 w-full rounded-md border px-3 text-sm" placeholder="Refund amount" /><select v-model="form.refund_method" class="h-10 w-full rounded-md border bg-white px-3 text-sm"><option value="manual_bank_transfer">Manual bank transfer</option><option value="original_method">Original payment method</option></select><input v-model="form.disbursement_reference" class="h-10 w-full rounded-md border px-3 text-sm" placeholder="Disbursement reference (optional)" /><textarea v-model="form.reason" class="min-h-24 w-full rounded-md border p-3 text-sm" placeholder="Mandatory refund reason" maxlength="500" /></div><div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-md border px-4 py-2 text-sm" @click="action = null">Cancel</button><button type="button" class="rounded-md bg-[#0F4C81] px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="busy || (action.type === 'refund' && (!form.amount || !form.reason.trim()))" @click="submitAction">{{ busy ? 'Saving…' : action.type === 'verify' ? 'Verify payment' : 'Record refund' }}</button></div></div></div>
    </AdminLayout>
</template>
