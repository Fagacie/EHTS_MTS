<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Download,
    FileText,
    Lock,
    PlayCircle,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { CatalogueShowProps } from '@/types/etms';

defineProps<CatalogueShowProps>();
</script>

<template>
    <PublicLayout>
        <Head :title="module.title" />

        <!-- Top Breadcrumb & Title -->
        <section class="border-b border-line bg-sand-50/70">
            <div class="mx-auto max-w-7xl px-4 py-9 sm:px-6 sm:py-11 lg:px-8">
                <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-muted">
                    <Link :href="routes.catalogue" class="inline-flex items-center gap-1 transition-colors hover:text-navy-950">
                        <ArrowLeft class="size-3.5" aria-hidden="true" />
                        <span>Training Modules</span>
                    </Link>
                    <span>/</span>
                    <Link :href="module.categoryUrl" class="transition-colors hover:text-navy-950">
                        {{ module.categoryName }}
                    </Link>
                    <span>/</span>
                    <span class="tabular-nums-mono text-navy-900">{{ module.code }}</span>
                </div>

                <div class="mt-4 max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge tone="accent">{{ module.categoryName }}</StatusBadge>
                        <span class="tabular-nums-mono rounded-full bg-white px-3 py-0.5 text-xs font-semibold text-navy-900 ring-1 ring-line">
                            {{ module.code }}
                        </span>
                        <StatusBadge v-if="module.hasActiveEntitlement" tone="success" dot>
                            Active in Your Portal
                        </StatusBadge>
                    </div>

                    <h1 class="type-page-title mt-3.5">{{ module.title }}</h1>

                    <p v-if="module.summary" class="mt-2.5 text-base leading-relaxed text-muted">
                        {{ module.summary }}
                    </p>

                    <div class="mt-5 flex flex-wrap items-center gap-5 text-sm font-medium text-ink">
                        <span class="inline-flex items-center gap-2 tabular">
                            <FileText class="size-4 text-accent-600" aria-hidden="true" />
                            {{ materials.length }} {{ materials.length === 1 ? 'document' : 'documents' }}
                        </span>
                        <span class="inline-flex items-center gap-2 tabular">
                            <PlayCircle class="size-4 text-primary-600" aria-hidden="true" />
                            {{ videos.length }} {{ videos.length === 1 ? 'video' : 'videos' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2-Column Content -->
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-12 lg:items-start">
                <div class="space-y-10 lg:col-span-8">
                    <div
                        v-if="module.coverImageUrl"
                        class="overflow-hidden rounded-2xl bg-navy-900 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <img
                            :src="module.coverImageUrl"
                            :alt="module.title"
                            class="aspect-[16/9] w-full object-cover"
                        />
                    </div>

                    <div>
                        <h2 class="type-section-title text-navy-950">About this module</h2>
                        <div class="mt-3.5 whitespace-pre-line text-sm leading-7 text-ink/90 sm:text-[15px]">
                            {{ module.description }}
                        </div>
                    </div>

                    <div
                        v-if="module.learningObjectives.length > 0"
                        class="rounded-2xl border border-line bg-sand-50 p-6 sm:p-7"
                    >
                        <h2 class="type-section-title text-navy-950">Learning objectives</h2>
                        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                            <li
                                v-for="(objective, index) in module.learningObjectives"
                                :key="index"
                                class="flex items-start gap-2.5 text-sm leading-relaxed text-ink"
                            >
                                <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-success-600" aria-hidden="true" />
                                <span>{{ objective }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="videos.length > 0">
                        <h2 class="type-section-title text-navy-950">Video lessons</h2>
                        <div class="mt-4 divide-y divide-neutral-100 overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                            <div
                                v-for="(video, idx) in videos"
                                :key="video.id"
                                class="flex items-center justify-between gap-4 px-5 py-4"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="tabular mt-0.5 text-xs font-semibold text-muted">
                                        {{ String(idx + 1).padStart(2, '0') }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-navy-950">{{ video.title }}</p>
                                        <p v-if="video.description" class="mt-0.5 text-xs text-muted">
                                            {{ video.description }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2.5">
                                    <span class="tabular text-xs font-medium text-muted">
                                        {{ video.durationLabel }}
                                    </span>
                                    <Lock
                                        v-if="!module.hasActiveEntitlement"
                                        class="size-3.5 text-muted"
                                        aria-hidden="true"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="materials.length > 0">
                        <h2 class="type-section-title text-navy-950">Manuals &amp; downloadable files</h2>
                        <div class="mt-4 divide-y divide-neutral-100 overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                            <div
                                v-for="material in materials"
                                :key="material.id"
                                class="flex flex-wrap items-center justify-between gap-4 px-5 py-4"
                            >
                                <div class="flex items-start gap-3">
                                    <FileText class="mt-0.5 size-4 shrink-0 text-accent-600" aria-hidden="true" />
                                    <div>
                                        <p class="text-sm font-semibold text-navy-950">{{ material.title }}</p>
                                        <p class="mt-0.5 text-xs text-muted">
                                            {{ material.typeLabel }} &middot;
                                            <span class="tabular">{{ material.fileSizeLabel }}</span>
                                        </p>
                                    </div>
                                </div>

                                <AppButton
                                    v-if="material.canDownload && material.downloadUrl"
                                    :href="material.downloadUrl"
                                    external
                                    variant="outline"
                                    size="sm"
                                    pill
                                >
                                    <Download class="size-3.5" aria-hidden="true" />
                                    <span>Download</span>
                                </AppButton>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-sand-100 px-3 py-1 text-xs font-medium text-muted"
                                >
                                    <Lock class="size-3" aria-hidden="true" />
                                    <span>Enrolled access</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Access Card -->
                <aside class="lg:sticky lg:top-24 lg:col-span-4">
                    <div class="rounded-2xl bg-white p-6 shadow-elevated ring-1 ring-neutral-200/80">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="tabular text-3xl font-bold tracking-tight text-navy-950">
                                {{ module.independentPriceLabel ?? 'Cohort Only' }}
                            </p>
                            <StatusBadge v-if="module.isAvailableIndependently" tone="success">
                                Instant Access
                            </StatusBadge>
                        </div>

                        <p class="mt-2 text-xs leading-relaxed text-muted">
                            Includes portal access to published manuals and video lessons immediately after payment confirmation.
                        </p>

                        <div class="mt-6 space-y-2.5">
                            <template v-if="module.hasActiveEntitlement">
                                <AppButton :href="module.portalModuleUrl" variant="secondary" size="lg" class="w-full">
                                    <span>Open in Learner Portal</span>
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </AppButton>
                            </template>

                            <template v-else-if="module.isAvailableIndependently && module.checkoutUrl">
                                <AppButton
                                    v-if="module.canPurchaseAsCustomer"
                                    :href="module.checkoutUrl"
                                    variant="warm"
                                    size="lg"
                                    class="w-full"
                                >
                                    <span>Purchase Module</span>
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </AppButton>

                                <template v-else-if="!auth.user">
                                    <AppButton :href="routes.login" variant="warm" size="lg" class="w-full">
                                        <span>Sign in to Purchase</span>
                                        <ArrowRight class="size-4" aria-hidden="true" />
                                    </AppButton>
                                    <AppButton :href="routes.register" variant="outline" size="md" class="w-full">
                                        Create Learner Account
                                    </AppButton>
                                </template>

                                <p
                                    v-else
                                    class="rounded-xl bg-warning-50 px-3.5 py-2.5 text-xs font-medium text-warning-700"
                                >
                                    Module purchases require a learner (customer) account.
                                </p>
                            </template>

                            <template v-else>
                                <AppButton :href="routes.sessions" variant="primary" size="lg" class="w-full">
                                    <span>View Training Sessions</span>
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </AppButton>
                            </template>
                        </div>

                        <dl class="mt-6 space-y-3 border-t border-line pt-5 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Module code</dt>
                                <dd class="tabular-nums-mono font-semibold text-navy-950">{{ module.code }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Subject</dt>
                                <dd class="font-semibold text-navy-950">{{ module.categoryName }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Files included</dt>
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
        </section>
    </PublicLayout>
</template>
