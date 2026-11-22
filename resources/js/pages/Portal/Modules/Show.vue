<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Download, FileText, Film, PlayCircle } from 'lucide-vue-next';
import HlsVideoPlayer from '@/components/portal/HlsVideoPlayer.vue';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { cn } from '@/lib/utils';
import type { PortalModuleWorkspaceProps } from '@/types/etms';

defineProps<PortalModuleWorkspaceProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head :title="`${module.title} — Learning Workspace`" />

    <PortalLayout :breadcrumb="module.code">
        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <Link
                    :href="routes.portalModules"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-neutral-600 transition-colors duration-150 hover:text-[#0F4C81]"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Back to My Modules</span>
                </Link>

                <div class="mt-3 flex flex-wrap items-center gap-2.5">
                    <span class="rounded-[6px] border border-neutral-200 bg-white px-2.5 py-0.5 font-mono text-xs font-medium text-neutral-700">
                        {{ module.code }}
                    </span>
                    <span class="rounded-[6px] border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                        Lifetime Access
                    </span>
                </div>

                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-900 sm:text-3xl">
                    {{ module.title }}
                </h1>
                <p
                    v-if="entitlement?.grantedAtLabel"
                    class="mt-1 text-sm text-neutral-500 tabular-nums"
                >
                    Access granted {{ entitlement.grantedAtLabel }}
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                <div :class="cn('space-y-6', videos.length > 0 ? 'lg:col-span-8' : 'lg:col-span-12')">
                    <div
                        v-if="activeVideo && streamManifestUrl"
                        class="overflow-hidden rounded-lg border border-neutral-200 bg-white"
                    >
                        <div class="border-b border-neutral-200 px-6 py-4">
                            <h2 class="text-base font-semibold text-neutral-900">{{ activeVideo.title }}</h2>
                            <p class="mt-0.5 text-xs text-neutral-500 tabular-nums">
                                Duration: {{ activeVideo.durationLabel }}
                            </p>
                        </div>
                        <div class="p-6">
                            <HlsVideoPlayer
                                :src="streamManifestUrl"
                                :title="activeVideo.title"
                            />
                            <p
                                v-if="activeVideo.description"
                                class="mt-4 text-sm leading-relaxed text-neutral-600"
                            >
                                {{ activeVideo.description }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="videos.length > 0"
                        role="status"
                        class="flex items-start gap-3 rounded-lg border border-neutral-200 bg-white p-4 text-sm"
                    >
                        <Film class="mt-0.5 h-4 w-4 shrink-0 text-[#0F4C81]" aria-hidden="true" />
                        <div>
                            <p class="font-semibold text-neutral-900">Video Curriculum Preparing</p>
                            <p class="mt-0.5 text-neutral-600">
                                Video lessons for this module are currently being prepared for playback. Please check back shortly.
                            </p>
                        </div>
                    </div>

                    <div class="divide-y divide-neutral-200 rounded-lg border border-neutral-200 bg-white">
                        <section class="p-6">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 tabular-nums">
                                    Learning Materials ({{ materials.length }})
                                </h2>
                                <span class="text-xs text-neutral-500">Secure time-limited downloads</span>
                            </div>

                            <p v-if="materials.length === 0" class="mt-3 text-sm text-neutral-500">
                                No downloadable materials have been published for this module yet.
                            </p>

                            <div v-else class="mt-4 divide-y divide-neutral-100">
                                <div
                                    v-for="material in materials"
                                    :key="material.id"
                                    class="flex flex-col gap-3 py-3.5 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <FileText class="mt-0.5 h-4 w-4 shrink-0 text-neutral-400" aria-hidden="true" />
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-semibold text-neutral-900">
                                                    {{ material.title }}
                                                </span>
                                                <span class="rounded border border-neutral-200 bg-neutral-50 px-1.5 py-0.5 text-[11px] font-medium text-neutral-600">
                                                    {{ material.typeLabel }}
                                                </span>
                                            </div>
                                            <p v-if="material.description" class="mt-0.5 text-xs text-neutral-600">
                                                {{ material.description }}
                                            </p>
                                            <p class="mt-1 font-mono text-xs text-neutral-500 tabular-nums">
                                                {{ material.originalFilename }} &middot; {{ material.fileSizeLabel }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="shrink-0">
                                        <Button
                                            v-if="material.isDownloadable && material.downloadUrl"
                                            as="a"
                                            :href="material.downloadUrl"
                                            size="sm"
                                            class="h-9 rounded-[6px] bg-[#0F4C81] px-3.5 text-xs font-medium text-white hover:bg-[#0F4C81]/90"
                                        >
                                            <Download class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span>Download ({{ material.fileSizeLabel }})</span>
                                        </Button>
                                        <span
                                            v-else
                                            class="rounded-[6px] border border-neutral-200 px-2.5 py-1 text-xs text-neutral-500"
                                        >
                                            Unavailable
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section v-if="module.learningObjectives.length > 0" class="p-6">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                                Learning Objectives
                            </h2>
                            <ul class="mt-4 grid gap-2.5 text-sm leading-relaxed text-neutral-700 sm:grid-cols-2">
                                <li
                                    v-for="(objective, idx) in module.learningObjectives"
                                    :key="idx"
                                    class="flex items-start gap-2.5"
                                >
                                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#0F4C81]" aria-hidden="true" />
                                    <span>{{ objective }}</span>
                                </li>
                            </ul>
                        </section>

                        <section class="p-6">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                                Syllabus &amp; Overview
                            </h2>
                            <div class="mt-3 whitespace-pre-line text-sm leading-relaxed text-neutral-700">
                                {{ module.description }}
                            </div>
                        </section>
                    </div>
                </div>

                <aside v-if="videos.length > 0" class="rounded-lg border border-neutral-200 bg-white lg:col-span-4">
                    <div class="border-b border-neutral-200 px-5 py-4">
                        <h2 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-neutral-500 tabular-nums">
                            <PlayCircle class="h-4 w-4 text-neutral-400" aria-hidden="true" />
                            <span>Video Lessons ({{ videos.length }})</span>
                        </h2>
                    </div>

                    <div class="divide-y divide-neutral-100">
                        <div
                            v-for="(video, index) in videos"
                            :key="video.id"
                            :class="
                                cn(
                                    'p-4 transition-colors duration-150',
                                    video.isCurrent ? 'bg-[#0F4C81]/[0.04]' : 'bg-white',
                                )
                            "
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold leading-snug text-neutral-900">
                                    <span class="mr-1 font-mono text-xs text-[#0F4C81] tabular-nums">
                                        {{ index + 1 }}.
                                    </span>
                                    {{ video.title }}
                                </p>
                                <span class="shrink-0 font-mono text-xs text-neutral-500 tabular-nums">
                                    {{ video.durationLabel }}
                                </span>
                            </div>

                            <div class="mt-2.5 flex items-center justify-between gap-2">
                                <span class="text-xs text-neutral-500">
                                    {{ video.encodingStatusLabel }}
                                </span>

                                <Button
                                    v-if="video.isPlayable && video.watchUrl"
                                    as-child
                                    :variant="video.isCurrent ? 'default' : 'outline'"
                                    size="sm"
                                    :class="
                                        cn(
                                            'h-8 rounded-[6px] text-xs',
                                            video.isCurrent && 'bg-[#0F4C81] text-white hover:bg-[#0F4C81]/90',
                                        )
                                    "
                                >
                                    <Link :href="video.watchUrl" preserve-scroll>
                                        {{ video.isCurrent ? 'Playing' : 'Watch Lesson' }}
                                    </Link>
                                </Button>

                                <PortalStatusBadge
                                    v-else
                                    status="processing"
                                    label="Processing"
                                />
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </PortalLayout>
</template>
