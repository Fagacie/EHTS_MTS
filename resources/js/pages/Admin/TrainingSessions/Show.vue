<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { EtmsSharedProps } from '@/types/etms';

interface Participant {
    id: number;
    reference: string;
    participant: { name: string; email: string | null };
    bookingStatus: string;
    bookingStatusValue: string;
    paymentStatus: string | null;
    attendanceStatus: string | null;
    attendanceReviewStatus: string | null;
    registeredAt: string | null;
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
interface EligibleParticipant {
    userId: number;
    name: string;
    email: string | null;
    hasCertificate: boolean;
}
interface Props extends EtmsSharedProps {
    session: {
        id: number;
        title: string;
        description: string | null;
        status: string;
        statusValue: string;
        sessionCode: string;
        startsAt: string | null;
        endsAt: string | null;
        registrationOpensAt: string | null;
        registrationClosesAt: string | null;
        venueName: string | null;
        venueAddress: string | null;
        venueCity: string | null;
        venueState: string | null;
        capacity: number;
        confirmedCount: number;
        reservedCount: number;
        price: string;
        recordingAccessPolicy?: string;
        coverImageUrl?: string | null;
        instructors: { name: string; role: string | null }[];
        editUrl: string;
    };
    participants: Paginator<Participant>;
    participantFilters: { q: string; booking_status: string };
    participantStatuses: { value: string; label: string }[];
    materials: {
        id: number;
        title: string;
        fileName: string | null;
        fileSizeLabel?: string;
        description: string | null;
        type: string | null;
        status: string;
        isDownloadable: boolean;
        uploadedBy?: string | null;
        reviewedBy?: string | null;
        reviewNotes?: string | null;
        downloadUrl?: string;
    }[];
    recordings: {
        id: number;
        title: string;
        description: string | null;
        status: string | null;
        encodingStatus: string | null;
        submittedBy: string | null;
        reviewedBy: string | null;
        submittedAt: string | null;
        reviewNotes: string | null;
    }[];
    attendance: {
        id: number;
        bookingId: number;
        participant: { name: string; email: string | null };
        attendanceStatus: string | null;
        reviewStatus: string | null;
        submittedAt: string | null;
        verifiedAt: string | null;
        submittedBy: string | null;
        verifiedBy: string | null;
        remarks?: string | null;
        notes: string | null;
    }[];
    completion: {
        submitted: boolean;
        submittedAt: string | null;
        submittedBy: string | null;
        notes: string | null;
        status: string;
        eligibleParticipantCount: number;
        eligibleUserIds: number[];
        eligibleParticipants?: EligibleParticipant[];
        issuedCertificateCount: number;
    };
    materialTypes: { value: string; label: string }[];
    materialStatuses: { value: string; label: string }[];
}

const props = defineProps<Props>();
const page = usePage<EtmsSharedProps>();
const pageErrors = computed(() => {
    const errors = (page.props as { errors?: Record<string, string> }).errors ?? {};
    return Object.values(errors).filter(Boolean);
});
const tabs = ['Overview', 'Participants', 'Session Materials & Recordings', 'Attendance', 'Completion'] as const;
type Tab = (typeof tabs)[number];
const activeTab = ref<Tab>('Overview');
const participantFilters = reactive({ ...props.participantFilters });

const recordingUpload = reactive({
    title: '',
    description: '',
    status: 'pending_review',
    file: null as File | null,
});
const recordingBusy = ref(false);
const recordingError = ref('');
const recordingFileKey = ref(0);

function date(value: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-MY', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: props.app.displayTimezone,
    }).format(new Date(value));
}

function filterParticipants() {
    router.get(
        `/admin-vue/sessions/${props.session.id}`,
        { q: participantFilters.q, booking_status: participantFilters.booking_status },
        { preserveState: true, replace: true },
    );
}

function onFile(event: Event, target: typeof recordingUpload): void {
    target.file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (target.file) {
        const maxBytes = 2097152 * 1024;
        if (target.file.size > maxBytes) {
            target.file = null;
            recordingError.value = 'The recording is larger than 2 GB.';
            (event.target as HTMLInputElement).value = '';
        }
    }
}

