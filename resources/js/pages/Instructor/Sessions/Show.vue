<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Clock,
    Download,
    FileText,
    MapPin,
    Plus,
    Trash2,
    UploadCloud,
    Users,
    Video,
    X,
} from 'lucide-vue-next';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import UserAvatar from '@/components/ds/UserAvatar.vue';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorSessionShowProps } from '@/types/etms';

const props = defineProps<InstructorSessionShowProps>();

type SessionTab = 'participants' | 'materials' | 'completion';
const activeTab = ref<SessionTab>('participants');

const uploadModalOpen = ref(false);
const materialFileKey = ref(0);
const deletingMaterialUrl = ref<string | null>(null);
const deletingBusy = ref(false);

const materialForm = useForm({
    title: '',
    description: '',
    material_type: 'pdf',
    material_file: null as File | null,
});

const fillPercent = computed(() => {
    if (props.session.capacity <= 0) return 0;
    return Math.min(100, Math.round((props.roster.length / props.session.capacity) * 100));
});

const presentCount = computed(
    () => props.roster.filter((p) => p.attendanceStatusValue === 'present').length,
);

const materialsCount = computed(() => props.materials?.length ?? 0);

function onMaterialFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    materialForm.material_file = input.files?.[0] ?? null;
}

function submitMaterial(): void {
    if (!props.submitMaterialUrl || !materialForm.material_file) return;

    materialForm.post(props.submitMaterialUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            materialForm.reset('title', 'description', 'material_file');
            materialFileKey.value++;
            uploadModalOpen.value = false;
            activeTab.value = 'materials';
        },
    });
}

function confirmDeleteMaterial(): void {
    if (!deletingMaterialUrl.value) return;
    deletingBusy.value = true;
    router.delete(deletingMaterialUrl.value, {
        preserveScroll: true,
        onFinish: () => {
            deletingBusy.value = false;
            deletingMaterialUrl.value = null;
        },
    });
}

function toneFor(status: string): 'success' | 'warning' | 'danger' | 'neutral' {
    const s = status.toLowerCase();
    if (['published', 'completed', 'approved', 'present', 'verified'].includes(s)) return 'success';
    if (['pending', 'pending_review', 'draft', 'full', 'registration_closed', 'submitted'].includes(s)) {
        return 'warning';
    }
    if (['cancelled', 'rejected', 'absent'].includes(s)) return 'danger';
    return 'neutral';
}
</script>

