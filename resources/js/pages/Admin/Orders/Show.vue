<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { EtmsSharedProps } from '@/types/etms';

interface OrderDetail { number:string; customer:string; email:string|null; phone:string|null; company:string|null; orderType:string; status:string; subtotal:string; total:string; billingAddress:string; paidAt:string|null; createdAt:string|null; items:{code:string;title:string;quantity:number;total:string}[]; payments:{reference:string;amount:string;status:string}[]; entitlements:{module:string|null;status:string}[]; refunds:{reference:string;amount:string;status:string}[] }
const { order } = defineProps<EtmsSharedProps & { order:OrderDetail }>();
</script>
<template>
<AdminLayout>
  <Link href="/admin-vue/orders" class="mb-4 inline-flex items-center gap-1 text-sm text-slate-600"><ArrowLeft :size="16"/>Back to orders</Link>
  <div class="mb-6"><p class="text-sm font-medium text-[#0F4C81]">{{order.orderType}}</p><h1 class="mt-1 font-mono text-2xl font-semibold">{{order.number}}</h1><p class="mt-1 text-sm text-slate-500">{{order.status}}</p></div>
  <div class="grid gap-6 md:grid-cols-2"><Card><CardContent class="p-5"><h2 class="font-semibold">Customer and billing</h2><dl class="mt-4 grid gap-2 text-sm"><dt class="text-slate-500">Customer</dt><dd>{{order.customer}}</dd><dt class="text-slate-500">Email</dt><dd>{{order.email || '—'}}</dd><dt class="text-slate-500">Phone</dt><dd>{{order.phone || '—'}}</dd><dt class="text-slate-500">Company</dt><dd>{{order.company || '—'}}</dd><dt class="text-slate-500">Billing address</dt><dd>{{order.billingAddress || '—'}}</dd></dl></CardContent></Card>
  <Card><CardContent class="p-5"><h2 class="font-semibold">Financial summary</h2><dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><dt class="text-slate-500">Subtotal</dt><dd class="text-right">MYR {{order.subtotal}}</dd><dt class="text-slate-500">Total</dt><dd class="text-right font-semibold">MYR {{order.total}}</dd><dt class="text-slate-500">Payments</dt><dd class="text-right">{{order.payments.length}}</dd><dt class="text-slate-500">Refunds</dt><dd class="text-right">{{order.refunds.length}}</dd></dl></CardContent></Card></div>
  <Card class="mt-6"><CardContent class="p-5"><h2 class="font-semibold">Order items</h2><table class="mt-4 w-full text-left text-sm"><thead class="border-b text-xs uppercase text-slate-500"><tr><th class="py-2">Code</th><th>Item</th><th>Qty</th><th class="text-right">Total</th></tr></thead><tbody class="divide-y"><tr v-for="item in order.items" :key="item.code"><td class="py-3 font-mono text-xs">{{item.code}}</td><td>{{item.title}}</td><td>{{item.quantity}}</td><td class="text-right font-mono">MYR {{item.total}}</td></tr></tbody></table></CardContent></Card>
  <div class="mt-6 grid gap-6 md:grid-cols-3"><Card><CardContent class="p-5"><h2 class="font-semibold">Payments</h2><p v-for="payment in order.payments" :key="payment.reference" class="mt-3 text-sm"><span class="font-mono">{{payment.reference}}</span><br/><span class="text-slate-500">{{payment.status}} · MYR {{payment.amount}}</span></p></CardContent></Card><Card><CardContent class="p-5"><h2 class="font-semibold">Entitlements</h2><p v-for="(entitlement,index) in order.entitlements" :key="index" class="mt-3 text-sm">{{entitlement.module || 'Module'}}<br/><span class="text-slate-500">{{entitlement.status}}</span></p></CardContent></Card><Card><CardContent class="p-5"><h2 class="font-semibold">Refunds</h2><p v-for="refund in order.refunds" :key="refund.reference" class="mt-3 text-sm"><span class="font-mono">{{refund.reference}}</span><br/><span class="text-slate-500">{{refund.status}} · MYR {{refund.amount}}</span></p></CardContent></Card></div>
</AdminLayout>
</template>
