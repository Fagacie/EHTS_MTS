<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { pluralize } from '@/lib/utils';
import type { PortalModulesIndexProps } from '@/types/etms';

defineProps<PortalModulesIndexProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head title="My Learning" />

    <PortalLayout breadcrumb="My Learning">
        <div class="mx-auto max-w-[1440px] space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        My Learning
                    </h1>
                    <p class="mt-1 text-[13.5px] text-[#625D6D]">
                        Access your purchased self-paced modules, downloadable technical documents, video lessons, and session recordings.
                    </p>
                </div>

                <div v-if="activeEntitlements.length > 0" class="flex flex-wrap items-center gap-2.5">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="routes.portalRecordings">
                            Session Recordings
                        </Link>
                    </Button>
                    <Button as-child size="sm">
                        <Link :href="routes.catalogue">
                            Browse Training
                        </Link>
                    </Button>
                </div>
            </header>

            <Card
                v-if="activeEntitlements.length === 0"
                class="flex flex-col items-center justify-center px-6 py-12 text-center"
            >
                <p class="text-[16px] font-bold text-[#282338]">
                    No active training modules yet
                </p>
                <p class="mt-1.5 max-w-lg text-[13.5px] text-[#625D6D]">
                    Purchase any self-paced module from the training catalogue to unlock online learning materials and video lessons.
                </p>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <Button as-child>
                        <Link :href="routes.catalogue">
                            Browse Training Catalogue
                        </Link>
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="routes.portalRecordings">
                            Check Session Recordings
                        </Link>
                    </Button>
                </div>
            </Card>

            <section v-else aria-label="Active Module Workspaces">
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="entitlement in activeEntitlements"
                        :key="entitlement.id"
                        class="flex h-full flex-col justify-between"
                    >
                        <CardContent class="flex-1 p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <Badge variant="outline" class="tabular-nums">
                                    {{ entitlement.module.code }}
                                </Badge>
                                <PortalStatusBadge
                                    status="active"
                                    :label="entitlement.accessLabel"
                                />
                            </div>

                            <p
                                v-if="entitlement.module.categoryName"
                                class="mt-3 text-[12.5px] font-bold text-[#5942B5]"
                            >
                                {{ entitlement.module.categoryName }}
                            </p>

                            <h2 class="mt-1 text-[16px] font-bold leading-snug text-[#282338]">
                                <Link
                                    :href="entitlement.module.workspaceUrl"
                                    class="hover:text-[#5942B5] hover:underline underline-offset-4"
                                >
                                    {{ entitlement.module.title }}
                                </Link>
                            </h2>

                            <p
                                v-if="entitlement.module.summary"
                                class="mt-2 line-clamp-2 text-[13px] leading-relaxed text-[#403B4B]"
                            >
                                {{ entitlement.module.summary }}
                            </p>

                            <dl class="mt-4 grid grid-cols-2 gap-3 rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] p-3">
                                <div>
                                    <dt class="text-[12px] font-semibold text-[#625D6D]">Materials</dt>
                                    <dd class="mt-0.5 text-[14px] font-bold text-[#282338] tabular-nums">
                                        {{ entitlement.module.materialsCount }}
                                        {{ pluralize(entitlement.module.materialsCount, 'file') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-[12px] font-semibold text-[#625D6D]">Video Lessons</dt>
                                    <dd class="mt-0.5 text-[14px] font-bold text-[#282338] tabular-nums">
                                        {{ entitlement.module.videosCount }}
                                        {{ pluralize(entitlement.module.videosCount, 'lesson') }}
                                    </dd>
                                </div>
                            </dl>

                            <p class="mt-3 text-[12px] text-[#625D6D] tabular-nums">
                                Granted {{ entitlement.grantedDateLabel }} &middot; {{ entitlement.grantSourceLabel }}
                            </p>
                        </CardContent>

                        <CardFooter class="border-t border-[#EEEAF2] px-5 py-3.5">
                            <Button
                                as-child
                                class="w-full"
                            >
                                <Link :href="entitlement.module.workspaceUrl">
                                    <span>Open Learning Workspace</span>
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </section>

            <Card v-if="revokedEntitlements.length > 0">
                <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                    <CardTitle>Inactive &amp; Revoked Modules</CardTitle>
                    <CardDescription>
                        Modules whose access was ended following a refund or administrative update
                    </CardDescription>
                </CardHeader>
                <CardContent class="divide-y divide-[#EEEAF2] pt-4">
                    <div
                        v-for="revoked in revokedEntitlements"
                        :key="revoked.id"
                        class="flex flex-wrap items-center justify-between gap-4 py-3.5 first:pt-0 last:pb-0"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="revoked.module"
                                    class="text-[12.5px] font-bold text-[#625D6D] tabular-nums"
                                >
                                    {{ revoked.module.code }}
                                </span>
                                <PortalStatusBadge status="revoked" label="Revoked" />
                            </div>
                            <p
                                v-if="revoked.module"
                                class="mt-1 text-[14.5px] font-bold text-[#282338]"
                            >
                                {{ revoked.module.title }}
                            </p>
                            <p
                                v-if="revoked.revocationReason"
                                class="mt-0.5 text-[13px] text-[#403B4B]"
                            >
                                Reason: {{ revoked.revocationReason }}
                            </p>
                        </div>

                        <Button
                            v-if="revoked.module"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <Link :href="revoked.module.catalogueUrl">
                                View Programme
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PortalLayout>
</template>
