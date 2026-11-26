<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    ChevronDown,
    Download,
    FileText,
    Play,
    Search,
    Video,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { pluralize } from '@/lib/utils';
import type { PortalRecordingsIndexProps } from '@/types/etms';

const props = defineProps<PortalRecordingsIndexProps>();

const { routes } = useRoutes();

type MediaFilter = 'all' | 'videos' | 'materials';
const activeFilter = ref<MediaFilter>('all');
const searchQuery = ref('');

// Expand sessions that actually have recordings/materials by default; collapse empty ones so the page stays concise
const expandedIds = ref<number[]>(
    props.bookingItems
        .filter((item) => item.recordings.length > 0 || (item.materials?.length ?? 0) > 0)
        .map((item) => item.bookingId),
);

function toggleExpanded(bookingId: number): void {
    if (expandedIds.value.includes(bookingId)) {
        expandedIds.value = expandedIds.value.filter((id) => id !== bookingId);
    } else {
        expandedIds.value = [...expandedIds.value, bookingId];
    }
}

const filteredItems = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    return props.bookingItems.filter((item) => {
        if (activeFilter.value === 'videos' && item.recordings.length === 0) return false;
        if (activeFilter.value === 'materials' && (item.materials?.length ?? 0) === 0) return false;

        if (!q) return true;
        return (
            item.sessionTitle.toLowerCase().includes(q) ||
            item.sessionCode.toLowerCase().includes(q) ||
            item.recordings.some((r) => r.title.toLowerCase().includes(q)) ||
            (item.materials ?? []).some((m) => m.title.toLowerCase().includes(q))
        );
    });
});

const filterTabs: { key: MediaFilter; label: string }[] = [
    { key: 'all', label: 'All sessions' },
    { key: 'videos', label: 'With recordings' },
    { key: 'materials', label: 'With materials' },
];
</script>

