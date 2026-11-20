<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Download, Film } from 'lucide-vue-next';
import HlsVideoPlayer from '@/components/portal/HlsVideoPlayer.vue';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
        <div class="mx-auto max-w-[1440px] space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" class="tabular-nums">
                            {{ module.code }}
                        </Badge>
                        <PortalStatusBadge status="active" label="Lifetime Access" />
                    </div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        {{ module.title }}
                    </h1>
                    <p
                        v-if="entitlement?.grantedAtLabel"
                        class="text-[13.5px] text-[#625D6D] tabular-nums"
                    >
                        Access granted on {{ entitlement.grantedAtLabel }}
                    </p>
                </div>

                <Button as-child variant="outline" size="sm" class="shrink-0">
                    <Link :href="routes.portalModules">
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        <span>Back to My Learning</span>
                    </Link>
                </Button>
            </header>

            <div class="grid gap-6 lg:grid-cols-12">
                <div class="space-y-5 lg:col-span-8">
                    <Card v-if="activeVideo && streamManifestUrl">
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>{{ activeVideo.title }}</CardTitle>
                            <CardDescription class="tabular-nums">
                                Duration: {{ activeVideo.durationLabel }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <HlsVideoPlayer
                                :src="streamManifestUrl"
                                :title="activeVideo.title"
                            />
                            <p
                                v-if="activeVideo.description"
                                class="mt-4 text-[13.5px] leading-relaxed text-[#403B4B]"
                            >
                                {{ activeVideo.description }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card
                        v-else-if="videos.length > 0"
                        role="status"
                        class="border-[#E4DFF1] bg-[#F1EDFB] p-4 shadow-none sm:px-5"
                    >
                        <div class="flex items-start gap-3">
                            <Film class="mt-0.5 h-5 w-5 shrink-0 text-[#5942B5]" aria-hidden="true" />
                            <div>
                                <p class="text-[14.5px] font-bold text-[#282338]">
                                    Video Curriculum Preparing
                                </p>
                                <p class="mt-1 text-[13px] text-[#403B4B]">
                                    Video lessons for this module are currently being prepared for playback. Please check back shortly.
                                </p>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle class="tabular-nums">
                                Learning Materials ({{ materials.length }})
                            </CardTitle>
                            <CardDescription>
                                Time-limited secure downloads for your enrolled module
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <p
                                v-if="materials.length === 0"
                                class="text-[13.5px] text-[#625D6D]"
                            >
                                No downloadable materials have been published for this module yet.
                            </p>

                            <div v-else class="divide-y divide-[#EEEAF2]">
                                <div
                                    v-for="material in materials"
                                    :key="material.id"
                                    class="flex flex-col gap-3 py-3.5 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[14.5px] font-bold text-[#282338]">
                                                {{ material.title }}
                                            </span>
                                            <Badge variant="outline">
                                                {{ material.typeLabel }}
                                            </Badge>
                                        </div>

                                        <p
                                            v-if="material.description"
                                            class="text-[13px] leading-relaxed text-[#403B4B]"
                                        >
                                            {{ material.description }}
                                        </p>

                                        <p class="text-[12px] text-[#625D6D] tabular-nums">
                                            {{ material.originalFilename }} &middot; {{ material.fileSizeLabel }}
                                        </p>
                                    </div>

                                    <div class="shrink-0">
                                        <Button
                                            v-if="material.isDownloadable && material.downloadUrl"
                                            as="a"
                                            :href="material.downloadUrl"
                                            size="sm"
                                        >
                                            <Download class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span>Download ({{ material.fileSizeLabel }})</span>
                                        </Button>

                                        <Badge v-else variant="outline">
                                            Download Unavailable
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Syllabus &amp; Learning Objectives</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-4">
                            <ul
                                v-if="module.learningObjectives.length > 0"
                                class="space-y-2 text-[13.5px] leading-relaxed text-[#292536]"
                            >
                                <li
                                    v-for="(objective, idx) in module.learningObjectives"
                                    :key="idx"
                                    class="flex items-start gap-2.5"
                                >
                                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#5942B5]" aria-hidden="true" />
                                    <span>{{ objective }}</span>
                                </li>
                            </ul>

                            <div
                                :class="
                                    cn(
                                        'text-[13.5px] leading-relaxed whitespace-pre-line text-[#403B4B]',
                                        module.learningObjectives.length > 0 && 'border-t border-[#EEEAF2] pt-4',
                                    )
                                "
                            >
                                {{ module.description }}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <aside class="lg:col-span-4">
                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle class="tabular-nums">
                                Video Lessons ({{ videos.length }})
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <p
                                v-if="videos.length === 0"
                                class="text-[13.5px] leading-relaxed text-[#625D6D]"
                            >
                                This programme is delivered through the downloadable workbooks and technical documents on this page.
                            </p>

                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="(video, index) in videos"
                                    :key="video.id"
                                    :class="
                                        cn(
                                            'rounded-xl border p-3.5 transition-colors',
                                            video.isCurrent
                                                ? 'border-[#7057D9] bg-[#F1EDFB]/70'
                                                : 'border-[#E5E1EB] bg-white',
                                        )
                                    "
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-[13.5px] font-bold leading-snug text-[#282338]">
                                            <span class="mr-1 font-semibold text-[#5942B5] tabular-nums">
                                                {{ index + 1 }}.
                                            </span>
                                            {{ video.title }}
                                        </p>
                                        <span class="shrink-0 text-[12px] font-semibold text-[#625D6D] tabular-nums">
                                            {{ video.durationLabel }}
                                        </span>
                                    </div>

                                    <div class="mt-2.5 flex items-center justify-between gap-2">
                                        <span class="text-[12px] text-[#625D6D]">
                                            {{ video.encodingStatusLabel }}
                                        </span>

                                        <Button
                                            v-if="video.isPlayable && video.watchUrl"
                                            as-child
                                            :variant="video.isCurrent ? 'default' : 'outline'"
                                            size="sm"
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
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </PortalLayout>
</template>