function uploadRecording(): void {
    if (!recordingUpload.title.trim()) {
        recordingError.value = 'Enter a recording title.';
        return;
    }
    if (!recordingUpload.file) {
        recordingError.value = 'Choose a video recording.';
        return;
    }
    recordingBusy.value = true;
    recordingError.value = '';
    router.post(
        '/admin-vue/recordings',
        {
            training_session_id: props.session.id,
            title: recordingUpload.title,
            description: recordingUpload.description,
            status: 'pending_review',
            file: recordingUpload.file,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                recordingError.value = Object.values(errors)[0] ?? 'Unable to upload the recording.';
            },
            onFinish: () => {
                recordingBusy.value = false;
            },
            onSuccess: () => {
                recordingUpload.title = '';
                recordingUpload.description = '';
                recordingUpload.file = null;
                recordingError.value = '';
                recordingFileKey.value++;
            },
        },
    );
}

function approveMaterial(materialId: number): void {
    router.post(
        `/admin-vue/sessions/${props.session.id}/materials/${materialId}/review`,
        { status: 'published' },
        { preserveScroll: true },
    );
}

function rejectMaterial(materialId: number): void {
    const reason = window.prompt('Reason for rejecting this session material:');
    if (!reason?.trim()) return;
    router.post(
        `/admin-vue/sessions/${props.session.id}/materials/${materialId}/review`,
        { status: 'rejected', review_notes: reason.trim() },
        { preserveScroll: true },
    );
}

function deleteContent(materialId: number): void {
    if (!window.confirm('Remove this session document?')) return;
    router.delete(`/admin-vue/sessions/${props.session.id}/materials/${materialId}`, {
        preserveScroll: true,
    });
}

