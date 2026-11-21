<script setup lang="ts">
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Search, ShoppingBag, X } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { EtmsSharedProps } from '@/types/etms';

interface Order { id:number; number:string; customer:string; email:string|null; company:string|null; orderType:string; total:string; status:string; paidAt:string|null; createdAt:string|null; itemsCount:number }
interface Props extends EtmsSharedProps { orders:{data:Order[];from:number|null;to:number|null;total:number;links:{url:string|null;label:string;active:boolean}[]}; filters:{q:string;order_type:string;status:string}; orderTypes:{value:string;label:string}[]; statuses:{value:string;label:string}[] }
const props = defineProps<Props>();
const filters = reactive({...props.filters});
function apply(){ router.get('/admin-vue/orders', filters, {preserveState:true, replace:true}); }
function clear(){ filters.q=''; filters.order_type=''; filters.status=''; apply(); }
function date(value:string|null){ return value ? new Intl.DateTimeFormat('en-MY',{dateStyle:'medium',timeStyle:'short',timeZone:props.app.displayTimezone}).format(new Date(value)) : '—'; }
</script>

<template>
<AdminLayout>
  <div class="mb-6"><p class="text-sm font-medium text-[#0F4C81]">Commerce</p><h1 class="mt-1 text-2xl font-semibold">Orders</h1><p class="mt-1 text-sm text-slate-500">Immutable customer, payment and fulfilment order ledger.</p></div>
  <Card><CardContent class="p-4"><form class="grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_14rem_auto_auto]" @submit.prevent="apply">
    <label class="relative"><Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16"/><input v-model="filters.q" class="h-10 w-full rounded-md border border-slate-300 pl-9 pr-3 text-sm" placeholder="Order, customer or company"/></label>
    <select v-model="filters.order_type" class="h-10 rounded-md border border-slate-300 px-3 text-sm"><option value="">All order types</option><option v-for="type in orderTypes" :key="type.value" :value="type.value">{{type.label}}</option></select>
    <select v-model="filters.status" class="h-10 rounded-md border border-slate-300 px-3 text-sm"><option value="">All statuses</option><option v-for="status in statuses" :key="status.value" :value="status.value">{{status.label}}</option></select>
    <button class="h-10 rounded-md bg-slate-900 px-4 text-sm text-white">Apply</button><button type="button" class="h-10 rounded-md border px-3 text-sm" @click="clear"><X class="mr-1 inline" :size="14"/>Clear</button>
  </form></CardContent></Card>
  <Card class="mt-6 overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th></tr></thead><tbody class="divide-y"><tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-50"><td class="px-4 py-4"><Link :href="`/admin-vue/orders/${order.number}`" class="font-mono text-xs font-semibold text-[#0F4C81]">{{order.number}}</Link><p class="text-xs text-slate-500">{{order.itemsCount}} items</p></td><td class="px-4 py-4"><p class="font-medium">{{order.customer}}</p><p class="text-xs text-slate-500">{{order.company || order.email || '—'}}</p></td><td class="px-4 py-4">{{order.orderType}}</td><td class="px-4 py-4 font-mono text-xs">MYR {{order.total}}</td><td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs">{{order.status}}</span></td><td class="px-4 py-4 text-slate-600">{{date(order.createdAt)}}</td></tr><tr v-if="!orders.data.length"><td colspan="6" class="p-12 text-center text-slate-500"><ShoppingBag class="mx-auto mb-2" :size="24"/>No orders match the filters.</td></tr></tbody></table></div>
  <div class="flex justify-between border-t px-4 py-3 text-sm text-slate-500"><span>Showing {{orders.from??0}}–{{orders.to??0}} of {{orders.total}}</span><nav><a v-for="link in orders.links" :key="link.label" :href="link.url??undefined" :class="['mx-0.5 rounded px-2 py-1 text-xs',link.active?'bg-[#0F4C81] text-white':'border',!link.url?'pointer-events-none opacity-40':'']" v-html="link.label"/></nav></div></Card>
</AdminLayout>
</template>
