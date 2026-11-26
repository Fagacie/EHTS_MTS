<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft } from 'lucide-vue-next';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import HlsVideoPlayer from '@/components/portal/HlsVideoPlayer.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalRecordingWatchProps } from '@/types/etms';

defineProps<PortalRecordingWatchProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head :title="recording.title" />

    <PortalLayout :breadcrumb="recording.title">
        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <Link
                    :href="routes.portalRecordings"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Back to Recordings</span>
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                <div v-if="streamManifestUrl" class="bg-navy-950">
                    <HlsVideoPlayer
                        :src="streamManifestUrl"
                        :title="recording.title"
                    />
                </div>

                <div
                    v-else
                    role="status"
                    class="flex items-start gap-3 bg-primary-50 p-6"
                >
                    <AlertCircle class="mt-0.5 size-5 shrink-0 text-primary-600" aria-hidden="true" />
                    <div>
                        <p class="text-sm font-bold text-navy-950">Video is being prepared</p>
                        <p class="mt-0.5 text-xs text-muted">
                            This session recording is currently being encoded for streaming. Please check back shortly.
                        </p>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge tone="success" dot>Session Recording</StatusBadge>
                            <span v-if="session?.sessionCode" class="tabular-nums-mono text-xs font-medium text-muted">
                                {{ session.sessionCode }}
                            </span>
                        </div>
                        <span class="tabular rounded-full bg-sand-100 px-3 py-0.5 text-xs font-semibold text-navy-900">
                            {{ recording.durationLabel }}
                        </span>
                    </div>

                    <h1 class="type-page-title mt-3">{{ recording.title }}</h1>
                    <p v-if="session?.title" class="mt-1 text-sm text-muted">
                        {{ session.title }}
                    </p>
                    <p v-if="recording.description" class="mt-4 text-sm leading-relaxed text-ink/90">
                        {{ recording.description }}
                    </p>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
