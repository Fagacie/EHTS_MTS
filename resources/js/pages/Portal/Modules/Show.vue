<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Download,
    FileText,
    Film,
    PlayCircle,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import HlsVideoPlayer from '@/components/portal/HlsVideoPlayer.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { pluralize } from '@/lib/utils';
import type { PortalModuleWorkspaceProps } from '@/types/etms';

const props = defineProps<PortalModuleWorkspaceProps>();

const { routes } = useRoutes();
const completedIds = ref<number[]>([]);

const progressStorageKey = computed(() => `etms_module_progress_${props.module.id}`);

function loadProgress(): void {
    try {
        const raw = localStorage.getItem(progressStorageKey.value);
        if (raw) {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                completedIds.value = parsed.filter((id): id is number => typeof id === 'number');
            }
        }
    } catch {
        completedIds.value = [];
    }
}

function saveProgress(): void {
    try {
        localStorage.setItem(progressStorageKey.value, JSON.stringify(completedIds.value));
    } catch {
        // Ignore storage restrictions
    }
}

function toggleLessonComplete(videoId: number): void {
    if (completedIds.value.includes(videoId)) {
        completedIds.value = completedIds.value.filter((id) => id !== videoId);
    } else {
        completedIds.value = [...completedIds.value, videoId];
    }
    saveProgress();
}

const isCurrentCompleted = computed(() =>
    props.activeVideo ? completedIds.value.includes(props.activeVideo.id) : false,
);

const currentVideoIndex = computed(() => {
    if (!props.activeVideo) return -1;
    return props.videos.findIndex((v) => v.id === props.activeVideo?.id);
});

const prevPlayableVideo = computed(() => {
    if (currentVideoIndex.value <= 0) return null;
    for (let i = currentVideoIndex.value - 1; i >= 0; i--) {
        if (props.videos[i]?.isPlayable && props.videos[i]?.watchUrl) {
            return props.videos[i];
        }
    }
    return null;
});

const nextPlayableVideo = computed(() => {
    if (currentVideoIndex.value < 0) return null;
    for (let i = currentVideoIndex.value + 1; i < props.videos.length; i++) {
        if (props.videos[i]?.isPlayable && props.videos[i]?.watchUrl) {
            return props.videos[i];
        }
    }
    return null;
});

const completedCount = computed(() =>
    props.videos.filter((v) => completedIds.value.includes(v.id)).length,
);

const progressPercent = computed(() => {
    if (props.videos.length === 0) return 0;
    return Math.round((completedCount.value / props.videos.length) * 100);
});

onMounted(loadProgress);
watch(() => props.module.id, loadProgress);
</script>

