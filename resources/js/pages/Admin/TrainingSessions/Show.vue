<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { CalendarDays, FileText, Upload } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { EtmsSharedProps } from '@/types/etms';

interface Participant { id:number; reference:string; participant:{name:string;email:string|null}; bookingStatus:string; bookingStatusValue:string; paymentStatus:string|null; attendanceStatus:string|null; attendanceReviewStatus:string|null; registeredAt:string|null; detailUrl:string }
interface Paginator<T>{data:T[];current_page:number;last_page:number;from:number|null;to:number|null;total:number;links:{url:string|null;label:string;active:boolean}[]}
interface Props extends EtmsSharedProps {
    session:{id:number;title:string;description:string|null;status:string;statusValue:string;sessionCode:string;startsAt:string|null;endsAt:string|null;registrationOpensAt:string|null;registrationClosesAt:string|null;venueName:string|null;venueAddress:string|null;venueCity:string|null;venueState:string|null;capacity:number;confirmedCount:number;reservedCount:number;price:string;module:{code:string;title:string}|null;instructors:{name:string;role:string|null}[];isAvailableIndependently:boolean;independentPrice:string|null;mainDocument:{id:number;title:string;fileName:string|null;status:string}|null;editUrl:string};
    participants:Paginator<Participant>;
    participantFilters:{q:string;booking_status:string};
    participantStatuses:{value:string;label:string}[];
    materials:{id:number;title:string;fileName:string|null;description:string|null;type:string|null;status:string;isMainDocument:boolean;isDownloadable:boolean}[];
    recordings:{id:number;title:string;description:string|null;status:string|null;encodingStatus:string|null;submittedBy:string|null;reviewedBy:string|null;submittedAt:string|null;reviewNotes:string|null}[];
    attendance:{id:number;bookingId:number;participant:{name:string;email:string|null};attendanceStatus:string|null;reviewStatus:string|null;submittedAt:string|null;verifiedAt:string|null;submittedBy:string|null;verifiedBy:string|null;notes:string|null}[];
    completion:{submitted:boolean;submittedAt:string|null;submittedBy:string|null;notes:string|null;status:string;eligibleParticipantCount:number;eligibleUserIds:number[];issuedCertificateCount:number};
    materialTypes:{value:string;label:string}[];
    materialStatuses:{value:string;label:string}[];
}

const props=defineProps<Props>();
const tabs=['Overview','Participants','Learning Material','Session Content','Attendance','Completion'] as const;
type Tab=typeof tabs[number];
const activeTab=ref<Tab>('Overview');
const participantFilters=reactive({...props.participantFilters});
const mainUpload=reactive({title:'',description:'',status:'published',isMainDocument:true,file:null as File|null,isAvailableIndependently:props.session.isAvailableIndependently,independentPrice:props.session.independentPrice ?? ''});
const learningBusy=ref(false);
const learningError=ref('');
const contentUpload=reactive({
    title:'',
    description:'',
    materialType:'document',
    status:'pending_review',
    isDownloadable:true,
    file:null as File|null,
});
const recordingUpload=reactive({
    title:'',
    description:'',
    status:'pending_review',
    file:null as File|null,
});
const contentBusy=ref(false);
const contentError=ref('');
const recordingBusy=ref(false);
const recordingError=ref('');
const mainFileKey=ref(0);
const contentFileKey=ref(0);
const recordingFileKey=ref(0);

