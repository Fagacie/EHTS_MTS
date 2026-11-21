<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';
interface Props extends EtmsSharedProps { settings:{issuing_organisation:string;signatory_name:string;signatory_title:string;logo_path:string;signature_path:string} }
const props=defineProps<Props>();
const form=reactive({...props.settings});
const busy=ref(false);
const logo=ref<File|null>(null);
const signature=ref<File|null>(null);
function handleLogo(event:Event){logo.value=((event.target as HTMLInputElement).files?.[0]??null);}
function handleSignature(event:Event){signature.value=((event.target as HTMLInputElement).files?.[0]??null);}
function submit(){busy.value=true;router.post('/admin-vue/certificate-settings',{...form,logo:logo.value,signature:signature.value},{forceFormData:true,onFinish:()=>busy.value=false});}
</script>
<template>
<AdminLayout>
<a href="/admin-vue/certificates" class="mb-4 block text-sm text-slate-600">← Back to certificates</a>
<h1 class="text-2xl font-semibold">Certificate signatory configuration</h1>
<p class="mt-1 text-sm text-slate-500">Global values are snapshotted onto newly issued certificates.</p>
<form class="mt-6 max-w-2xl space-y-4 rounded-lg border bg-white p-5" @submit.prevent="submit">
<label class="block text-sm">Issuing organisation<input v-model="form.issuing_organisation" required maxlength="200" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="block text-sm">Authorised signatory name<input v-model="form.signatory_name" required maxlength="200" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="block text-sm">Signatory designation<input v-model="form.signatory_title" required maxlength="200" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="block text-sm">Organisation logo<input type="file" accept="image/*,.svg" class="mt-1 block w-full text-sm" @change="handleLogo"/></label>
<label class="block text-sm">Authorised signature image<input type="file" accept="image/*,.svg" class="mt-1 block w-full text-sm" @change="handleSignature"/></label>
<div class="flex justify-end"><button :disabled="busy" class="rounded-md bg-[#0F4C81] px-4 py-2 text-sm text-white">{{busy?'Saving…':'Save configuration'}}</button></div>
</form>
</AdminLayout>
</template>