<template>
    <Head :title="module.title" />

    <PortalLayout :breadcrumb="module.title">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Back Link -->
            <div>
                <Link
                    :href="routes.portalModules"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Back to My Modules</span>
                </Link>
            </div>

            <!-- Unified Module Header Card (with Cover Banner when available & no active video) -->
            <section class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                <div
                    v-if="module.coverImageUrl && !activeVideo"
                    class="relative h-48 w-full overflow-hidden bg-navy-950 sm:h-56"
                >
                    <img
                        :src="module.coverImageUrl"
                        :alt="module.title"
                        class="h-full w-full object-cover"
                    />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-navy-950/85 via-navy-950/35 to-transparent"
                        aria-hidden="true"
                    />
                    <div class="absolute inset-x-0 bottom-0 p-6 sm:p-7">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge tone="success" dot>Active Access</StatusBadge>
                            <span class="tabular-nums-mono rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold text-white backdrop-blur-xs">
                                {{ module.code }}
                            </span>
                        </div>
                        <h1 class="type-page-title mt-2.5 !text-white">
                            {{ module.title }}
                        </h1>
                    </div>
                </div>

                <div v-else class="p-6 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <StatusBadge tone="success" dot>Active Access</StatusBadge>
                                <span class="tabular-nums-mono text-xs font-medium text-muted">
                                    {{ module.code }}
                                </span>
                                <span v-if="entitlement?.grantedAtLabel" class="tabular text-xs text-muted">
                                    &middot; Granted {{ entitlement.grantedAtLabel }}
                                </span>
                            </div>

                            <h1 class="type-page-title mt-3">
                                {{ module.title }}
                            </h1>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <span class="tabular inline-flex items-center gap-1.5 rounded-full bg-sand-50 px-3 py-1.5 text-xs font-semibold text-navy-950 ring-1 ring-line">
                                <FileText class="size-3.5 text-accent-600" aria-hidden="true" />
                                {{ materials.length }} {{ pluralize(materials.length, 'manual') }}
                            </span>
                            <span
                                v-if="videos.length > 0"
                                class="tabular inline-flex items-center gap-1.5 rounded-full bg-sand-50 px-3 py-1.5 text-xs font-semibold text-navy-950 ring-1 ring-line"
                            >
                                <PlayCircle class="size-3.5 text-primary-600" aria-hidden="true" />
                                {{ videos.length }} {{ pluralize(videos.length, 'lesson') }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2-Column Non-Tabbed Workspace -->
            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                <!-- Left Main Column: Video Player (if any), Downloadable Materials, Overview -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- HLS Video Cinema Card -->
                    <section
                        v-if="activeVideo && streamManifestUrl"
                        class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80"
                    >
                        <div class="bg-navy-950">
                            <HlsVideoPlayer
                                :src="streamManifestUrl"
                                :title="activeVideo.title"
                            />
                        </div>

                        <div class="p-5 sm:p-6">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="tabular text-xs font-semibold text-primary-600">
                                        Lesson {{ currentVideoIndex + 1 }} of {{ videos.length }} &middot; {{ activeVideo.durationLabel }}
                                    </p>
                                    <h2 class="mt-1 text-lg font-bold tracking-tight text-navy-950">
                                        {{ activeVideo.title }}
                                    </h2>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors"
                                    :class="
                                        isCurrentCompleted
                                            ? 'bg-success-50 text-success-700 ring-1 ring-inset ring-success-600/20'
                                            : 'border border-line-strong bg-white text-navy-950 hover:bg-sand-50'
                                    "
                                    @click="toggleLessonComplete(activeVideo.id)"
                                >
                                    <CheckCircle2 v-if="isCurrentCompleted" class="size-3.5 text-success-600" aria-hidden="true" />
                                    <Check v-else class="size-3.5 text-muted" aria-hidden="true" />
                                    <span>{{ isCurrentCompleted ? 'Completed' : 'Mark complete' }}</span>
                                </button>
                            </div>

                            <p v-if="activeVideo.description" class="mt-2.5 text-sm leading-relaxed text-muted">
                                {{ activeVideo.description }}
                            </p>

                            <div
                                v-if="videos.length > 1"
                                class="mt-5 flex items-center justify-between gap-3 border-t border-line pt-4"
                            >
                                <AppButton
                                    v-if="prevPlayableVideo?.watchUrl"
                                    :href="prevPlayableVideo.watchUrl"
                                    variant="outline"
                                    size="sm"
                                    pill
                                >
                                    <ChevronLeft class="size-4" aria-hidden="true" />
                                    <span>Previous</span>
                                </AppButton>
                                <span v-else class="text-xs text-muted/60">First lesson</span>

                                <AppButton
                                    v-if="nextPlayableVideo?.watchUrl"
                                    :href="nextPlayableVideo.watchUrl"
                                    variant="primary"
                                    size="sm"
                                    pill
                                >
                                    <span>Next lesson</span>
                                    <ChevronRight class="size-4" aria-hidden="true" />
                                </AppButton>
                                <span v-else class="text-xs font-medium text-success-700">Last lesson</span>
                            </div>
                        </div>
                    </section>

                    <div
                        v-else-if="videos.length > 0"
                        role="status"
                        class="flex items-start gap-3 rounded-2xl bg-primary-50 p-5 ring-1 ring-inset ring-primary-600/15"
                    >
                        <Film class="mt-0.5 size-5 shrink-0 text-primary-600" aria-hidden="true" />
                        <div>
                            <p class="text-sm font-bold text-navy-950">Video lessons are being prepared</p>
                            <p class="mt-0.5 text-xs text-muted">
                                Stream playback will be ready shortly. All downloadable manuals are available below.
                            </p>
                        </div>
                    </div>

                    <!-- Learning Materials (Directly Visible) -->
                    <section class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-bold text-navy-950">Learning materials</h2>
                            <span class="tabular rounded-full bg-sand-100 px-2.5 py-0.5 text-xs font-semibold text-navy-900">
                                {{ materials.length }} {{ pluralize(materials.length, 'file') }}
                            </span>
                        </div>

                        <ul v-if="materials.length > 0" class="mt-4 divide-y divide-neutral-100">
                            <li
                                v-for="material in materials"
                                :key="material.id"
                                class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex min-w-0 items-start gap-3.5">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-700">
                                        <FileText class="size-4.5" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-navy-950">
                                            {{ material.title }}
                                        </p>
                                        <p v-if="material.description" class="mt-0.5 text-xs text-muted">
                                            {{ material.description }}
                                        </p>
                                        <p class="tabular mt-1 text-xs text-muted">
                                            {{ material.typeLabel }} &middot; {{ material.fileSizeLabel }}
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <AppButton
                                        v-if="material.isDownloadable && material.downloadUrl"
                                        :href="material.downloadUrl"
                                        external
                                        variant="primary"
                                        size="sm"
                                        pill
                                    >
                                        <Download class="size-3.5" aria-hidden="true" />
                                        <span>Download</span>
                                    </AppButton>
                                    <StatusBadge v-else tone="neutral">Unavailable</StatusBadge>
                                </div>
                            </li>
                        </ul>

                        <p v-else class="mt-3 text-sm text-muted">
                            No downloadable materials have been published for this module yet.
                        </p>
                    </section>

                    <!-- Module Overview & Syllabus (Directly Visible) -->
                    <section
                        v-if="module.description"
                        class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <h2 class="text-base font-bold text-navy-950">Syllabus &amp; overview</h2>
                        <div class="mt-3.5 whitespace-pre-line text-sm leading-7 text-ink/90">
                            {{ module.description }}
                        </div>
                    </section>
                </div>

                <!-- Right Sticky Sidebar: Video Playlist (if any), Learning Objectives, Access Summary -->
                <aside class="space-y-5 lg:sticky lg:top-24 lg:col-span-4">
                    <!-- Lesson Playlist & Progress (when videos exist) -->
                    <div
                        v-if="videos.length > 0"
                        class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80"
                    >
                        <div class="border-b border-line bg-sand-50/70 p-4 sm:px-5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-2 text-sm font-bold text-navy-950">
                                    <PlayCircle class="size-4 text-primary-600" aria-hidden="true" />
                                    <span>Video lessons</span>
                                </span>
                                <span class="tabular text-xs font-semibold text-muted">
                                    {{ completedCount }} / {{ videos.length }} &middot; {{ progressPercent }}%
                                </span>
                            </div>
                            <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-line">
                                <div
                                    class="h-full rounded-full bg-success-600 transition-all duration-300"
                                    :style="{ width: `${progressPercent}%` }"
                                />
                            </div>
                        </div>

                        <ul class="max-h-[420px] divide-y divide-neutral-100 overflow-y-auto">
                            <li
                                v-for="(video, index) in videos"
                                :key="video.id"
                                class="transition-colors"
                                :class="video.isCurrent ? 'bg-primary-50/65' : 'hover:bg-sand-50/80'"
                            >
                                <component
                                    :is="video.isPlayable && video.watchUrl ? Link : 'div'"
                                    :href="video.isPlayable && video.watchUrl ? video.watchUrl : undefined"
                                    :preserve-scroll="video.isPlayable && video.watchUrl ? true : undefined"
                                    class="flex items-start gap-3 px-4 py-3.5 sm:px-5"
                                >
                                    <span
                                        class="tabular mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                        :class="
                                            completedIds.includes(video.id)
                                                ? 'bg-success-600 text-white'
                                                : video.isCurrent
                                                    ? 'bg-primary-600 text-white'
                                                    : 'bg-sand-100 text-navy-900'
                                        "
                                    >
                                        <Check v-if="completedIds.includes(video.id)" class="size-3.5" aria-hidden="true" />
                                        <span v-else>{{ index + 1 }}</span>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm leading-snug"
                                            :class="video.isCurrent ? 'font-bold text-primary-700' : 'font-semibold text-navy-950'"
                                        >
                                            {{ video.title }}
                                        </p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="tabular text-xs text-muted">{{ video.durationLabel }}</span>
                                            <StatusBadge v-if="video.isCurrent" tone="primary">Playing</StatusBadge>
                                            <StatusBadge v-else-if="!video.isPlayable" tone="warning">Processing</StatusBadge>
                                        </div>
                                    </div>
                                </component>
                            </li>
                        </ul>
                    </div>

                    <!-- Learning Objectives Card (Directly Visible in Sidebar) -->
                    <div
                        v-if="module.learningObjectives.length > 0"
                        class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <h2 class="text-base font-bold text-navy-950">Learning objectives</h2>
                        <ul class="mt-4 space-y-3">
                            <li
                                v-for="(objective, idx) in module.learningObjectives"
                                :key="idx"
                                class="flex items-start gap-2.5 text-sm leading-relaxed text-ink"
                            >
                                <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-success-600" aria-hidden="true" />
                                <span>{{ objective }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Access Details Card -->
                    <div class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80">
                        <h2 class="text-base font-bold text-navy-950">Module details</h2>
                        <dl class="mt-4 space-y-3 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Code</dt>
                                <dd class="tabular-nums-mono font-semibold text-navy-950">{{ module.code }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Access</dt>
                                <dd class="font-semibold text-success-700">Lifetime</dd>
                            </div>
                            <div v-if="entitlement?.grantedAtLabel" class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Granted</dt>
                                <dd class="tabular font-medium text-navy-950">{{ entitlement.grantedAtLabel }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Materials</dt>
                                <dd class="tabular font-semibold text-navy-950">{{ materials.length }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Video lessons</dt>
                                <dd class="tabular font-semibold text-navy-950">{{ videos.length }}</dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </div>
    </PortalLayout>
</template>
