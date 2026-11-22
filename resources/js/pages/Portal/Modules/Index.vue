<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, FileText, PlayCircle } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { pluralize } from '@/lib/utils';
import type { PortalModulesIndexProps } from '@/types/etms';

defineProps<PortalModulesIndexProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head title="My Learning Modules" />

    <PortalLayout breadcrumb="My Learning Modules">
        <div class="mx-auto max-w-6xl space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-3xl">
                        My Learning Modules
                    </h1>
                    <p class="mt-1 text-sm text-neutral-600">
                        Access your purchased standalone learning modules, downloadable documents, and self-paced video lessons.
                    </p>
                </div>

                <Button
                    v-if="activeEntitlements.length > 0"
                    as-child
                    variant="outline"
                    class="h-9 shrink-0 rounded-[6px] border-neutral-300 text-sm font-medium text-neutral-800 hover:bg-neutral-50"
                >
                    <Link :href="routes.catalogue">
                        Browse Standalone Modules
                    </Link>
                </Button>
            </header>

            <div
                v-if="activeEntitlements.length === 0 && sessionDocuments.length === 0"
                class="flex flex-col items-center justify-center rounded-lg border border-neutral-200 bg-white px-6 py-14 text-center"
            >
                <BookOpen class="h-8 w-8 text-neutral-400" aria-hidden="true" />
                <p class="mt-3 text-base font-semibold text-neutral-900">
                    No standalone learning modules purchased yet
                </p>
                <p class="mt-1 max-w-md text-sm text-neutral-600">
                    Purchase any standalone learning module to unlock self-paced technical documents and video lessons.
                </p>
                <Button
                    as-child
                    class="mt-6 h-10 rounded-[6px] bg-[#0F4C81] px-5 text-sm font-medium text-white hover:bg-[#0F4C81]/90"
                >
                    <Link :href="routes.catalogue">
                        Browse Standalone Modules
                    </Link>
                </Button>
            </div>

            <div
                v-if="sessionDocuments.length > 0"
                class="rounded-lg border border-neutral-200 bg-white"
            >
                <div class="border-b border-neutral-200 px-6 py-4">
                    <h2 class="text-sm font-semibold text-neutral-900">Purchased Session Documents</h2>
                    <p class="mt-0.5 text-xs text-neutral-500">
                        Historical documents purchased separately from physical session bookings
                    </p>
                </div>
                <div class="divide-y divide-neutral-100 px-6">
                    <div
                        v-for="document in sessionDocuments"
                        :key="document.id"
                        class="flex flex-wrap items-center justify-between gap-4 py-3.5"
                    >
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">{{ document.documentTitle }}</p>
                            <p class="mt-0.5 text-xs text-neutral-500">
                                {{ document.sessionTitle }} &middot; Granted {{ document.grantedDateLabel }}
                            </p>
                        </div>
                        <Button
                            v-if="document.downloadUrl"
                            as-child
                            size="sm"
                            class="rounded-[6px] bg-[#0F4C81] text-white hover:bg-[#0F4C81]/90"
                        >
                            <a :href="document.downloadUrl">Download</a>
                        </Button>
                    </div>
                </div>
            </div>

            <section v-if="activeEntitlements.length > 0" aria-label="Active Module Workspaces">
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="entitlement in activeEntitlements"
                        :key="entitlement.id"
                        class="flex h-full flex-col justify-between rounded-lg border border-neutral-200 bg-white p-5 transition-colors duration-150 hover:border-neutral-300"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-xs font-semibold text-[#0F4C81] tabular-nums">
                                    {{ entitlement.module.code }}
                                </span>
                                <span class="rounded-[6px] border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                    {{ entitlement.accessLabel }}
                                </span>
                            </div>

                            <h2 class="mt-3 text-base font-semibold leading-snug text-neutral-900">
                                <Link
                                    :href="entitlement.module.workspaceUrl"
                                    class="transition-colors duration-150 hover:text-[#0F4C81]"
                                >
                                    {{ entitlement.module.title }}
                                </Link>
                            </h2>

                            <p
                                v-if="entitlement.module.summary"
                                class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-neutral-600"
                            >
                                {{ entitlement.module.summary }}
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-neutral-600 tabular-nums">
                                <span class="inline-flex items-center gap-1.5">
                                    <FileText class="h-3.5 w-3.5 text-neutral-400" aria-hidden="true" />
                                    <span>
                                        {{ entitlement.module.materialsCount }}
                                        {{ pluralize(entitlement.module.materialsCount, 'material') }}
                                    </span>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <PlayCircle class="h-3.5 w-3.5 text-neutral-400" aria-hidden="true" />
                                    <span>
                                        {{ entitlement.module.videosCount }}
                                        {{ pluralize(entitlement.module.videosCount, 'lesson') }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-neutral-100 pt-4">
                            <div class="mb-3 flex items-center justify-between text-xs text-neutral-500 tabular-nums">
                                <span>Granted {{ entitlement.grantedDateLabel }}</span>
                                <span>{{ entitlement.grantSourceLabel }}</span>
                            </div>
                            <Button
                                as-child
                                class="h-9 w-full rounded-[6px] bg-[#0F4C81] text-sm font-medium text-white hover:bg-[#0F4C81]/90"
                            >
                                <Link :href="entitlement.module.workspaceUrl">
                                    <span>Open Workspace</span>
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>
                    </article>
                </div>
            </section>

            <div
                v-if="revokedEntitlements.length > 0"
                class="rounded-lg border border-neutral-200 bg-white"
            >
                <div class="border-b border-neutral-200 px-6 py-4">
                    <h2 class="text-sm font-semibold text-neutral-900">Inactive &amp; Revoked Modules</h2>
                    <p class="mt-0.5 text-xs text-neutral-500">
                        Modules whose access was ended following a refund or administrative update
                    </p>
                </div>
                <div class="divide-y divide-neutral-100 px-6">
                    <div
                        v-for="revoked in revokedEntitlements"
                        :key="revoked.id"
                        class="flex flex-wrap items-center justify-between gap-4 py-3.5"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="revoked.module"
                                    class="font-mono text-xs font-medium text-neutral-500 tabular-nums"
                                >
                                    {{ revoked.module.code }}
                                </span>
                                <PortalStatusBadge status="revoked" label="Revoked" />
                            </div>
                            <p v-if="revoked.module" class="mt-1 text-sm font-semibold text-neutral-900">
                                {{ revoked.module.title }}
                            </p>
                            <p v-if="revoked.revocationReason" class="mt-0.5 text-xs text-neutral-600">
                                Reason: {{ revoked.revocationReason }}
                            </p>
                        </div>

                        <Button v-if="revoked.module" as-child variant="outline" size="sm" class="rounded-[6px]">
                            <Link :href="revoked.module.catalogueUrl">
                                View Module
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
