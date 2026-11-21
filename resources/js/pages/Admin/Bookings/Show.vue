<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { EtmsSharedProps } from '@/types/etms';

interface Props extends EtmsSharedProps {
 booking:{id:number;reference:string;status:string;statusValue:string;registeredAt:string|null;cancellationReason:string|null;participant:{name:string;email:string|null};session:{title:string;code:string;startsAt:string|null;venue:string;detailUrl:string}|null;order:{number:string;type:string|null;status:string|null;total:string;paidAt:string|null;payments:{id:number;status:string|null;amount:string;paidAt:string|null}[];refunds:{id:number;status:string|null;amount:string;reason:string|null}[]}|null;attendance:{attendanceStatus:string|null;reviewStatus:string|null;submittedAt:string|null;verifiedAt:string|null;notes:string|null}|null};
}
const props=defineProps<Props>();
const reason=ref('');
function date(value:string|null){if(!value)return '—';return new Intl.DateTimeFormat('en-MY',{dateStyle:'medium',timeStyle:'short',timeZone:props.app.displayTimezone}).format(new Date(value));}
function cancel(){if(!reason.value.trim())return;router.post(`/admin-vue/bookings/${props.booking.id}/cancel`,{reason:reason.value},{preserveScroll:true});}
</script>
<template>
<AdminLayout>
<a v-if="booking.session" :href="booking.session.detailUrl" class="mb-4 block text-sm text-slate-600">← Back to session workspace</a>
<div class="mb-5"><p class="text-sm font-medium text-[#0F4C81]">{{booking.reference}}</p><h1 class="mt-1 text-2xl font-semibold">{{booking.participant.name}}</h1><p class="mt-1 text-sm text-slate-500">{{booking.status}} · Registered {{date(booking.registeredAt)}}</p></div>
<div class="grid gap-5 lg:grid-cols-2"><Card><CardContent class="p-5"><h2 class="font-medium">Participant</h2><dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><dt class="text-slate-500">Name</dt><dd>{{booking.participant.name}}</dd><dt class="text-slate-500">Email</dt><dd>{{booking.participant.email||'—'}}</dd><dt class="text-slate-500">Booking reference</dt><dd class="font-mono">{{booking.reference}}</dd><dt class="text-slate-500">Booking status</dt><dd>{{booking.status}}</dd></dl></CardContent></Card><Card><CardContent class="p-5"><h2 class="font-medium">Training session</h2><p v-if="booking.session" class="mt-4 text-sm"><span class="font-medium">{{booking.session.title}}</span><br/><span class="text-slate-500">{{booking.session.code}} · {{date(booking.session.startsAt)}} · {{booking.session.venue}}</span></p><p v-else class="mt-4 text-sm text-slate-500">No associated session.</p></CardContent></Card><Card><CardContent class="p-5"><h2 class="font-medium">Payment and order</h2><template v-if="booking.order"><p class="mt-4 text-sm">Order {{booking.order.number}} · MYR {{booking.order.total}} · {{booking.order.status}}</p><ul class="mt-3 divide-y"><li v-for="payment in booking.order.payments" :key="payment.id" class="py-2 text-sm"><span class="font-mono">MYR {{payment.amount}}</span> · {{payment.status||'—'}} · {{date(payment.paidAt)}}</li></ul><p v-if="booking.order.refunds.length" class="mt-3 text-xs text-slate-500">Refund records: {{booking.order.refunds.length}}</p></template><p v-else class="mt-4 text-sm text-slate-500">No associated order.</p></CardContent></Card><Card><CardContent class="p-5"><h2 class="font-medium">Attendance</h2><p v-if="booking.attendance" class="mt-4 text-sm">{{booking.attendance.attendanceStatus||'—'}} · {{booking.attendance.reviewStatus||'Draft'}}<span v-if="booking.attendance.notes" class="block mt-2 text-slate-500">{{booking.attendance.notes}}</span></p><p v-else class="mt-4 text-sm text-slate-500">No attendance record.</p></CardContent></Card></div>
<Card v-if="['pending_payment','confirmed'].includes(booking.statusValue)" class="mt-5"><CardContent class="p-5"><h2 class="font-medium">Cancellation</h2><textarea v-model="reason" class="mt-3 min-h-24 w-full rounded-md border p-3 text-sm" placeholder="Cancellation reason"/><button type="button" :disabled="!reason.trim()" class="mt-3 rounded-md border border-red-300 px-4 py-2 text-sm text-red-700 disabled:opacity-50" @click="cancel">Cancel booking</button></CardContent></Card>
</AdminLayout>
</template>
