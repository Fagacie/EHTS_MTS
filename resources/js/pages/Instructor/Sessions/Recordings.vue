<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    FileVideo,
    HardDrive,
    Info,
    UploadCloud,
    Video,
} from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorRecordingsProps } from '@/types/etms';

const props = defineProps<InstructorRecordingsProps>();

const form = useForm({
    title: '',
    description: '',
    video_file: null as File | null,
    provider_video_id: '',
    duration_seconds: 3600,
});

function onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        form.video_file = input.files[0];
    } else {
        form.video_file = null;
    }
}

function submitRecording() {
    form.post(props.submitUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('title', 'description', 'video_file', 'provider_video_id');
        },
    });
}
</script>

<template>
    <Head :title="`Recordings — ${session.sessionCode} — Ecohydrotech Training`" />

    <InstructorLayout
        :breadcrumb="`${session.sessionCode} / Recordings`"
        :title="`${session.sessionCode} — ${session.title}`"
        :subtitle="`Recording Access Policy: ${session.recordingAccessPolicyLabel} (INS-06, FR-08, D-017)`"
    >
        <div class="space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-[#F1EDFB] px-2.5 py-1 font-mono text-xs font-bold text-[#5942B5]">
                            {{ session.sessionCode }}
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#7057D9]">
                            Session Recording Upload (INS-06, FR-08, D-017)
                        </span>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#292536]">
                        {{ session.title }}
                    </h1>
                    <p class="mt-1 text-sm text-[#625D6D]">
                        Recording Access Policy: <strong class="text-[#292536]">{{ session.recordingAccessPolicyLabel }}</strong>
                    </p>
                </div>

                <Button as-child variant="outline" size="sm" class="h-9 rounded-xl border-[#E5E1EB] px-3.5 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]">
                    <Link :href="session.showUrl">
                        <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />
                        Back to Session
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 lg:grid-cols-12">
                <!-- Submitted Recordings Column (col-span-7) -->
                <div class="space-y-6 lg:col-span-7">
                    <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#E5E1EB] pb-4">
                            <div>
                                <h2 class="text-base font-bold text-[#292536]">
                                    Submitted Session Recordings ({{ recordings.length }})
                                </h2>
                                <p class="mt-0.5 text-xs text-[#625D6D]">
                                    Recordings are stored privately on company server, transcoded via FFmpeg HLS, and held in pending review until approved.
                                </p>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="recordings.length === 0" class="py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1EDFB] text-[#7057D9]">
                                <Video class="h-6 w-6" />
                            </div>
                            <h3 class="mt-3 text-base font-bold text-[#292536]">No recordings submitted yet</h3>
                            <p class="mx-auto mt-1 max-w-sm text-xs text-[#625D6D]">
                                Use the form to upload session lecture or workshop video files.
                            </p>
                        </div>

                        <!-- List of Recordings -->
                        <div v-else class="divide-y divide-[#E5E1EB]">
                            <div
                                v-for="rec in recordings"
                                :key="rec.id"
                                class="py-4 first:pt-4 last:pb-0"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="text-sm font-bold text-[#292536]">{{ rec.title }}</h3>
                                    <div class="flex items-center gap-2">
                                        <PortalStatusBadge status="info" :label="rec.encodingStatusLabel" />
                                        <PortalStatusBadge :status="rec.status" :label="rec.statusLabel" />
                                    </div>
                                </div>

                                <p v-if="rec.description" class="mt-1 text-xs text-[#625D6D]">
                                    {{ rec.description }}
                                </p>

                                <p v-if="rec.reviewNotes" class="mt-1 text-xs font-medium text-amber-800">
                                    Administrator review note: {{ rec.reviewNotes }}
                                </p>

                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 font-mono text-[11px] text-[#625D6D]">
                                    <span v-if="rec.originalFilename">File: {{ rec.originalFilename }}</span>
                                    <span v-if="rec.durationLabel">Duration: {{ rec.durationLabel }}</span>
                                    <span v-if="rec.submittedAtLabel">Submitted: {{ rec.submittedAtLabel }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Form Column (col-span-5) -->
                <div class="space-y-6 lg:col-span-5">
                    <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                        <div class="border-b border-[#E5E1EB] pb-4">
                            <h2 class="text-base font-bold text-[#292536]">Upload Session Recording</h2>
                            <p class="mt-0.5 text-xs text-[#625D6D]">
                                Upload an MP4 / MOV / MKV / WebM video file for automated FFmpeg HLS transcoding.
                            </p>
                        </div>

                        <form @submit.prevent="submitRecording" class="mt-4 space-y-4">
                            <div>
                                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                                    Recording Title <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="title"
                                    type="text"
                                    v-model="form.title"
                                    required
                                    placeholder="Part 1: Morning Hydrology Lecture & Calibration"
                                    class="mt-1 w-full rounded-xl border border-[#E5E1EB] bg-white px-3.5 py-2 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                                />
                                <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div>
                                <label for="video_file" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                                    Video File (MP4 / MOV / MKV / WebM) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="video_file"
                                    type="file"
                                    required
                                    accept="video/mp4,video/quicktime,video/x-matroska,video/webm"
                                    @change="onFileSelected"
                                    class="mt-1 block w-full rounded-xl border border-[#E5E1EB] bg-white px-3 py-2 text-xs text-[#292536] file:mr-3 file:rounded-lg file:border-0 file:bg-[#F1EDFB] file:px-3 file:py-1 file:text-xs file:font-semibold file:text-[#5942B5] hover:file:bg-[#EAE5F8]"
                                />
                                <p v-if="form.errors.video_file" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.video_file }}
                                </p>
                            </div>

                            <div>
                                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                                    Recording Notes (Optional)
                                </label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Key timestamps, topics covered, or lab steps demonstrated..."
                                    class="mt-1 w-full rounded-xl border border-[#E5E1EB] bg-white p-3 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                                />
                            </div>

                            <!-- Upload Progress Bar -->
                            <div v-if="form.progress" class="space-y-1">
                                <div class="flex justify-between text-xs text-[#625D6D]">
                                    <span>Uploading video...</span>
                                    <span>{{ form.progress.percentage }}%</span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-[#E5E1EB] overflow-hidden">
                                    <div
                                        class="h-full bg-[#7057D9] transition-all duration-150"
                                        :style="{ width: `${form.progress.percentage}%` }"
                                    />
                                </div>
                            </div>

                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full rounded-xl bg-[#7057D9] py-2.5 text-xs font-bold text-white shadow-none hover:bg-[#5942B5] disabled:cursor-not-allowed disabled:bg-[#E5E1EB] disabled:text-[#625D6D]"
                            >
                                <UploadCloud class="mr-1.5 h-4 w-4" />
                                {{ form.processing ? 'Uploading & Transcoding...' : 'Upload & Submit for Approval' }}
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </InstructorLayout>
</template>
