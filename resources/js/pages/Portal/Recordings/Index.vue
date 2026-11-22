<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, FileText, Lock, Play } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
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
import type { PortalRecordingsIndexProps } from '@/types/etms';

defineProps<PortalRecordingsIndexProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head title="Session Recordings & Resources" />

    <PortalLayout breadcrumb="Recordings & Resources">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header>
                <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                    Session Recordings &amp; Resources
                </h1>
                <p class="mt-1 text-[13.5px] text-[#625D6D]">
                    Access supplementary teaching materials for your confirmed physical sessions and session recordings once your attendance is verified as Present.
                </p>
            </header>

            <Card
                v-if="bookingItems.length === 0"
                class="flex flex-col items-center justify-center px-6 py-12 text-center"
            >
                <p class="text-[16px] font-bold text-[#282338]">
                    No confirmed session bookings found
                </p>
                <p class="mt-1.5 max-w-lg text-[13.5px] text-[#625D6D]">
                    Register and attend a physical training session to unlock post-session recordings once verified.
                </p>

                <Button as-child class="mt-6">
                    <Link :href="routes.sessions">
                        Browse Physical Sessions
                    </Link>
                </Button>
            </Card>

            <div v-else class="space-y-5">
                <Card
                    v-for="item in bookingItems"
                    :key="item.bookingId"
                >
                    <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-1">
                                <CardTitle>
                                    {{ item.sessionCode }} &mdash; {{ item.sessionTitle }}
                                </CardTitle>
                                <CardDescription class="tabular-nums">
                                    Booking Ref: {{ item.bookingReference }} &middot; {{ item.scheduleLabel }}
                                </CardDescription>
                            </div>

                            <Button as-child variant="outline" size="sm" class="w-full shrink-0 sm:w-auto">
                                <Link :href="item.bookingDetailUrl">
                                    View Booking
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="pt-4">
                        <div
                            v-if="!item.isEligible"
                            class="flex items-start gap-3 rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] p-4"
                        >
                            <Lock class="mt-0.5 h-4 w-4 shrink-0 text-[#5942B5]" aria-hidden="true" />
                            <div>
                                <p class="text-[14px] font-bold text-[#282338]">
                                    Recording Access Locked
                                </p>
                                <p
                                    v-if="item.denialReason"
                                    class="mt-1 text-[13px] leading-relaxed text-[#403B4B]"
                                >
                                    {{ item.denialReason }}
                                </p>
                            </div>
                        </div>

                        <p
                            v-else-if="item.recordings.length === 0"
                            class="text-[13.5px] leading-relaxed text-[#625D6D]"
                        >
                            Your attendance is verified. Session recordings will appear here as soon as they are published.
                        </p>

                        <div v-else class="divide-y divide-[#EEEAF2]">
                            <div
                                v-for="recording in item.recordings"
                                :key="recording.id"
                                class="flex flex-col gap-3 py-3.5 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-[14.5px] font-bold text-[#282338]">
                                            {{ recording.title }}
                                        </span>
                                        <PortalStatusBadge status="published" label="Available" />
                                    </div>

                                    <p
                                        v-if="recording.description"
                                        class="text-[13px] leading-relaxed text-[#403B4B]"
                                    >
                                        {{ recording.description }}
                                    </p>

                                    <p class="text-[12.5px] text-[#625D6D] tabular-nums">
                                        Duration: {{ recording.durationLabel }} &middot; Status: {{ recording.encodingStatusLabel }}
                                    </p>
                                </div>

                                <div class="shrink-0">
                                    <Button
                                        v-if="recording.isReady && recording.watchUrl"
                                        as-child
                                        size="sm"
                                    >
                                        <Link :href="recording.watchUrl">
                                            <Play class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span>Watch Recording</span>
                                        </Link>
                                    </Button>

                                    <PortalStatusBadge
                                        v-else
                                        status="processing"
                                        :label="recording.encodingStatusLabel"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="(item.materials ?? []).length > 0"
                            class="mt-5 border-t border-[#EEEAF2] pt-4"
                        >
                            <p class="mb-2.5 text-[12.5px] font-bold uppercase tracking-wider text-[#625D6D]">
                                Published Session Resources
                            </p>
                            <div class="divide-y divide-[#EEEAF2] rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] px-4">
                                <div
                                    v-for="material in item.materials"
                                    :key="material.id"
                                    class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="flex min-w-0 items-start gap-2.5">
                                        <FileText class="mt-0.5 h-4 w-4 shrink-0 text-[#5942B5]" aria-hidden="true" />
                                        <div class="min-w-0">
                                            <p class="text-[14px] font-bold text-[#282338]">{{ material.title }}</p>
                                            <p v-if="material.description" class="text-[12.5px] text-[#403B4B]">
                                                {{ material.description }}
                                            </p>
                                            <p class="text-[12px] text-[#625D6D] tabular-nums">
                                                {{ material.typeLabel }} &middot; {{ material.originalFilename }} &middot; {{ material.fileSizeLabel }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        v-if="material.isDownloadable && material.downloadUrl"
                                        as-child
                                        variant="outline"
                                        size="sm"
                                        class="shrink-0"
                                    >
                                        <a :href="material.downloadUrl">
                                            <Download class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span>Download</span>
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </PortalLayout>
</template>