<template>
    <Head title="Recordings & Resources" />

    <PortalLayout breadcrumb="Recordings">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Recordings &amp; resources</h1>
                    <p class="mt-1 text-sm text-muted">
                        Watch published session replays and download materials for your registered sessions.
                    </p>
                </div>

                <AppButton :href="routes.sessions" variant="outline" size="sm" pill>
                    <span>Browse Sessions</span>
                    <ArrowUpRight class="size-3.5" aria-hidden="true" />
                </AppButton>
            </header>

            <!-- Compact Search & Filter Bar -->
            <div
                v-if="bookingItems.length > 0"
                class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.key"
                        type="button"
                        class="cursor-pointer rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors"
                        :class="
                            activeFilter === tab.key
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="activeFilter = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div class="relative sm:w-64">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                        aria-hidden="true"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search sessions or videos..."
                        aria-label="Search sessions or recordings"
                        class="h-9 w-full rounded-xl border border-line-strong/80 bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                    />
                </div>
            </div>

            <!-- Empty State (no confirmed sessions) -->
            <EmptyState
                v-if="bookingItems.length === 0"
                :icon="Video"
                title="No registered sessions yet"
                description="Once you register for a live session, its recordings and downloadable resources will appear here."
            >
                <template #action>
                    <AppButton :href="routes.sessions" variant="warm" size="sm" pill>
                        Browse Live Sessions
                    </AppButton>
                </template>
            </EmptyState>

            <!-- Filtered Empty State -->
            <EmptyState
                v-else-if="filteredItems.length === 0"
                :icon="Search"
                title="No matching sessions"
                description="Try switching the filter or clearing your search."
            >
                <template #action>
                    <AppButton
                        variant="outline"
                        size="sm"
                        pill
                        @click="activeFilter = 'all'; searchQuery = ''"
                    >
                        Reset filters
                    </AppButton>
                </template>
            </EmptyState>

            <!-- Compact Accordion Session List -->
            <div v-else class="space-y-3.5">
                <section
                    v-for="item in filteredItems"
                    :key="item.bookingId"
                    class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80"
                >
                    <!-- Clean Session Row Header -->
                    <div class="flex items-center justify-between gap-3 px-5 py-4">
                        <button
                            type="button"
                            class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 text-left"
                            :aria-expanded="expandedIds.includes(item.bookingId)"
                            @click="toggleExpanded(item.bookingId)"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl transition-colors"
                                :class="
                                    item.recordings.length > 0
                                        ? 'bg-navy-950 text-white'
                                        : 'bg-sand-100 text-muted'
                                "
                            >
                                <Video class="size-4" aria-hidden="true" />
                            </span>

                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-bold text-navy-950 sm:text-[15px]">
                                    {{ item.sessionTitle }}
                                </h2>
                                <p class="tabular mt-0.5 text-xs text-muted">
                                    {{ item.scheduleLabel }}
                                </p>
                            </div>
                        </button>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                v-if="item.recordings.length > 0"
                                class="tabular hidden rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-semibold text-primary-700 sm:inline-flex"
                            >
                                {{ item.recordings.length }} {{ pluralize(item.recordings.length, 'video') }}
                            </span>
                            <span
                                v-if="(item.materials?.length ?? 0) > 0"
                                class="tabular hidden rounded-full bg-accent-50 px-2.5 py-0.5 text-xs font-semibold text-accent-700 sm:inline-flex"
                            >
                                {{ item.materials?.length }} {{ pluralize(item.materials?.length ?? 0, 'file') }}
                            </span>
                            <span
                                v-if="item.recordings.length === 0 && (item.materials?.length ?? 0) === 0"
                                class="hidden text-xs text-muted sm:inline"
                            >
                                Pending uploads
                            </span>

                            <Link
                                :href="item.bookingDetailUrl"
                                class="hidden items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold text-muted transition-colors hover:bg-sand-100 hover:text-navy-950 md:inline-flex"
                            >
                                <span>Details</span>
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            </Link>

                            <button
                                type="button"
                                :aria-label="expandedIds.includes(item.bookingId) ? 'Collapse session' : 'Expand session'"
                                class="inline-flex size-8 cursor-pointer items-center justify-center rounded-full text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                                @click="toggleExpanded(item.bookingId)"
                            >
                                <ChevronDown
                                    class="size-4 transition-transform duration-150"
                                    :class="expandedIds.includes(item.bookingId) ? 'rotate-180' : ''"
                                    aria-hidden="true"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Expanded Media Content -->
                    <div
                        v-if="expandedIds.includes(item.bookingId)"
                        class="border-t border-line bg-sand-50/50 px-5 py-4"
                    >
                        <!-- Recordings List -->
                        <div v-if="item.isEligible && item.recordings.length > 0" class="space-y-2.5">
                            <div
                                v-for="recording in item.recordings"
                                :key="recording.id"
                                class="flex flex-col justify-between gap-3 rounded-xl bg-white p-3.5 ring-1 ring-line/80 sm:flex-row sm:items-center"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                                        <Play class="size-4" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-navy-950">
                                            {{ recording.title }}
                                        </p>
                                        <p class="tabular mt-0.5 text-xs text-muted">
                                            {{ recording.durationLabel }}
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <AppButton
                                        v-if="recording.isReady && recording.watchUrl"
                                        :href="recording.watchUrl"
                                        variant="warm"
                                        size="sm"
                                        pill
                                    >
                                        <Play class="size-3.5" aria-hidden="true" />
                                        <span>Watch</span>
                                    </AppButton>
                                    <StatusBadge v-else tone="warning">
                                        {{ recording.encodingStatusLabel }}
                                    </StatusBadge>
                                </div>
                            </div>
                        </div>

                        <p v-else-if="!item.isEligible" class="text-xs text-muted">
                            {{ item.denialReason || 'Recordings are not enabled for this session.' }}
                        </p>

                        <p v-else-if="(item.materials?.length ?? 0) === 0" class="text-xs text-muted">
                            No recordings or materials have been published for this session yet.
                        </p>

                        <!-- Materials List -->
                        <div
                            v-if="(item.materials?.length ?? 0) > 0"
                            class="space-y-2"
                            :class="item.recordings.length > 0 ? 'mt-4 border-t border-line pt-4' : ''"
                        >
                            <div
                                v-for="material in item.materials"
                                :key="material.id"
                                class="flex flex-col justify-between gap-3 rounded-xl bg-white p-3.5 ring-1 ring-line/80 sm:flex-row sm:items-center"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-700">
                                        <FileText class="size-4" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-navy-950">
                                            {{ material.title }}
                                        </p>
                                        <p class="tabular mt-0.5 text-xs text-muted">
                                            {{ material.typeLabel }} &middot; {{ material.fileSizeLabel }}
                                        </p>
                                    </div>
                                </div>

                                <AppButton
                                    v-if="material.isDownloadable && material.downloadUrl"
                                    :href="material.downloadUrl"
                                    external
                                    variant="outline"
                                    size="sm"
                                    pill
                                >
                                    <Download class="size-3.5" aria-hidden="true" />
                                    <span>Download</span>
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </PortalLayout>
</template>