<template>
    <Head :title="`${session.sessionCode} — ${session.title} — Ecohydrotech Training`" />

    <InstructorLayout :breadcrumb="session.sessionCode">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <Link
                        href="/instructor/sessions"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-muted transition hover:text-ink"
                    >
                        <ArrowLeft class="size-3.5" aria-hidden="true" />
                        <span>Assigned Sessions</span>
                    </Link>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="tabular-nums-mono rounded-md bg-sand-100 px-2 py-0.5 text-xs font-semibold text-navy-950">
                            {{ session.sessionCode }}
                        </span>
                        <StatusBadge :tone="toneFor(session.statusValue)" :label="session.statusLabel" />
                        <span class="text-xs font-medium text-muted">{{ session.roleLabel }}</span>
                    </div>

                    <h1 class="type-page-title mt-1.5 text-ink">{{ session.title }}</h1>
                    <p v-if="session.moduleTitle" class="mt-0.5 text-sm text-muted">
                        {{ session.moduleCode }} · {{ session.moduleTitle }}
                    </p>
                </div>

                <!-- Primary Session Actions -->
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <Link
                        :href="session.recordingsUrl"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-line bg-surface px-3.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                    >
                        <Video class="size-3.5 text-muted" aria-hidden="true" />
                        <span>Recordings ({{ session.recordingsCount }})</span>
                    </Link>
                    <Link
                        :href="session.completionUrl"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-line bg-surface px-3.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                    >
                        <FileText class="size-3.5 text-muted" aria-hidden="true" />
                        <span>Completion</span>
                    </Link>
                    <Link
                        :href="session.attendanceUrl"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-navy-950 px-4 text-xs font-semibold text-white transition hover:bg-navy-900"
                    >
                        <CheckCircle2 class="size-3.5" aria-hidden="true" />
                        <span>Take attendance</span>
                    </Link>
                </div>
            </div>

            <!-- Compact Summary Bar -->
            <section class="grid grid-cols-2 divide-y divide-line/70 overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card sm:grid-cols-4 sm:divide-x sm:divide-y-0">
                <div class="p-4">
                    <p class="text-xs text-muted">Enrolled seats</p>
                    <p class="tabular mt-1 text-xl font-bold text-ink">
                        {{ roster.length }}
                        <span class="text-sm font-normal text-muted">/ {{ session.capacity }}</span>
                    </p>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-sand-100">
                        <div class="h-full rounded-full bg-navy-950" :style="{ width: `${fillPercent}%` }" />
                    </div>
                </div>

                <div class="p-4">
                    <p class="text-xs text-muted">Marked present</p>
                    <p class="tabular mt-1 text-xl font-bold text-ink">{{ presentCount }}</p>
                    <p class="mt-1 text-[11px] text-muted">of {{ roster.length }} confirmed</p>
                </div>

                <div class="p-4">
                    <p class="text-xs text-muted">Teaching materials</p>
                    <p class="tabular mt-1 text-xl font-bold text-ink">{{ materialsCount }}</p>
                    <p class="mt-1 text-[11px] text-muted">Slides &amp; handouts</p>
                </div>

                <div class="p-4">
                    <p class="text-xs text-muted">Recordings</p>
                    <p class="tabular mt-1 text-xl font-bold text-ink">{{ session.recordingsCount }}</p>
                    <p class="mt-1 text-[11px] text-muted">{{ session.recordingAccessPolicyLabel }}</p>
                </div>
            </section>

            <!-- Main Workspace Split -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left 8 cols: Tabbed Content -->
                <div class="space-y-4 lg:col-span-8">
                    <!-- Workspace Tabs -->
                    <nav class="flex items-center gap-1 border-b border-line pb-px" aria-label="Session sections">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 border-b-2 px-3.5 py-2.5 text-sm font-medium transition"
                            :class="
                                activeTab === 'participants'
                                    ? 'border-navy-950 text-navy-950'
                                    : 'border-transparent text-muted hover:border-line hover:text-ink'
                            "
                            @click="activeTab = 'participants'"
                        >
                            <span>Participants</span>
                            <span
                                class="tabular rounded-full px-1.5 py-0.5 text-[11px] font-semibold"
                                :class="
                                    activeTab === 'participants'
                                        ? 'bg-navy-950 text-white'
                                        : 'bg-sand-100 text-muted'
                                "
                            >
                                {{ roster.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 border-b-2 px-3.5 py-2.5 text-sm font-medium transition"
                            :class="
                                activeTab === 'materials'
                                    ? 'border-navy-950 text-navy-950'
                                    : 'border-transparent text-muted hover:border-line hover:text-ink'
                            "
                            @click="activeTab = 'materials'"
                        >
                            <span>Materials</span>
                            <span
                                class="tabular rounded-full px-1.5 py-0.5 text-[11px] font-semibold"
                                :class="
                                    activeTab === 'materials'
                                        ? 'bg-navy-950 text-white'
                                        : 'bg-sand-100 text-muted'
                                "
                            >
                                {{ materialsCount }}
                            </span>
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 border-b-2 px-3.5 py-2.5 text-sm font-medium transition"
                            :class="
                                activeTab === 'completion'
                                    ? 'border-navy-950 text-navy-950'
                                    : 'border-transparent text-muted hover:border-line hover:text-ink'
                            "
                            @click="activeTab = 'completion'"
                        >
                            <span>Completion notes</span>
                        </button>
                    </nav>

                    <!-- TAB 1: PARTICIPANTS -->
                    <section
                        v-if="activeTab === 'participants'"
                        class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card"
                    >
                        <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                            <div>
                                <h2 class="text-base font-semibold text-ink">Confirmed participants</h2>
                                <p class="mt-0.5 text-xs text-muted">
                                    Trainees with verified bookings for this session.
                                </p>
                            </div>
                            <Link
                                :href="session.attendanceUrl"
                                class="text-xs font-medium text-primary-600 hover:underline"
                            >
                                Manage attendance
                            </Link>
                        </div>

                        <div v-if="roster.length === 0" class="px-5 py-12 text-center">
                            <Users class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                            <p class="mt-2 text-sm font-medium text-ink">No confirmed participants yet</p>
                            <p class="mt-0.5 text-xs text-muted">
                                Confirmed trainees will appear here automatically.
                            </p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full border-collapse text-left text-sm">
                                <thead>
                                    <tr class="border-b border-line/80 bg-sand-50/70 text-xs font-semibold text-muted">
                                        <th class="px-5 py-3">Participant</th>
                                        <th class="px-4 py-3">Booking Ref</th>
                                        <th class="px-4 py-3">Attendance</th>
                                        <th class="px-5 py-3">Verification</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/65">
                                    <tr
                                        v-for="booking in roster"
                                        :key="booking.bookingId"
                                        class="hover:bg-sand-50/60"
                                    >
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-3">
                                                <UserAvatar :name="booking.participantName" size="sm" />
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium text-ink">
                                                        {{ booking.participantName }}
                                                    </p>
                                                    <p class="truncate text-xs text-muted">
                                                        {{ booking.organisation }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="tabular-nums-mono px-4 py-3.5 text-xs font-semibold text-ink">
                                            {{ booking.bookingReference }}
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <StatusBadge
                                                :tone="toneFor(booking.attendanceStatusValue)"
                                                :label="booking.attendanceStatusLabel"
                                            />
                                        </td>
                                        <td class="px-5 py-3.5 text-xs font-medium text-muted">
                                            {{ booking.reviewStatusLabel }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- TAB 2: TEACHING MATERIALS -->
                    <section
                        v-if="activeTab === 'materials'"
                        class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card"
                    >
                        <div class="flex items-center justify-between gap-3 border-b border-line/80 px-5 py-4">
                            <div>
                                <h2 class="text-base font-semibold text-ink">Teaching materials</h2>
                                <p class="mt-0.5 text-xs text-muted">
                                    Slides, worksheets, and manuals submitted for attendee access.
                                </p>
                            </div>
                            <button
                                v-if="submitMaterialUrl && session.statusValue !== 'cancelled'"
                                type="button"
                                class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-navy-950 px-3 text-xs font-semibold text-white transition hover:bg-navy-900"
                                @click="uploadModalOpen = true"
                            >
                                <Plus class="size-3.5" aria-hidden="true" />
                                <span>Upload material</span>
                            </button>
                        </div>

                        <div v-if="!materials || materials.length === 0" class="px-5 py-12 text-center">
                            <UploadCloud class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                            <p class="mt-2 text-sm font-medium text-ink">No materials uploaded yet</p>
                            <p class="mt-0.5 text-xs text-muted">
                                Upload slides or worksheets for administrator approval and trainee download.
                            </p>
                            <button
                                v-if="submitMaterialUrl && session.statusValue !== 'cancelled'"
                                type="button"
                                class="mt-4 inline-flex h-9 items-center gap-1.5 rounded-xl bg-navy-950 px-4 text-xs font-semibold text-white transition hover:bg-navy-900"
                                @click="uploadModalOpen = true"
                            >
                                <UploadCloud class="size-3.5" aria-hidden="true" />
                                <span>Upload first material</span>
                            </button>
                        </div>

                        <div v-else class="divide-y divide-line/70">
                            <div
                                v-for="mat in materials"
                                :key="mat.id"
                                class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-ink">{{ mat.title }}</span>
                                        <span class="rounded-md bg-sand-100 px-2 py-0.5 text-[11px] font-medium text-muted">
                                            {{ mat.typeLabel }}
                                        </span>
                                        <StatusBadge :tone="toneFor(mat.status)" :label="mat.statusLabel" />
                                    </div>
                                    <p v-if="mat.description" class="mt-1 text-xs text-muted">
                                        {{ mat.description }}
                                    </p>
                                    <p class="tabular-nums-mono mt-1 text-[11px] text-muted">
                                        {{ mat.originalFilename }} · {{ mat.fileSizeLabel }}
                                    </p>
                                    <p v-if="mat.reviewNotes" class="mt-1 text-xs font-medium text-warning-700">
                                        Review note: {{ mat.reviewNotes }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-1.5">
                                    <a
                                        v-if="mat.downloadUrl"
                                        :href="mat.downloadUrl"
                                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-surface px-2.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                                    >
                                        <Download class="size-3.5 text-muted" aria-hidden="true" />
                                        <span>Download</span>
                                    </a>
                                    <button
                                        v-if="mat.canDelete"
                                        type="button"
                                        aria-label="Remove material"
                                        class="inline-flex size-8 items-center justify-center rounded-lg border border-line bg-surface text-muted transition hover:border-danger-200 hover:bg-danger-50 hover:text-danger-600"
                                        @click="deletingMaterialUrl = mat.deleteUrl"
                                    >
                                        <Trash2 class="size-3.5" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- TAB 3: COMPLETION NOTES -->
                    <section
                        v-if="activeTab === 'completion'"
                        class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card"
                    >
                        <div class="flex items-center justify-between gap-3 border-b border-line/80 pb-3.5">
                            <div>
                                <h2 class="text-base font-semibold text-ink">Completion notes</h2>
                                <p class="mt-0.5 text-xs text-muted">
                                    Delivery summary and operational remarks for administrators.
                                </p>
                            </div>
                            <Link
                                :href="session.completionUrl"
                                class="inline-flex h-8 items-center rounded-lg bg-navy-950 px-3 text-xs font-semibold text-white transition hover:bg-navy-900"
                            >
                                {{ session.completionNotes ? 'Edit notes' : 'Write report' }}
                            </Link>
                        </div>

                        <div v-if="session.completionNotes" class="mt-4 space-y-2">
                            <p class="whitespace-pre-line text-sm leading-relaxed text-ink">
                                {{ session.completionNotes }}
                            </p>
                            <p v-if="session.completedAtLabel" class="text-xs text-muted">
                                Recorded {{ session.completedAtLabel }} MYT
                            </p>
                        </div>
                        <div v-else class="py-10 text-center">
                            <FileText class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                            <p class="mt-2 text-sm font-medium text-ink">No completion notes yet</p>
                            <p class="mt-0.5 text-xs text-muted">
                                Record syllabus coverage and practical lab outcomes after delivering this session.
                            </p>
                        </div>
                    </section>
                </div>

                <!-- Right 4 cols: Logistics Sidebar -->
                <aside class="space-y-5 lg:col-span-4">
                    <section class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <h2 class="border-b border-line/80 pb-3 text-sm font-semibold text-ink">
                            Schedule &amp; venue
                        </h2>
                        <dl class="mt-3 space-y-3.5 text-sm">
                            <div class="flex items-start gap-2.5">
                                <CalendarDays class="mt-0.5 size-4 shrink-0 text-muted" aria-hidden="true" />
                                <div>
                                    <dt class="text-xs text-muted">Date &amp; time (MYT)</dt>
                                    <dd class="font-medium text-ink">{{ session.scheduleLabel }}</dd>
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5">
                                <Clock class="mt-0.5 size-4 shrink-0 text-muted" aria-hidden="true" />
                                <div>
                                    <dt class="text-xs text-muted">Duration</dt>
                                    <dd class="font-medium text-ink">{{ session.durationLabel }}</dd>
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5">
                                <MapPin class="mt-0.5 size-4 shrink-0 text-muted" aria-hidden="true" />
                                <div>
                                    <dt class="text-xs text-muted">Venue</dt>
                                    <dd class="font-medium text-ink">{{ session.venueName }}</dd>
                                    <dd class="mt-0.5 text-xs text-muted">
                                        {{ session.venueAddress }}, {{ session.venueCity }}, {{ session.venueState }}
                                    </dd>
                                </div>
                            </div>

                            <div class="flex items-start gap-2.5">
                                <Video class="mt-0.5 size-4 shrink-0 text-muted" aria-hidden="true" />
                                <div>
                                    <dt class="text-xs text-muted">Recording access</dt>
                                    <dd class="font-medium text-ink">{{ session.recordingAccessPolicyLabel }}</dd>
                                </div>
                            </div>
                        </dl>
                    </section>
                </aside>
            </div>
        </div>

        <!-- Upload Teaching Material Dialog -->
        <DialogRoot :open="uploadModalOpen" @update:open="uploadModalOpen = $event">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 max-h-[90vh] w-[94vw] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <DialogTitle class="text-lg font-semibold text-ink">
                                Upload teaching material
                            </DialogTitle>
                            <DialogDescription class="mt-1 text-sm text-muted">
                                Submitted files are reviewed by an administrator before release to confirmed trainees.
                            </DialogDescription>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-sand-100 hover:text-ink"
                            aria-label="Close"
                        >
                            <X class="size-4" />
                        </DialogClose>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submitMaterial">
                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Material title</span>
                            <input
                                v-model="materialForm.title"
                                type="text"
                                required
                                maxlength="200"
                                placeholder="e.g., Workshop Slides & Worked Examples"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                            <span v-if="materialForm.errors.title" class="text-danger-600">
                                {{ materialForm.errors.title }}
                            </span>
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Material type</span>
                            <select
                                v-model="materialForm.material_type"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option
                                    v-for="type in materialTypes ?? [
                                        { value: 'pdf', label: 'PDF Document' },
                                        { value: 'presentation', label: 'Presentation' },
                                        { value: 'worksheet', label: 'Worksheet' },
                                        { value: 'document', label: 'Reference Document' },
                                    ]"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>File (PDF, Office, ZIP, Image — up to 100 MB)</span>
                            <input
                                :key="materialFileKey"
                                type="file"
                                required
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.png,.jpg,.jpeg"
                                class="block w-full rounded-xl border border-line bg-surface px-3 py-2 text-xs text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-sand-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-navy-950"
                                @change="onMaterialFileSelected"
                            />
                            <span v-if="materialForm.errors.material_file" class="text-danger-600">
                                {{ materialForm.errors.material_file }}
                            </span>
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Notes (optional)</span>
                            <textarea
                                v-model="materialForm.description"
                                rows="3"
                                placeholder="Short notes for attendees or administrator review..."
                                class="rounded-xl border border-line bg-surface p-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <div class="flex justify-end gap-2 border-t border-line pt-4">
                            <button
                                type="button"
                                class="h-10 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                                @click="uploadModalOpen = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="materialForm.processing || !materialForm.material_file || !materialForm.title.trim()"
                                class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-45"
                            >
                                <UploadCloud class="size-4" aria-hidden="true" />
                                <span>{{ materialForm.processing ? 'Uploading…' : 'Upload material' }}</span>
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Delete Material Confirmation -->
        <DialogRoot
            :open="Boolean(deletingMaterialUrl)"
            @update:open="(v) => !v && (deletingMaterialUrl = null)"
        >
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 w-[92vw] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <DialogTitle class="text-lg font-semibold text-ink">Remove teaching material?</DialogTitle>
                    <DialogDescription class="mt-1 text-sm text-muted">
                        This unapproved file will be permanently removed from the session.
                    </DialogDescription>
                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="h-9 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                            @click="deletingMaterialUrl = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="deletingBusy"
                            class="h-9 rounded-xl bg-danger-600 px-4 text-sm font-semibold text-white transition hover:bg-danger-700 disabled:opacity-50"
                            @click="confirmDeleteMaterial"
                        >
                            {{ deletingBusy ? 'Removing…' : 'Remove' }}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </InstructorLayout>
</template>
