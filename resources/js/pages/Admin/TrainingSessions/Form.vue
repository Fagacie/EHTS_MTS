<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface Option { value: string; label: string }
interface InstructorAssignment { instructor_id: string; role_label: string }
interface MaterialRow { id:number; title:string; description:string|null; material_type:string; status:string; original_filename:string|null; is_downloadable:boolean; is_main_document:boolean }
interface Props extends EtmsSharedProps {
    mode:'create'|'edit';
    session?: Record<string, any>;
    modules:Option[]; statuses:Option[]; recordingPolicies:Option[]; instructors:Option[];
    materialTypes?:Option[]; materialStatuses?:Option[];
}
const props=defineProps<Props>();
const form=reactive({
    title: props.session?.title ?? '', description: props.session?.description ?? '',
    starts_at: props.session?.starts_at ?? '', ends_at: props.session?.ends_at ?? '',
    registration_opens_at: props.session?.registration_opens_at ?? '', registration_closes_at: props.session?.registration_closes_at ?? '',
    venue_name: props.session?.venue_name ?? '', venue_address: props.session?.venue_address ?? '',
    venue_city: props.session?.venue_city ?? 'Shah Alam', venue_state: props.session?.venue_state ?? 'Selangor',
    capacity: props.session?.capacity ?? 25, price: props.session?.price ?? '',
    recording_access_policy: props.session?.recording_access_policy ?? 'attendees_only',
    is_available_independently: props.session?.is_available_independently ?? false, independent_price: props.session?.independent_price ?? '',
    main_document_material_id: props.session?.main_document_material_id ? String(props.session.main_document_material_id) : '',
    status: props.session?.status ?? 'draft',
    instructors: [...(props.session?.instructors ?? [])] as InstructorAssignment[],
    cover_image: null as File | null,
});
const busy=ref(false);
const coverPreview=ref<string|null>(props.session?.cover_image_url ?? null);
function onCoverImage(event:Event){form.cover_image=((event.target as HTMLInputElement).files?.[0]??null);coverPreview.value=form.cover_image?URL.createObjectURL(form.cover_image):props.session?.cover_image_url??null;}
const materialForm=reactive({title:'',description:'',material_type:'pdf',status:'published',is_downloadable:true,is_main_document:false,file:null as File|null});
const editingMaterial=ref<MaterialRow|null>(null);
const editingMaterialFile=ref<File|null>(null);
function addInstructor(){form.instructors.push({instructor_id:'',role_label:'Lead Instructor'});}
function submit(){
    busy.value=true;
    if(props.mode==='create'){router.post('/admin-vue/sessions',form,{forceFormData:true,onFinish:()=>busy.value=false});}
    else{router.post(`/admin-vue/sessions/${props.session!.id}`,{...form,_method:'put'}, {forceFormData:true,onFinish:()=>busy.value=false});}
}
function onMaterialFile(event:Event){materialForm.file=((event.target as HTMLInputElement).files?.[0]??null);}
function uploadMaterial(){
    if(!props.session||!materialForm.file)return;
    router.post(`/admin-vue/sessions/${props.session.id}/materials`,materialForm as any,{forceFormData:true,preserveScroll:true,onSuccess:()=>{materialForm.title='';materialForm.description='';materialForm.file=null;}});
}
function deleteMaterial(material:MaterialRow){router.delete(`/admin-vue/sessions/${props.session!.id}/materials/${material.id}`,{preserveScroll:true});}
function beginEditMaterial(material:MaterialRow){
    editingMaterial.value={...material};
    editingMaterialFile.value=null;
}
function onEditMaterialFile(event:Event){editingMaterialFile.value=((event.target as HTMLInputElement).files?.[0]??null);}
function saveMaterialEdit(){
    if(!editingMaterial.value||!props.session)return;
    router.post(`/admin-vue/sessions/${props.session.id}/materials/${editingMaterial.value.id}`,{
        title:editingMaterial.value.title,
        description:editingMaterial.value.description,
        material_type:editingMaterial.value.material_type,
        status:editingMaterial.value.status,
        is_downloadable:editingMaterial.value.is_downloadable,
        is_main_document:editingMaterial.value.is_main_document,
        file:editingMaterialFile.value,
    } as any,{forceFormData:true,preserveScroll:true,onSuccess:()=>{editingMaterial.value=null;editingMaterialFile.value=null;}});
}
</script>
<template>
<AdminLayout>
<a href="/admin-vue/sessions" class="mb-4 block text-sm text-slate-600">← Back to sessions</a>
<h1 class="text-2xl font-semibold">{{mode==='create'?'Create training session':'Edit training session'}}</h1>
<form class="mt-6 max-w-5xl space-y-6" @submit.prevent="submit">
<section class="grid gap-4 rounded-lg border bg-white p-5 md:grid-cols-2">
<label class="text-sm md:col-span-2">Session title<input v-model="form.title" required maxlength="200" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm md:col-span-2">Description<textarea v-model="form.description" class="mt-1 min-h-24 w-full rounded-md border p-3"/></label>
<div class="md:col-span-2 flex items-center gap-4">
<img v-if="coverPreview" :src="coverPreview" alt="Session cover" class="h-24 w-40 rounded-md object-cover border"/>
<span v-else class="grid h-24 w-40 place-items-center rounded-md border bg-slate-50 text-xs text-slate-500">No cover</span>
<label class="text-sm">Programme cover image<input type="file" accept="image/*" class="mt-1 block text-sm" @change="onCoverImage"/></label>
</div>
</section>
<section class="grid gap-4 rounded-lg border bg-white p-5 md:grid-cols-3">
<label class="text-sm">Starts<input v-model="form.starts_at" type="datetime-local" required class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm">Ends<input v-model="form.ends_at" type="datetime-local" required class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm">Registration opens<input v-model="form.registration_opens_at" type="datetime-local" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm md:col-span-2">Registration closes<input v-model="form.registration_closes_at" type="datetime-local" required class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm">Lifecycle<select v-model="form.status" required class="mt-1 h-10 w-full rounded-md border px-3"><option v-for="status in statuses" :key="status.value" :value="status.value">{{status.label}}</option></select></label>
</section>
<section class="grid gap-4 rounded-lg border bg-white p-5 md:grid-cols-2">
<label class="text-sm">Venue name<input v-model="form.venue_name" required maxlength="200" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm">City<input v-model="form.venue_city" required maxlength="100" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm md:col-span-2">Street address<textarea v-model="form.venue_address" required maxlength="255" class="mt-1 min-h-20 w-full rounded-md border p-3"/></label>
<label class="text-sm">State<input v-model="form.venue_state" required maxlength="100" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm">Seat capacity<input v-model="form.capacity" type="number" min="1" required class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="text-sm">Session price (MYR)<input v-model="form.price" type="number" min="0" step="0.01" required class="mt-1 h-10 w-full rounded-md border px-3"/></label>
<label class="flex items-center gap-2 text-sm"><input v-model="form.is_available_independently" type="checkbox"/>Offer independent document purchase</label>
<label v-if="form.is_available_independently" class="text-sm">Independent price (MYR)<input v-model="form.independent_price" type="number" min="0" step="0.01" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
</section>
<section class="rounded-lg border bg-white p-5">
<div class="flex items-center justify-between"><h2 class="font-medium">Assigned instructors</h2><button type="button" class="rounded-md border px-3 py-1.5 text-sm" @click="addInstructor">Add instructor</button></div>
<div v-for="(assignment,index) in form.instructors" :key="index" class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
<select v-model="assignment.instructor_id" required class="h-10 rounded-md border px-3"><option value="">Select instructor</option><option v-for="instructor in instructors" :key="instructor.value" :value="instructor.value">{{instructor.label}}</option></select>
<input v-model="assignment.role_label" required maxlength="80" class="h-10 rounded-md border px-3" placeholder="Role label"/>
<button type="button" class="rounded-md border px-3 text-sm text-red-700" @click="form.instructors.splice(index,1)">Remove</button>
</div>
</section>
<section v-if="mode==='edit'" class="rounded-lg border bg-white p-5">
<h2 class="font-medium">Session materials</h2>
<div class="mt-3 grid gap-3 md:grid-cols-2">
<input v-model="materialForm.title" class="h-10 rounded-md border px-3" placeholder="Material title"/>
<select v-model="materialForm.material_type" class="h-10 rounded-md border px-3"><option v-for="type in materialTypes" :key="type.value" :value="type.value">{{type.label}}</option></select>
<select v-model="materialForm.status" class="h-10 rounded-md border px-3"><option v-for="status in materialStatuses" :key="status.value" :value="status.value">{{status.label}}</option></select>
<input type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.png,.jpg,.jpeg" class="text-sm" @change="onMaterialFile"/>
<textarea v-model="materialForm.description" class="min-h-20 rounded-md border p-3 md:col-span-2" placeholder="Description"/>
<label class="flex items-center gap-2 text-sm"><input v-model="materialForm.is_downloadable" type="checkbox"/>Downloadable</label>
<label class="flex items-center gap-2 text-sm"><input v-model="materialForm.is_main_document" type="checkbox"/>Main independent-purchase document</label>
</div>
<button type="button" :disabled="!materialForm.file||!materialForm.title" class="mt-3 rounded-md bg-slate-900 px-3 py-2 text-sm text-white" @click="uploadMaterial">Upload material</button>
<ul class="mt-4 divide-y"><li v-for="material in session?.materials" :key="material.id" class="py-3 text-sm"><div class="flex items-center justify-between"><div><p class="font-medium">{{material.title}}</p><p class="text-xs text-slate-500">{{material.original_filename}} · {{material.status}}</p></div><div class="flex gap-3"><button type="button" class="text-[#0F4C81]" @click="beginEditMaterial(material)">Edit</button><button type="button" class="text-red-700" @click="deleteMaterial(material)">Delete</button></div></div></li></ul>
<div v-if="editingMaterial" class="mt-4 grid gap-3 rounded-md border p-4 md:grid-cols-2">
<input v-model="editingMaterial.title" class="h-10 rounded-md border px-3" />
<select v-model="editingMaterial.material_type" class="h-10 rounded-md border px-3"><option v-for="type in materialTypes" :key="type.value" :value="type.value">{{type.label}}</option></select>
<select v-model="editingMaterial.status" class="h-10 rounded-md border px-3"><option v-for="status in materialStatuses" :key="status.value" :value="status.value">{{status.label}}</option></select>
<textarea v-model="editingMaterial.description" class="min-h-20 rounded-md border p-3" />
<input type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.png,.jpg,.jpeg" class="text-sm" @change="onEditMaterialFile"/>
<label class="flex items-center gap-2"><input v-model="editingMaterial.is_downloadable" type="checkbox"/>Downloadable</label>
<label class="flex items-center gap-2"><input v-model="editingMaterial.is_main_document" type="checkbox"/>Main document</label>
<div class="md:col-span-2 flex justify-end gap-2"><button type="button" class="rounded-md border px-3 py-1.5" @click="editingMaterial=null">Cancel</button><button type="button" class="rounded-md bg-slate-900 px-3 py-1.5 text-white" @click="saveMaterialEdit">Save material</button></div>
</div>
</section>
<div class="flex justify-end gap-2"><a href="/admin-vue/sessions" class="rounded-md border px-4 py-2 text-sm">Cancel</a><button :disabled="busy" class="rounded-md bg-[#0F4C81] px-4 py-2 text-sm text-white">{{busy?'Saving…':'Save session'}}</button></div>
</form>
</AdminLayout>
</template>
