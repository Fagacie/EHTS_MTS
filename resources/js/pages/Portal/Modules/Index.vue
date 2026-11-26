<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    BookOpen,
    Download,
    FileText,
    PlayCircle,
    Search,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { pluralize } from '@/lib/utils';
import type { PortalModulesIndexProps } from '@/types/etms';

const props = defineProps<PortalModulesIndexProps>();

const { routes } = useRoutes();
const searchQuery = ref('');

const filteredEntitlements = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.activeEntitlements;

    return props.activeEntitlements.filter(
        (e) =>
            e.module.title.toLowerCase().includes(q) ||
            e.module.code.toLowerCase().includes(q) ||
            (e.module.categoryName?.toLowerCase().includes(q) ?? false),
    );
});
</script>

<template>
    <Head title="My Modules" />

    <PortalLayout breadcrumb="My Modules">
        <div class="mx-auto max-w-[1440px] space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">My modules</h1>
                    <p class="mt-1 text-sm text-muted">
                        Open your self-paced training workspaces, downloadable manuals, and video lessons.
                    </p>
                </div>

                <AppButton :href="routes.catalogue" variant="warm" size="sm" pill>
                    <span>Explore Modules</span>
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </AppButton>
            </header>

            <!-- Search Bar (when user has modules) -->
            <div
                v-if="activeEntitlements.length > 0"
                class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="relative flex-1 sm:max-w-sm">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted"
                        aria-hidden="true"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search your modules..."
                        aria-label="Search your modules"
                        class="h-9.5 w-full rounded-xl border border-line-strong/80 bg-white pr-3.5 pl-9.5 text-xs text-ink placeholder:text-muted/75 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                    />
                </div>

                <p class="tabular text-xs font-medium text-muted">
                    Showing <span class="font-semibold text-navy-950">{{ filteredEntitlements.length }}</span> of
                    <span class="font-semibold text-navy-950">{{ activeEntitlements.length }}</span>
                    {{ pluralize(activeEntitlements.length, 'module') }}
                </p>
            </div>

            <!-- Empty State (no modules and no session documents) -->
            <EmptyState
                v-if="activeEntitlements.length === 0 && sessionDocuments.length === 0"
                :icon="BookOpen"
                title="No training modules unlocked yet"
                description="Purchase any self-paced training module to access downloadable manuals and video lessons."
            >
                <template #action>
                    <AppButton :href="routes.catalogue" variant="warm" size="sm" pill>
                        Browse Modules
                    </AppButton>
                </template>
            </EmptyState>

            <!-- Active Module Cards -->
            <section v-if="activeEntitlements.length > 0" aria-label="Active modules">
                <div
                    v-if="filteredEntitlements.length > 0"
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="entitlement in filteredEntitlements"
                        :key="entitlement.id"
                        :href="entitlement.module.workspaceUrl"
                        class="group block rounded-2xl p-2 -m-2 transition-all duration-200 hover:bg-white/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                    >
                        <!-- Borderless Photo-First Media -->
                        <div class="relative aspect-[16/10] overflow-hidden rounded-2xl bg-navy-900 shadow-card transition-shadow duration-200 group-hover:shadow-card-hover">
                            <img
                                v-if="entitlement.module.coverImageUrl"
                                :src="entitlement.module.coverImageUrl"
                                :alt="entitlement.module.title"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.04]"
                            />
                            <div
                                v-else
                                class="flex h-full w-full flex-col justify-between bg-navy-900 p-5 text-white"
                            >
                                <span class="tabular-nums-mono text-xs font-medium text-white/60">
                                    {{ entitlement.module.code }}
                                </span>
                                <p class="line-clamp-2 text-base font-semibold leading-snug text-white">
                                    {{ entitlement.module.title }}
                                </p>
                            </div>
                        </div>

                        <!-- Minimal Editorial Metadata -->
                        <div class="px-1 pt-3.5 pb-1">
                            <h2 class="line-clamp-2 text-[16px] font-semibold leading-snug text-navy-950 transition-colors duration-150 group-hover:text-primary-600">
                                {{ entitlement.module.title }}
                            </h2>

                            <div class="mt-1.5 flex items-center gap-3 text-xs font-medium text-muted">
                                <span class="tabular inline-flex items-center gap-1">
                                    <PlayCircle class="size-3.5 text-primary-600" aria-hidden="true" />
                                    {{ entitlement.module.videosCount }} {{ pluralize(entitlement.module.videosCount, 'lesson') }}
                                </span>
                                <span class="tabular inline-flex items-center gap-1">
                                    <FileText class="size-3.5 text-accent-600" aria-hidden="true" />
                                    {{ entitlement.module.materialsCount }} {{ pluralize(entitlement.module.materialsCount, 'manual') }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="tabular text-xs font-medium text-muted">
                                    {{ entitlement.accessLabel }}
                                </span>

                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-navy-950 transition-colors duration-150 group-hover:text-coral-600">
                                    <span class="relative after:absolute after:bottom-0 after:left-0 after:h-[1.5px] after:w-0 after:bg-coral-500 after:transition-all after:duration-200 group-hover:after:w-full">
                                        Open workspace
                                    </span>
                                    <ArrowUpRight
                                        class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                        aria-hidden="true"
                                    />
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <EmptyState
                    v-else
                    :icon="Search"
                    title="No modules match your search"
                    description="Try clearing your search keyword."
                >
                    <template #action>
                        <AppButton variant="outline" size="sm" pill @click="searchQuery = ''">
                            Clear search
                        </AppButton>
                    </template>
                </EmptyState>
            </section>

            <!-- Purchased Session Documents -->
            <section
                v-if="sessionDocuments.length > 0"
                class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
            >
                <h2 class="text-base font-bold text-navy-950">Session documents</h2>
                <ul class="mt-4 divide-y divide-neutral-100">
                    <li
                        v-for="document in sessionDocuments"
                        :key="document.id"
                        class="flex flex-wrap items-center justify-between gap-4 py-3.5 first:pt-0 last:pb-0"
                    >
                        <div class="flex items-start gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-700">
                                <FileText class="size-4" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-navy-950">{{ document.documentTitle }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    {{ document.sessionTitle }} &middot; Granted {{ document.grantedDateLabel }}
                                </p>
                            </div>
                        </div>

                        <AppButton
                            v-if="document.downloadUrl"
                            :href="document.downloadUrl"
                            external
                            variant="outline"
                            size="sm"
                            pill
                        >
                            <Download class="size-3.5" aria-hidden="true" />
                            <span>Download</span>
                        </AppButton>
                    </li>
                </ul>
            </section>

            <!-- Inactive & Revoked Modules -->
            <section
                v-if="revokedEntitlements.length > 0"
                class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
            >
                <h2 class="text-base font-bold text-navy-950">Inactive modules</h2>
                <ul class="mt-4 divide-y divide-neutral-100">
                    <li
                        v-for="revoked in revokedEntitlements"
                        :key="revoked.id"
                        class="flex flex-wrap items-center justify-between gap-4 py-3.5 first:pt-0 last:pb-0"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span v-if="revoked.module" class="tabular-nums-mono text-xs font-medium text-muted">
                                    {{ revoked.module.code }}
                                </span>
                                <StatusBadge tone="danger">Revoked</StatusBadge>
                            </div>
                            <p v-if="revoked.module" class="mt-1 text-sm font-semibold text-navy-950">
                                {{ revoked.module.title }}
                            </p>
                            <p v-if="revoked.revocationReason" class="mt-0.5 text-xs text-muted">
                                {{ revoked.revocationReason }}
                            </p>
                        </div>

                        <AppButton
                            v-if="revoked.module"
                            :href="revoked.module.catalogueUrl"
                            variant="outline"
                            size="sm"
                            pill
                        >
                            View Module
                        </AppButton>
                    </li>
                </ul>
            </section>
        </div>
    </PortalLayout>
</template>