function date(value:string|null):string{if(!value)return '—';return new Intl.DateTimeFormat('en-MY',{dateStyle:'medium',timeStyle:'short',timeZone:props.app.displayTimezone}).format(new Date(value));}
function filterParticipants(){router.get(`/admin-vue/sessions/${props.session.id}`,{q:participantFilters.q,booking_status:participantFilters.booking_status},{preserveState:true,replace:true});}
function onFile(event:Event,target:typeof mainUpload|typeof contentUpload|typeof recordingUpload):void{
    target.file=((event.target as HTMLInputElement).files?.[0]??null);
    if(target.file){
        const maxBytes=target===recordingUpload?2097152*1024:102400*1024;
        if(target.file.size>maxBytes){
            target.file=null;
            learningError.value=target===mainUpload?'The document is larger than 100 MB.':'';
            contentError.value=target===contentUpload?'The document is larger than 100 MB.':'';
            recordingError.value=target===recordingUpload?'The recording is larger than 2 GB.':'';
            (event.target as HTMLInputElement).value='';
        }
    }
}
function uploadMain(): void {
    if (!mainUpload.title.trim()) {
        learningError.value='Enter a document title.';
        return;
    }
    if (!mainUpload.file) {
        learningError.value='Choose a PDF document.';
        return;
    }
    learningBusy.value=true;
    learningError.value='';
    router.post(`/admin-vue/sessions/${props.session.id}/materials`, {
        title: mainUpload.title,
        description: mainUpload.description,
        status: mainUpload.status,
        is_downloadable: true,
        is_main_document: true,
        is_available_independently: mainUpload.isAvailableIndependently,
        independent_price: mainUpload.isAvailableIndependently ? mainUpload.independentPrice : null,
        material_type: 'pdf',
        file: mainUpload.file,
    }, {
        forceFormData:true,
        preserveScroll:true,
        onError:(errors)=>{learningError.value=Object.values(errors)[0]??'Unable to upload the learning document.'},
        onFinish:()=>{learningBusy.value=false},
        onSuccess:()=>{mainUpload.title='';mainUpload.description='';mainUpload.file=null;mainUpload.independentPrice='';learningError.value='';mainFileKey.value++},
    });
}
function uploadContent(): void {
    if (!contentUpload.title.trim()) {
        contentError.value='Enter a document title.';
        return;
    }
    if (!contentUpload.file) {
        contentError.value='Choose a supporting document.';
        return;
    }
    contentBusy.value=true;
    contentError.value='';
    router.post(`/admin-vue/sessions/${props.session.id}/materials`,{
        title:contentUpload.title,
        description:contentUpload.description,
        status:contentUpload.status,
        is_downloadable:contentUpload.isDownloadable,
        is_main_document:false,
        material_type:contentUpload.materialType,
        file:contentUpload.file,
    },{
        forceFormData:true,
        preserveScroll:true,
        onError:(errors)=>{contentError.value=Object.values(errors)[0]??'Unable to upload the document.'},
        onFinish:()=>{contentBusy.value=false},
        onSuccess:()=>{contentUpload.title='';contentUpload.description='';contentUpload.file=null;contentUpload.materialType='document';contentError.value='';contentFileKey.value++},
    });
}
function uploadRecording(): void {
    if (!recordingUpload.title.trim()) {
        recordingError.value='Enter a recording title.';
        return;
    }
    if (!recordingUpload.file) {
        recordingError.value='Choose a video recording.';
        return;
    }
    recordingBusy.value=true;
    recordingError.value='';
    router.post('/admin-vue/recordings',{
        training_session_id:props.session.id,
        title:recordingUpload.title,
        description:recordingUpload.description,
        status:'pending_review',
        file:recordingUpload.file,
    },{
        forceFormData:true,
        preserveScroll:true,
        onError:(errors)=>{recordingError.value=Object.values(errors)[0]??'Unable to upload the recording.'},
        onFinish:()=>{recordingBusy.value=false},
        onSuccess:()=>{recordingUpload.title='';recordingUpload.description='';recordingUpload.file=null;recordingError.value='';recordingFileKey.value++},
    });
}
function deleteContent(materialId:number): void {
    if (!window.confirm('Remove this session document?')) return;
    router.delete(`/admin-vue/sessions/${props.session.id}/materials/${materialId}`,{
        preserveScroll:true,
    });
}
function verifyAttendance(id:number){router.post(`/admin-vue/attendance/${id}/verify`,{},{preserveScroll:true});}
function correctionAttendance(id:number){router.post(`/admin-vue/attendance/${id}/request-correction`,{verification_notes:'Please review and correct this attendance submission.'},{preserveScroll:true});}
function approveRecording(id:number){router.post(`/admin-vue/recordings/${id}/approve`,{},{preserveScroll:true});}
function rejectRecording(id:number){const reason=window.prompt('Reason for rejection');if(reason?.trim())router.post(`/admin-vue/recordings/${id}/reject`,{review_notes:reason},{preserveScroll:true});}
function unpublishRecording(id:number){router.post(`/admin-vue/recordings/${id}/unpublish`,{},{preserveScroll:true});}
function issueCertificate(userId:number){router.post('/admin-vue/certificates/issue',{trainee_id:userId,record_type:'session',record_id:props.session.id,completion_date:props.session.startsAt?.slice(0,10)??new Date().toISOString().slice(0,10)},{preserveScroll:true});}
function runSessionAction(action:'publish'|'close-registration'|'complete'|'reconcile-seats'):void{router.post(`/admin-vue/sessions/${props.session.id}/${action}`,{}, {preserveScroll:true});}
function cancelSession():void{const reason=window.prompt('Cancellation reason');if(reason?.trim())router.post(`/admin-vue/sessions/${props.session.id}/cancel`,{cancellation_reason:reason},{preserveScroll:true});}
function notifyParticipants():void{const message=window.prompt('Session update message');if(message?.trim())router.post(`/admin-vue/sessions/${props.session.id}/notify`,{update_message:message},{preserveScroll:true});}
function deleteSession():void{if(!window.confirm('Delete this training session? This is only allowed when no protected records are associated.'))return;router.delete(`/admin-vue/sessions/${props.session.id}`,{preserveScroll:true});}
</script>