function verifyAttendance(id: number) {
    router.post(`/admin-vue/attendance/${id}/verify`, {}, { preserveScroll: true });
}
function correctionAttendance(id: number) {
    router.post(
        `/admin-vue/attendance/${id}/request-correction`,
        { verification_notes: 'Please review and correct this attendance submission.' },
        { preserveScroll: true },
    );
}
function approveRecording(id: number) {
    router.post(`/admin-vue/recordings/${id}/approve`, {}, { preserveScroll: true });
}
function rejectRecording(id: number) {
    const reason = window.prompt('Reason for rejection');
    if (reason?.trim()) {
        router.post(`/admin-vue/recordings/${id}/reject`, { review_notes: reason }, { preserveScroll: true });
    }
}
function unpublishRecording(id: number) {
    router.post(`/admin-vue/recordings/${id}/unpublish`, {}, { preserveScroll: true });
}
function issueCertificate(userId: number) {
    router.post(
        '/admin-vue/certificates/issue',
        {
            trainee_id: userId,
            record_type: 'session',
            record_id: props.session.id,
            completion_date: props.session.startsAt?.slice(0, 10) ?? new Date().toISOString().slice(0, 10),
        },
        { preserveScroll: true },
    );
}
function runSessionAction(action: 'publish' | 'close-registration' | 'complete' | 'reconcile-seats'): void {
    router.post(`/admin-vue/sessions/${props.session.id}/${action}`, {}, { preserveScroll: true });
}
function cancelSession(): void {
    const reason = window.prompt('Cancellation reason');
    if (reason?.trim()) {
        router.post(
            `/admin-vue/sessions/${props.session.id}/cancel`,
            { cancellation_reason: reason },
            { preserveScroll: true },
        );
    }
}
function notifyParticipants(): void {
    const message = window.prompt('Session update message');
    if (message?.trim()) {
        router.post(
            `/admin-vue/sessions/${props.session.id}/notify`,
            { update_message: message },
            { preserveScroll: true },
        );
    }
}
function deleteSession(): void {
    if (!window.confirm('Delete this training session? This is only allowed when no protected records are associated.')) {
        return;
    }
    router.delete(`/admin-vue/sessions/${props.session.id}`, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout>
        <a href="/admin-vue/sessions" class="mb-4 block text-sm text-slate-600">← Back to training sessions</a>
        <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <p class="text-sm font-medium text-[#0F4C81]">{{ session.sessionCode }}</p>
                <h1 class="mt-1 text-2xl font-semibold">{{ session.title }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ session.status }} · {{ date(session.startsAt) }}</p>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <a
                    :href="session.editUrl"
                    class="inline-flex h-10 items-center rounded-md border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50"
                >
                    Edit session
                </a>
                <button
                    v-if="session.statusValue === 'draft' || session.statusValue === 'registration_closed'"
                    type="button"
                    class="h-10 rounded-md border border-[#0F4C81] px-3 text-sm text-[#0F4C81]"
                    @click="runSessionAction('publish')"
                >
                    Publish
                </button>
                <button
                    v-if="session.statusValue === 'published' || session.statusValue === 'full'"
                    type="button"
                    class="h-10 rounded-md border border-amber-300 px-3 text-sm text-amber-800"
                    @click="runSessionAction('close-registration')"
                >
                    Close registration
                </button>
                <button
                    v-if="['published', 'full', 'registration_closed', 'in_progress'].includes(session.statusValue)"
                    type="button"
                    class="h-10 rounded-md border border-emerald-300 px-3 text-sm text-emerald-800"
                    @click="runSessionAction('complete')"
                >
                    Complete
                </button>
                <button type="button" class="h-10 rounded-md border px-3 text-sm" @click="runSessionAction('reconcile-seats')">
                    Reconcile seats
                </button>
                <button
                    v-if="session.confirmedCount > 0"
                    type="button"
                    class="h-10 rounded-md border px-3 text-sm"
                    @click="notifyParticipants"
                >
                    Notify participants
                </button>
                <button
                    v-if="!['cancelled', 'completed'].includes(session.statusValue)"
                    type="button"
                    class="h-10 rounded-md border border-red-300 px-3 text-sm text-red-700"
                    @click="cancelSession"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="h-10 rounded-md border border-red-300 px-3 text-sm text-red-700"
                    @click="deleteSession"
                >
                    Delete
                </button>
            </div>
        </div>
        <div
            v-if="page.props.flash?.status"
            role="status"
            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900"
        >
            {{ page.props.flash.status }}
        </div>
        <div
            v-if="page.props.flash?.error || pageErrors.length > 0"
            role="alert"
            class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
        >
            <p v-if="page.props.flash?.error" class="font-medium">{{ page.props.flash.error }}</p>
            <ul v-if="pageErrors.length > 0" class="list-disc space-y-0.5 pl-4">
                <li v-for="err in pageErrors" :key="err">{{ err }}</li>
            </ul>
        </div>
        <div class="mb-5 flex flex-wrap gap-1 border-b border-slate-200">
            <button
                v-for="tab in tabs"
                :key="tab"
                type="button"
                :class="[
                    'border-b-2 px-3 py-2 text-sm',
                    activeTab === tab ? 'border-[#0F4C81] text-[#0F4C81]' : 'border-transparent text-slate-500',
                ]"
                @click="activeTab = tab"
            >
                {{ tab }}
            </button>
        </div>

        <Card v-if="activeTab === 'Overview'">
            <CardContent class="grid gap-5 p-5 md:grid-cols-2">
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Description</h2>
                    <p class="mt-2 text-sm">{{ session.description || 'No description' }}</p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Schedule</h2>
                    <p class="mt-2 text-sm">{{ date(session.startsAt) }} – {{ date(session.endsAt) }}</p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Venue</h2>
                    <p class="mt-2 text-sm">
                        {{ [session.venueName, session.venueAddress, session.venueCity, session.venueState].filter(Boolean).join(', ') || 'Not set' }}
                    </p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Assigned Instructor(s)</h2>
                    <p class="mt-2 text-sm">{{ session.instructors.map((i) => i.name).join(', ') || 'Unassigned' }}</p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Capacity</h2>
                    <p class="mt-2 text-sm">
                        {{ session.confirmedCount }} confirmed · {{ session.reservedCount }} reserved · {{ session.capacity }} total
                    </p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Registration Price</h2>
                    <p class="mt-2 font-mono text-sm">{{ app.currency }} {{ session.price }}</p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Registration Window</h2>
                    <p class="mt-2 text-sm">{{ date(session.registrationOpensAt) }} – {{ date(session.registrationClosesAt) }}</p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Session Materials &amp; Recordings</h2>
                    <p class="mt-2 text-sm">{{ materials.length }} teaching document(s) · {{ recordings.length }} recording(s)</p>
                </div>
            </CardContent>
        </Card>

        <Card v-if="activeTab === 'Participants'">
            <CardContent class="p-4">
                <form class="mb-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="filterParticipants">
                    <input
                        v-model="participantFilters.q"
                        class="h-10 flex-1 rounded-md border px-3 text-sm"
                        placeholder="Search participant or booking reference"
                    />
                    <select v-model="participantFilters.booking_status" class="h-10 rounded-md border px-3 text-sm">
                        <option value="">All booking statuses</option>
                        <option v-for="status in participantStatuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                    <button class="h-10 rounded-md bg-slate-900 px-4 text-sm text-white">Apply</button>
                </form>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-3">Participant</th>
                                <th class="px-3 py-3">Booking</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Payment</th>
                                <th class="px-3 py-3">Attendance</th>
                                <th class="px-3 py-3">Registered</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="participant in participants.data" :key="participant.id">
                                <td class="px-3 py-3">
                                    <p class="font-medium">{{ participant.participant.name }}</p>
                                    <p class="text-xs text-slate-500">{{ participant.participant.email }}</p>
                                </td>
                                <td class="px-3 py-3 font-mono text-xs">{{ participant.reference }}</td>
                                <td class="px-3 py-3">{{ participant.bookingStatus }}</td>
                                <td class="px-3 py-3">{{ participant.paymentStatus || '—' }}</td>
                                <td class="px-3 py-3">
                                    {{ participant.attendanceStatus || '—' }}
                                    <p v-if="participant.attendanceReviewStatus" class="text-xs text-slate-500">
                                        {{ participant.attendanceReviewStatus }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 text-slate-600">{{ date(participant.registeredAt) }}</td>
                                <td class="px-3 py-3">
                                    <a :href="participant.detailUrl" class="text-xs font-medium text-[#0F4C81]">View details</a>
                                </td>
                            </tr>
                            <tr v-if="!participants.data.length">
                                <td colspan="7" class="px-3 py-12 text-center text-slate-500">No participants found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card v-if="activeTab === 'Session Materials & Recordings'">
            <CardContent class="grid gap-6 p-5">
                <section class="rounded-lg border border-slate-200 p-5">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Upload session recording</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Upload a session video recording for verified-present attendees.
                        </p>
                    </div>
                    <div class="mt-4 grid gap-3">
                        <label class="text-sm text-slate-700">
                            Recording title
                            <input
                                v-model="recordingUpload.title"
                                class="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm"
                                placeholder="Recording title"
                            />
                        </label>
                        <label class="text-sm text-slate-700">
                            Video file
                            <input
                                :key="recordingFileKey"
                                name="file"
                                type="file"
                                accept=".mp4,.mov,.mkv,.webm"
                                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                @change="onFile($event, recordingUpload)"
                            />
                        </label>
                        <label class="text-sm text-slate-700">
                            Description
                            <textarea
                                v-model="recordingUpload.description"
                                class="mt-1 min-h-20 w-full rounded-md border border-slate-300 p-3 text-sm"
                                placeholder="Optional recording description"
                            />
                        </label>
                        <p v-if="recordingError" class="text-sm text-red-700">{{ recordingError }}</p>
                        <button
                            type="button"
                            :disabled="recordingBusy || !recordingUpload.file || !recordingUpload.title.trim()"
                            class="h-10 rounded-md bg-slate-900 px-4 text-sm font-medium text-white disabled:opacity-50"
                            @click="uploadRecording"
                        >
                            {{ recordingBusy ? 'Uploading…' : 'Upload recording' }}
                        </button>
                    </div>
                </section>

                <section>
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">Session teaching materials ({{ materials.length }})</h3>
                        <p class="text-xs text-slate-500">Uploaded by assigned instructors for administrator review and publication.</p>
                    </div>
                    <div v-if="materials.length" class="mt-3 divide-y rounded-lg border border-slate-200">
                        <div
                            v-for="material in materials"
                            :key="material.id"
                            class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-slate-900">{{ material.title }}</p>
                                    <span class="rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-600">
                                        {{ material.type || 'Document' }}
                                    </span>
                                    <span class="rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700">
                                        {{ material.status }}
                                    </span>
                                </div>
                                <p v-if="material.description" class="mt-0.5 text-xs text-slate-600">{{ material.description }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ material.fileName || 'File' }}
                                    <template v-if="material.fileSizeLabel"> · {{ material.fileSizeLabel }}</template>
                                    <template v-if="material.uploadedBy"> · Uploaded by {{ material.uploadedBy }}</template>
                                </p>
                                <p v-if="material.reviewNotes" class="mt-1 text-xs text-amber-800">
                                    Review note: {{ material.reviewNotes }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    v-if="material.downloadUrl"
                                    :href="material.downloadUrl"
                                    class="inline-flex items-center gap-1 rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    <Download :size="14" />
                                    Download
                                </a>
                                <button
                                    v-if="material.status !== 'published'"
                                    type="button"
                                    class="rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white"
                                    @click="approveMaterial(material.id)"
                                >
                                    Approve &amp; Publish
                                </button>
                                <button
                                    v-if="material.status !== 'rejected'"
                                    type="button"
                                    class="rounded-md border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-800"
                                    @click="rejectMaterial(material.id)"
                                >
                                    Reject
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700"
                                    @click="deleteContent(material.id)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-3 rounded-lg border border-slate-200 p-6 text-center text-sm text-slate-500">
                        No session teaching materials uploaded yet.
                    </p>
                </section>

                <section class="lg:col-span-2">
                    <h3 class="text-sm font-semibold text-slate-900">Session recordings ({{ recordings.length }})</h3>
                    <div v-if="recordings.length" class="mt-3 grid gap-3">
                        <div
                            v-for="recording in recordings"
                            :key="recording.id"
                            class="flex flex-col gap-3 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="font-medium text-slate-900">{{ recording.title }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ recording.status }} · {{ recording.submittedBy || 'Unknown submitter' }}
                                </p>
                                <p v-if="recording.reviewNotes" class="mt-1 text-xs text-amber-800">
                                    Review note: {{ recording.reviewNotes }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-if="recording.status !== 'published'"
                                    type="button"
                                    class="rounded-md bg-emerald-700 px-3 py-1.5 text-xs text-white"
                                    @click="approveRecording(recording.id)"
                                >
                                    Approve
                                </button>
                                <button
                                    v-if="recording.status === 'published'"
                                    type="button"
                                    class="rounded-md border px-3 py-1.5 text-xs"
                                    @click="unpublishRecording(recording.id)"
                                >
                                    Unpublish
                                </button>
                                <button
                                    v-if="recording.status !== 'rejected'"
                                    type="button"
                                    class="rounded-md border border-red-300 px-3 py-1.5 text-xs text-red-700"
                                    @click="rejectRecording(recording.id)"
                                >
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-3 rounded-lg border border-slate-200 p-6 text-center text-sm text-slate-500">
                        No recordings uploaded.
                    </p>
                </section>
            </CardContent>
        </Card>

        <Card v-if="activeTab === 'Attendance'">
            <CardContent class="p-4">
                <div
                    v-if="session.statusValue !== 'completed'"
                    class="mb-4 flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p>
                        Attendance can only be verified after the physical session is marked <strong>Completed</strong>.
                    </p>
                    <button
                        v-if="['published', 'full', 'registration_closed', 'in_progress'].includes(session.statusValue)"
                        type="button"
                        class="shrink-0 rounded-md border border-amber-400 bg-white px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100"
                        @click="runSessionAction('complete')"
                    >
                        Mark Session Completed
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-3">Participant</th>
                                <th class="px-3 py-3">Attendance</th>
                                <th class="px-3 py-3">Submission</th>
                                <th class="px-3 py-3">Verification</th>
                                <th class="px-3 py-3">Comments &amp; Notes</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="record in attendance" :key="record.id">
                                <td class="px-3 py-3">
                                    <p class="font-medium">{{ record.participant.name }}</p>
                                    <p class="text-xs text-slate-500">{{ record.participant.email }}</p>
                                </td>
                                <td class="px-3 py-3">{{ record.attendanceStatus || '—' }}</td>
                                <td class="px-3 py-3">
                                    <p>{{ record.reviewStatus || 'Draft' }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ record.submittedBy || '—' }} · {{ date(record.submittedAt) }}
                                    </p>
                                </td>
                                <td class="px-3 py-3">
                                    {{ record.verifiedBy || 'Not verified' }}
                                    <p v-if="record.verifiedAt" class="text-xs text-slate-500">{{ date(record.verifiedAt) }}</p>
                                </td>
                                <td class="max-w-xs space-y-1 px-3 py-3 text-xs text-slate-600">
                                    <p v-if="record.remarks">
                                        <span class="font-semibold text-slate-800">Instructor:</span> {{ record.remarks }}
                                    </p>
                                    <p v-if="record.notes">
                                        <span class="font-semibold text-slate-800">Admin:</span> {{ record.notes }}
                                    </p>
                                    <span v-if="!record.remarks && !record.notes" class="text-slate-400">—</span>
                                </td>
                                <td class="px-3 py-3">
                                    <div v-if="record.reviewStatus !== 'Verified'" class="flex gap-2">
                                        <button
                                            type="button"
                                            class="text-xs font-medium text-emerald-700"
                                            @click="verifyAttendance(record.id)"
                                        >
                                            Verify
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-medium text-amber-700"
                                            @click="correctionAttendance(record.id)"
                                        >
                                            Request correction
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!attendance.length">
                                <td colspan="6" class="px-3 py-12 text-center text-slate-500">No attendance records submitted.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card v-if="activeTab === 'Completion'">
            <CardContent class="grid gap-5 p-5 md:grid-cols-2">
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Instructor completion report</h2>
                    <p class="mt-2 text-sm">{{ completion.submitted ? 'Submitted' : 'Not submitted' }}</p>
                    <p v-if="completion.submittedAt" class="mt-1 text-xs text-slate-500">
                        {{ completion.submittedBy || 'Unknown' }} · {{ date(completion.submittedAt) }}
                    </p>
                    <p v-if="completion.notes" class="mt-2 text-sm">{{ completion.notes }}</p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Session completion</h2>
                    <p class="mt-2 text-sm">
                        {{ completion.status === 'completed' ? 'Approved completion' : 'Pending approval' }}
                    </p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Eligible participants</h2>
                    <p class="mt-2 text-sm">{{ completion.eligibleParticipantCount }} verified-present attendees</p>
                </div>
                <div>
                    <h2 class="text-sm font-medium text-slate-500">Certificates</h2>
                    <p class="mt-2 text-sm">{{ completion.issuedCertificateCount }} issued for this session</p>
                    <div v-if="completion.eligibleParticipants?.length" class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="ep in completion.eligibleParticipants"
                            :key="ep.userId"
                            type="button"
                            :disabled="ep.hasCertificate"
                            class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-50"
                            @click="issueCertificate(ep.userId)"
                        >
                            {{ ep.hasCertificate ? `Certificate issued (${ep.name})` : `Issue certificate for ${ep.name}` }}
                        </button>
                    </div>
                    <div v-else class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="userId in completion.eligibleUserIds"
                            :key="userId"
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-xs"
                            @click="issueCertificate(userId)"
                        >
                            Issue certificate
                        </button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AdminLayout>
</template>
