<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, UploadCloud, Video } from 'lucide-vue-next';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorRecordingsProps } from '@/types/etms';

const props = defineProps<InstructorRecordingsProps>();
const fileInputKey = ref(0);

const form = useForm({
    title: '',
    description: '',
    video_file: null as File | null,
    provider_video_id: '',
    duration_seconds: 3600,
});

function onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.video_file = input.files?.[0] ?? null;
}

function submitRecording(): void {
    form.post(props.submitUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('title', 'description', 'video_file', 'provider_video_id');
            fileInputKey.value++;
        },
    });
}

function statusTone(status: string): 'success' | 'warning' | 'danger' | 'neutral' {
    const s = status.toLowerCase();
    if (['published', 'approved', 'ready', 'completed'].includes(s)) return 'success';
    if (['pending', 'pending_review', 'queued', 'processing'].includes(s)) return 'warning';
    if (['rejected', 'failed'].includes(s)) return 'danger';
    return 'neutral';
}
</script>

<template>
    <Head :title="`Recordings — ${session.sessionCode} — Ecohydrotech Training`" />

    <InstructorLayout :breadcrumb="`${session.sessionCode} / Recordings`">
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <Link
                    :href="session.showUrl"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted transition hover:text-ink"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Back to {{ session.sessionCode }}</span>
                </Link>
                <h1 class="type-page-title mt-1.5 text-ink">Session Recordings</h1>
                <p class="mt-1 text-sm text-muted">
                    {{ session.sessionCode }} · {{ session.title }} · Access policy:
                    <strong class="font-medium text-ink">{{ session.recordingAccessPolicyLabel }}</strong>
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left 7 cols: Submitted Recordings -->
                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card lg:col-span-7">
                    <div class="border-b border-line/80 px-5 py-4">
                        <h2 class="text-base font-semibold text-ink">
                            Submitted recordings ({{ recordings.length }})
                        </h2>
                        <p class="mt-0.5 text-xs text-muted">
                            Recordings are transcoded into encrypted HLS streams and reviewed by an administrator before publication.
                        </p>
                    </div>

                    <div v-if="recordings.length === 0" class="px-5 py-12 text-center">
                        <Video class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                        <p class="mt-2.5 text-sm font-medium text-ink">No recordings submitted yet</p>
                        <p class="mt-0.5 text-xs text-muted">
                            Upload a session lecture or workshop recording using the form.
                        </p>
                    </div>

                    <div v-else class="divide-y divide-line/70">
                        <div v-for="rec in recordings" :key="rec.id" class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="text-sm font-semibold text-ink">{{ rec.title }}</h3>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <StatusBadge
                                        :tone="statusTone(rec.encodingStatus)"
                                        :label="rec.encodingStatusLabel"
                                    />
                                    <StatusBadge
                                        :tone="statusTone(rec.status)"
                                        :label="rec.statusLabel"
                                    />
                                </div>
                            </div>

                            <p v-if="rec.description" class="mt-1 text-xs text-muted">
                                {{ rec.description }}
                            </p>

                            <p v-if="rec.reviewNotes" class="mt-1.5 text-xs font-medium text-warning-700">
                                Review note: {{ rec.reviewNotes }}
                            </p>

                            <div class="tabular-nums-mono mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-muted">
                                <span v-if="rec.originalFilename">{{ rec.originalFilename }}</span>
                                <span v-if="rec.durationLabel">{{ rec.durationLabel }}</span>
                                <span v-if="rec.submittedAtLabel">Submitted {{ rec.submittedAtLabel }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Right 5 cols: Upload Form -->
                <section class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card lg:col-span-5">
                    <div class="border-b border-line/80 pb-3.5">
                        <h2 class="text-base font-semibold text-ink">Upload recording</h2>
                        <p class="mt-0.5 text-xs text-muted">
                            MP4, MOV, MKV, or WebM video files for automated HLS transcoding.
                        </p>
                    </div>

                    <form class="mt-4 space-y-4" @submit.prevent="submitRecording">
                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Recording title</span>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="e.g., Part 1: Hydrology Calibration Lecture"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                            <span v-if="form.errors.title" class="text-danger-600">
                                {{ form.errors.title }}
                            </span>
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Video file</span>
                            <input
                                :key="fileInputKey"
                                type="file"
                                required
                                accept="video/mp4,video/quicktime,video/x-matroska,video/webm"
                                class="block w-full rounded-xl border border-line bg-surface px-3 py-2 text-xs text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-sand-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-navy-950"
                                @change="onFileSelected"
                            />
                            <span v-if="form.errors.video_file" class="text-danger-600">
                                {{ form.errors.video_file }}
                            </span>
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Notes (optional)</span>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Key timestamps, topics covered, or lab steps..."
                                class="rounded-xl border border-line bg-surface p-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <div v-if="form.progress" class="space-y-1">
                            <div class="flex justify-between text-xs text-muted">
                                <span>Uploading video…</span>
                                <span class="tabular font-medium text-ink">{{ form.progress.percentage }}%</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-sand-100">
                                <div
                                    class="h-full bg-navy-950 transition-all duration-150"
                                    :style="{ width: `${form.progress.percentage}%` }"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing || !form.video_file || !form.title.trim()"
                            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-45"
                        >
                            <UploadCloud class="size-4" aria-hidden="true" />
                            <span>{{ form.processing ? 'Uploading…' : 'Upload & submit for review' }}</span>
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </InstructorLayout>
</template>