<template>
<AdminLayout>
    <a href="/admin-vue/sessions" class="mb-4 block text-sm text-slate-600">← Back to training sessions</a>
    <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
        <div><p class="text-sm font-medium text-[#0F4C81]">{{session.sessionCode}}</p><h1 class="mt-1 text-2xl font-semibold">{{session.title}}</h1><p class="mt-1 text-sm text-slate-500">{{session.status}} · {{date(session.startsAt)}}</p></div>
        <div class="flex flex-wrap justify-end gap-2">
            <a :href="session.editUrl" class="inline-flex h-10 items-center rounded-md border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">Edit session</a>
            <button v-if="session.statusValue==='draft'||session.statusValue==='registration_closed'" type="button" class="h-10 rounded-md border border-[#0F4C81] px-3 text-sm text-[#0F4C81]" @click="runSessionAction('publish')">Publish</button>
            <button v-if="session.statusValue==='published'||session.statusValue==='full'" type="button" class="h-10 rounded-md border border-amber-300 px-3 text-sm text-amber-800" @click="runSessionAction('close-registration')">Close registration</button>
            <button v-if="['published','full','registration_closed','in_progress'].includes(session.statusValue)" type="button" class="h-10 rounded-md border border-emerald-300 px-3 text-sm text-emerald-800" @click="runSessionAction('complete')">Complete</button>
            <button type="button" class="h-10 rounded-md border px-3 text-sm" @click="runSessionAction('reconcile-seats')">Reconcile seats</button>
            <button v-if="session.confirmedCount>0" type="button" class="h-10 rounded-md border px-3 text-sm" @click="notifyParticipants">Notify participants</button>
            <button v-if="!['cancelled','completed'].includes(session.statusValue)" type="button" class="h-10 rounded-md border border-red-300 px-3 text-sm text-red-700" @click="cancelSession">Cancel</button>
            <button type="button" class="h-10 rounded-md border border-red-300 px-3 text-sm text-red-700" @click="deleteSession">Delete</button>
        </div>
    </div>
    <div class="mb-5 flex flex-wrap gap-1 border-b border-slate-200">
        <button v-for="tab in tabs" :key="tab" type="button" :class="['border-b-2 px-3 py-2 text-sm',activeTab===tab?'border-[#0F4C81] text-[#0F4C81]':'border-transparent text-slate-500']" @click="activeTab=tab">{{tab}}</button>
    </div>

    <Card v-if="activeTab==='Overview'"><CardContent class="grid gap-5 p-5 md:grid-cols-2">
        <div><h2 class="text-sm font-medium text-slate-500">Description</h2><p class="mt-2 text-sm">{{session.description||'No description'}}</p></div>
        <div><h2 class="text-sm font-medium text-slate-500">Schedule</h2><p class="mt-2 text-sm">{{date(session.startsAt)}} – {{date(session.endsAt)}}</p></div>
        <div><h2 class="text-sm font-medium text-slate-500">Venue</h2><p class="mt-2 text-sm">{{[session.venueName,session.venueAddress,session.venueCity,session.venueState].filter(Boolean).join(', ')||'Not set'}}</p></div>
        <div><h2 class="text-sm font-medium text-slate-500">Instructor(s)</h2><p class="mt-2 text-sm">{{session.instructors.map(i=>i.name).join(', ')||'Unassigned'}}</p></div>
        <div><h2 class="text-sm font-medium text-slate-500">Capacity</h2><p class="mt-2 text-sm">{{session.confirmedCount}} confirmed · {{session.reservedCount}} reserved · {{session.capacity}} total</p></div>
        <div><h2 class="text-sm font-medium text-slate-500">Physical price</h2><p class="mt-2 text-sm font-mono">MYR {{session.price}}</p></div>
        <div><h2 class="text-sm font-medium text-slate-500">Registration window</h2><p class="mt-2 text-sm">{{date(session.registrationOpensAt)}} – {{date(session.registrationClosesAt)}}</p></div>
        <div><h2 class="text-sm font-medium text-slate-500">Main learning document</h2><p class="mt-2 text-sm">{{session.mainDocument?`${session.mainDocument.title} · ${session.mainDocument.fileName||'File available'}`:'No main document uploaded'}}</p><p v-if="session.isAvailableIndependently" class="mt-1 text-xs text-slate-500">Independent purchase: MYR {{session.independentPrice||'0.00'}}</p></div>
    </CardContent></Card>

    <Card v-if="activeTab==='Participants'"><CardContent class="p-4">
        <form class="mb-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="filterParticipants"><input v-model="participantFilters.q" class="h-10 flex-1 rounded-md border px-3 text-sm" placeholder="Search participant or booking reference"/><select v-model="participantFilters.booking_status" class="h-10 rounded-md border px-3 text-sm"><option value="">All booking statuses</option><option v-for="status in participantStatuses" :key="status.value" :value="status.value">{{status.label}}</option></select><button class="h-10 rounded-md bg-slate-900 px-4 text-sm text-white">Apply</button></form>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Participant</th><th class="px-3 py-3">Booking</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Payment</th><th class="px-3 py-3">Attendance</th><th class="px-3 py-3">Registered</th><th class="px-3 py-3"></th></tr></thead><tbody class="divide-y"><tr v-for="participant in participants.data" :key="participant.id"><td class="px-3 py-3"><p class="font-medium">{{participant.participant.name}}</p><p class="text-xs text-slate-500">{{participant.participant.email}}</p></td><td class="px-3 py-3 font-mono text-xs">{{participant.reference}}</td><td class="px-3 py-3">{{participant.bookingStatus}}</td><td class="px-3 py-3">{{participant.paymentStatus||'—'}}</td><td class="px-3 py-3">{{participant.attendanceStatus||'—'}}<p v-if="participant.attendanceReviewStatus" class="text-xs text-slate-500">{{participant.attendanceReviewStatus}}</p></td><td class="px-3 py-3 text-slate-600">{{date(participant.registeredAt)}}</td><td class="px-3 py-3"><a :href="participant.detailUrl" class="text-xs font-medium text-[#0F4C81]">View details</a></td></tr><tr v-if="!participants.data.length"><td colspan="7" class="px-3 py-12 text-center text-slate-500">No participants found.</td></tr></tbody></table></div>
    </CardContent></Card>

    <Card v-if="activeTab==='Learning Material'">
        <CardContent class="p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Main learning document</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{session.mainDocument?.title||'No main document uploaded'}}
                        <span v-if="session.mainDocument?.fileName"> · {{session.mainDocument.fileName}}</span>
                    </p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                    {{session.mainDocument?.status||'Not uploaded'}}
                </span>
            </div>

            <div class="mt-5 grid gap-4 rounded-lg border border-slate-200 p-4 md:grid-cols-2">
                <label class="text-sm text-slate-700">Document title
                    <input v-model="mainUpload.title" class="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm" placeholder="e.g. Hydrology Training Manual"/>
                </label>
                <label class="text-sm text-slate-700">Publication status
                    <select v-model="mainUpload.status" class="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm">
                        <option v-for="status in materialStatuses" :key="status.value" :value="status.value">{{status.label}}</option>
                    </select>
                </label>
                <label class="text-sm text-slate-700 md:col-span-2">Description
                    <textarea v-model="mainUpload.description" class="mt-1 min-h-20 w-full rounded-md border border-slate-300 p-3 text-sm" placeholder="Optional document description"/>
                </label>
                <label class="text-sm text-slate-700 md:col-span-2">PDF document
                    <input :key="mainFileKey" name="file" type="file" accept=".pdf,application/pdf" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm" @change="onFile($event,mainUpload)"/>
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="mainUpload.isAvailableIndependently" type="checkbox"/>
                    Available for independent purchase
                </label>
                <label v-if="mainUpload.isAvailableIndependently" class="text-sm text-slate-700">Independent purchase price (MYR)
                    <input v-model="mainUpload.independentPrice" type="number" min="0" step="0.01" class="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm"/>
                </label>
                <p v-if="learningError" class="text-sm text-red-700 md:col-span-2">{{learningError}}</p>
                <div class="flex justify-end md:col-span-2">
                    <button type="button" :disabled="learningBusy || !mainUpload.file || !mainUpload.title.trim()" class="inline-flex h-10 items-center gap-2 rounded-md bg-[#0F4C81] px-4 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50" @click="uploadMain">
                        <Upload :size="16"/>
                        {{learningBusy?'Uploading…':'Upload main document'}}
                    </button>
                </div>
            </div>
        </CardContent>
    </Card>

    <Card v-if="activeTab==='Session Content'">
        <CardContent class="grid gap-6 p-5 lg:grid-cols-2">
            <section class="rounded-lg border border-slate-200 p-5">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Additional documents</h2>
                    <p class="mt-1 text-sm text-slate-500">Upload presentations, worksheets, references or supporting files.</p>
                </div>
                <div class="mt-4 grid gap-3">
                    <label class="text-sm text-slate-700">Document title
                        <input v-model="contentUpload.title" class="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm" placeholder="Document title"/>
                    </label>
                    <label class="text-sm text-slate-700">Document type
                        <select v-model="contentUpload.materialType" class="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm">
                            <option value="pdf">PDF document</option>
                            <option value="presentation">Presentation</option>
                            <option value="document">Reference document</option>
                            <option value="worksheet">Technical worksheet</option>
                            <option value="image">Diagram or image</option>
                            <option value="other">Supporting file</option>
                        </select>
                    </label>
                    <label class="text-sm text-slate-700">Publication status
                        <select v-model="contentUpload.status" class="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm">
                            <option value="pending_review">Pending review</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label class="text-sm text-slate-700">File
                        <input :key="contentFileKey" name="file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.png,.jpg,.jpeg" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm" @change="onFile($event,contentUpload)"/>
                    </label>
                    <label class="text-sm text-slate-700">Description
                        <textarea v-model="contentUpload.description" class="mt-1 min-h-20 w-full rounded-md border border-slate-300 p-3 text-sm" placeholder="Optional document description"/>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="contentUpload.isDownloadable" type="checkbox"/>
                        Downloadable
                    </label>
                    <p v-if="contentError" class="text-sm text-red-700">{{contentError}}</p>
                    <button type="button" :disabled="contentBusy || !contentUpload.file || !contentUpload.title.trim()" class="h-10 rounded-md bg-[#0F4C81] px-4 text-sm font-medium text-white disabled:opacity-50" @click="uploadContent">
                        {{contentBusy?'Uploading…':'Upload document'}}
                    </button>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 p-5">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Video recordings</h2>
                    <p class="mt-1 text-sm text-slate-500">Upload a recording for administrator review and publication.</p>
                </div>
                <div class="mt-4 grid gap-3">
                    <label class="text-sm text-slate-700">Recording title
                        <input v-model="recordingUpload.title" class="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm" placeholder="Recording title"/>
                    </label>
                    <label class="text-sm text-slate-700">Video file
                        <input :key="recordingFileKey" name="file" type="file" accept=".mp4,.mov,.mkv,.webm" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm" @change="onFile($event,recordingUpload)"/>
                    </label>
                    <label class="text-sm text-slate-700">Description
                        <textarea v-model="recordingUpload.description" class="mt-1 min-h-20 w-full rounded-md border border-slate-300 p-3 text-sm" placeholder="Optional recording description"/>
                    </label>
                    <p v-if="recordingError" class="text-sm text-red-700">{{recordingError}}</p>
                    <button type="button" :disabled="recordingBusy || !recordingUpload.file || !recordingUpload.title.trim()" class="h-10 rounded-md bg-slate-900 px-4 text-sm font-medium text-white disabled:opacity-50" @click="uploadRecording">
                        {{recordingBusy?'Uploading…':'Upload recording'}}
                    </button>
                </div>
            </section>

            <section class="lg:col-span-2">
                <h3 class="text-sm font-semibold text-slate-900">Session documents</h3>
                <div v-if="materials.filter(m=>!m.isMainDocument).length" class="mt-3 divide-y rounded-lg border border-slate-200">
                    <div v-for="material in materials.filter(m=>!m.isMainDocument)" :key="material.id" class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{material.title}}</p>
                            <p class="text-xs text-slate-500">{{material.fileName||'File'}} · {{material.status}}</p>
                        </div>
                        <button type="button" class="w-fit rounded-md border border-red-300 px-3 py-1.5 text-xs text-red-700" @click="deleteContent(material.id)">Remove</button>
                    </div>
                </div>
                <p v-else class="mt-3 rounded-lg border border-slate-200 p-6 text-center text-sm text-slate-500">No additional documents uploaded.</p>
            </section>

            <section class="lg:col-span-2">
                <h3 class="text-sm font-semibold text-slate-900">Session recordings</h3>
                <div v-if="recordings.length" class="mt-3 grid gap-3">
                    <div v-for="recording in recordings" :key="recording.id" class="flex flex-col gap-3 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900">{{recording.title}}</p>
                            <p class="mt-1 text-xs text-slate-500">{{recording.status}} · {{recording.encodingStatus||'Pending'}} · {{recording.submittedBy||'Unknown submitter'}}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button v-if="recording.status!=='published'" type="button" class="rounded-md bg-emerald-700 px-3 py-1.5 text-xs text-white" @click="approveRecording(recording.id)">Approve</button>
                            <button v-if="recording.status==='published'" type="button" class="rounded-md border px-3 py-1.5 text-xs" @click="unpublishRecording(recording.id)">Unpublish</button>
                            <button v-if="recording.status!=='rejected'" type="button" class="rounded-md border border-red-300 px-3 py-1.5 text-xs text-red-700" @click="rejectRecording(recording.id)">Reject</button>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-3 rounded-lg border border-slate-200 p-6 text-center text-sm text-slate-500">No recordings uploaded.</p>
            </section>
        </CardContent>
    </Card>

    <Card v-if="activeTab==='Attendance'"><CardContent class="p-4"><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Participant</th><th class="px-3 py-3">Attendance</th><th class="px-3 py-3">Submission</th><th class="px-3 py-3">Verification</th><th class="px-3 py-3">History</th><th class="px-3 py-3"></th></tr></thead><tbody class="divide-y"><tr v-for="record in attendance" :key="record.id"><td class="px-3 py-3"><p class="font-medium">{{record.participant.name}}</p><p class="text-xs text-slate-500">{{record.participant.email}}</p></td><td class="px-3 py-3">{{record.attendanceStatus||'—'}}</td><td class="px-3 py-3"><p>{{record.reviewStatus||'Draft'}}</p><p class="text-xs text-slate-500">{{record.submittedBy||'—'}} · {{date(record.submittedAt)}}</p></td><td class="px-3 py-3">{{record.verifiedBy||'Not verified'}}<p v-if="record.verifiedAt" class="text-xs text-slate-500">{{date(record.verifiedAt)}}</p></td><td class="max-w-xs px-3 py-3 text-xs text-slate-500">{{record.notes||'—'}}</td><td class="px-3 py-3"><div v-if="record.reviewStatus!=='Verified'" class="flex gap-2"><button type="button" class="text-xs font-medium text-emerald-700" @click="verifyAttendance(record.id)">Verify</button><button type="button" class="text-xs font-medium text-amber-700" @click="correctionAttendance(record.id)">Request correction</button></div></td></tr><tr v-if="!attendance.length"><td colspan="6" class="px-3 py-12 text-center text-slate-500">No attendance records submitted.</td></tr></tbody></table></div></CardContent></Card>

    <Card v-if="activeTab==='Completion'"><CardContent class="grid gap-5 p-5 md:grid-cols-2"><div><h2 class="text-sm font-medium text-slate-500">Instructor completion report</h2><p class="mt-2 text-sm">{{completion.submitted?'Submitted':'Not submitted'}}</p><p v-if="completion.submittedAt" class="mt-1 text-xs text-slate-500">{{completion.submittedBy||'Unknown'}} · {{date(completion.submittedAt)}}</p><p v-if="completion.notes" class="mt-2 text-sm">{{completion.notes}}</p></div><div><h2 class="text-sm font-medium text-slate-500">Session completion</h2><p class="mt-2 text-sm">{{completion.status==='completed'?'Approved completion':'Pending approval'}}</p></div><div><h2 class="text-sm font-medium text-slate-500">Eligible participants</h2><p class="mt-2 text-sm">{{completion.eligibleParticipantCount}} verified-present attendees</p></div><div><h2 class="text-sm font-medium text-slate-500">Certificates</h2><p class="mt-2 text-sm">{{completion.issuedCertificateCount}} issued for this session</p><button v-for="userId in completion.eligibleUserIds" :key="userId" type="button" class="mt-3 mr-2 rounded-md border px-3 py-1.5 text-xs" @click="issueCertificate(userId)">Issue certificate</button></div></CardContent></Card>
</AdminLayout>
</template>
