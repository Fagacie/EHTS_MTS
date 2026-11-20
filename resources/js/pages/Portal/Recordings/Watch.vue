<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft } from 'lucide-vue-next';
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
import type { PortalRecordingWatchProps } from '@/types/etms';

defineProps<PortalRecordingWatchProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head :title="recording.title" />

    <PortalLayout
        :breadcrumb="`${session?.sessionCode ?? 'Session'} / Recording`"
    >
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            v-if="session?.sessionCode"
                            variant="outline"
                            class="tabular-nums"
                        >
                            {{ session.sessionCode }}
                        </Badge>
                        <PortalStatusBadge
                            status="verified"
                            label="Verified Attendee Access"
                        />
                    </div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        {{ recording.title }}
                    </h1>
                    <p class="text-[13.5px] text-[#625D6D] tabular-nums">
                        <template v-if="session?.title">{{ session.title }} &middot; </template>
                        Duration: {{ recording.durationLabel }}
                    </p>
                </div>

                <Button as-child variant="outline" size="sm" class="shrink-0">
                    <Link :href="routes.portalRecordings">
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        <span>Back to Session Recordings</span>
                    </Link>
                </Button>
            </header>

            <Card>
                <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                    <CardTitle>Session Recording Player</CardTitle>
                    <CardDescription>
                        Authorised playback for verified session attendees
                    </CardDescription>
                </CardHeader>
                <CardContent class="pt-4">
                    <HlsVideoPlayer
                        v-if="streamManifestUrl"
                        :src="streamManifestUrl"
                        :title="recording.title"
                    />

                    <div
                        v-else
                        role="status"
                        class="flex items-start gap-3 rounded-xl border border-[#E4DFF1] bg-[#F1EDFB] p-4"
                    >
                        <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#5942B5]" aria-hidden="true" />
                        <div>
                            <p class="text-[14.5px] font-bold text-[#282338]">Video Preparing</p>
                            <p class="mt-1 text-[13px] text-[#403B4B]">
                                This session recording is currently being prepared for streaming. Please check back shortly.
                            </p>
                        </div>
                    </div>

                    <p
                        v-if="recording.description"
                        class="mt-4 text-[13.5px] leading-relaxed text-[#403B4B]"
                    >
                        {{ recording.description }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </PortalLayout>
</template>
