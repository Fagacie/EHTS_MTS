<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Award, Download, Search } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { EtmsSharedProps, PortalCertificatesIndexProps } from '@/types/etms';

const props = defineProps<PortalCertificatesIndexProps>();

const page = usePage<EtmsSharedProps>();
const { routes } = useRoutes();
const searchQuery = ref('');

const filteredIssued = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.issuedCertificates;

    return props.issuedCertificates.filter(
        (c) =>
            c.title.toLowerCase().includes(q) ||
            c.certificateNumber.toLowerCase().includes(q) ||
            (c.code?.toLowerCase().includes(q) ?? false),
    );
});
</script>

<template>
    <Head title="Certificates" />

    <PortalLayout breadcrumb="Certificates">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Certificates</h1>
                    <p class="mt-1 text-sm text-muted">
                        Preview your official Ecohydrotech certificates and download printable PDF copies.
                    </p>
                </div>

                <div v-if="issuedCertificates.length > 0" class="relative sm:w-64">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                        aria-hidden="true"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search certificates..."
                        aria-label="Search certificates"
                        class="h-9.5 w-full rounded-xl border border-line-strong/80 bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                    />
                </div>
            </header>

            <!-- Empty State -->
            <EmptyState
                v-if="issuedCertificates.length === 0 && revokedCertificates.length === 0"
                :icon="Award"
                title="No certificates issued yet"
                description="Certificates appear here once your training completion is verified and issued by Ecohydrotech."
            >
                <template #action>
                    <div class="flex flex-wrap items-center justify-center gap-2.5">
                        <AppButton :href="routes.portalBookings" variant="outline" size="sm" pill>
                            View Live Sessions
                        </AppButton>
                        <AppButton :href="routes.catalogue" variant="warm" size="sm" pill>
                            Explore Modules
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <template v-else>
                <!-- Issued Certificates Grid -->
                <section v-if="issuedCertificates.length > 0" class="space-y-4">
                    <div
                        v-if="filteredIssued.length > 0"
                        class="grid gap-5 md:grid-cols-2"
                    >
                        <article
                            v-for="certificate in filteredIssued"
                            :key="certificate.id"
                            class="group flex flex-col justify-between overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80 transition-all duration-150 hover:-translate-y-0.5 hover:shadow-card-hover"
                        >
                            <!-- Colorful 4-Color Top Accent Ribbon -->
                            <div class="flex h-1.5 w-full" aria-hidden="true">
                                <span class="flex-1 bg-accent-600" />
                                <span class="flex-1 bg-primary-600" />
                                <span class="flex-1 bg-coral-500" />
                                <span class="flex-1 bg-warm-500" />
                            </div>

                            <div class="flex-1 p-5 sm:p-6">
                                <div class="flex items-center justify-between gap-3">
                                    <img
                                        :src="page.props.app.logoUrl"
                                        :alt="page.props.app.brandName"
                                        class="h-8 w-auto object-contain"
                                    />
                                    <StatusBadge tone="success" dot>
                                        Official Issued
                                    </StatusBadge>
                                </div>

                                <div class="mt-4">
                                    <p class="tabular-nums-mono text-xs font-semibold text-primary-600">
                                        {{ certificate.certificateNumber }}
                                    </p>
                                    <Link
                                        :href="certificate.viewUrl"
                                        class="mt-1 block text-base font-bold leading-snug text-navy-950 transition-colors group-hover:text-primary-600"
                                    >
                                        {{ certificate.title }}
                                    </Link>
                                    <p class="mt-1 text-xs text-muted">
                                        {{ certificate.recordTypeLabel }}
                                        <template v-if="certificate.code"> &middot; {{ certificate.code }}</template>
                                    </p>
                                </div>

                                <dl class="mt-5 grid grid-cols-2 gap-3 rounded-xl bg-sand-50 p-3.5 text-xs">
                                    <div>
                                        <dt class="text-muted">Completed</dt>
                                        <dd class="tabular mt-0.5 font-semibold text-navy-950">
                                            {{ certificate.completionDateLabel }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-muted">Issued</dt>
                                        <dd class="tabular mt-0.5 font-semibold text-navy-950">
                                            {{ certificate.issuedDateLabel }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2.5 border-t border-line bg-sand-50/40 px-5 py-3.5 sm:px-6">
                                <Link
                                    :href="certificate.viewUrl"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-navy-950 transition-colors hover:text-primary-600"
                                >
                                    <span>Preview certificate</span>
                                    <ArrowUpRight class="size-3.5" aria-hidden="true" />
                                </Link>

                                <AppButton
                                    v-if="certificate.downloadUrl"
                                    :href="certificate.downloadUrl"
                                    external
                                    variant="warm"
                                    size="sm"
                                    pill
                                >
                                    <Download class="size-3.5" aria-hidden="true" />
                                    <span>Download PDF</span>
                                </AppButton>
                            </div>
                        </article>
                    </div>

                    <EmptyState
                        v-else
                        :icon="Search"
                        title="No certificates match your search"
                        description="Try clearing your search keyword."
                    >
                        <template #action>
                            <AppButton variant="outline" size="sm" pill @click="searchQuery = ''">
                                Clear search
                            </AppButton>
                        </template>
                    </EmptyState>
                </section>

                <!-- Revoked Certificates -->
                <section v-if="revokedCertificates.length > 0" class="space-y-3">
                    <h2 class="text-sm font-bold text-navy-950">
                        Revoked certificates ({{ revokedCertificates.length }})
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div
                            v-for="certificate in revokedCertificates"
                            :key="certificate.id"
                            class="rounded-2xl bg-white p-5 shadow-card ring-1 ring-neutral-200/80"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="tabular-nums-mono text-xs font-semibold text-muted">
                                    {{ certificate.certificateNumber }}
                                </span>
                                <StatusBadge tone="danger" dot>Revoked</StatusBadge>
                            </div>
                            <p class="mt-2 text-sm font-bold text-navy-950">{{ certificate.title }}</p>
                            <p v-if="certificate.revocationReason" class="mt-2 rounded-xl bg-danger-50 px-3 py-2 text-xs text-danger-700">
                                {{ certificate.revocationReason }}
                            </p>
                            <div class="mt-4 flex items-center justify-between gap-2 border-t border-line pt-3">
                                <span class="tabular text-xs text-muted">
                                    Revoked {{ certificate.revokedAtLabel ?? '—' }}
                                </span>
                                <Link
                                    :href="certificate.viewUrl"
                                    class="text-xs font-semibold text-primary-600 hover:text-primary-700"
                                >
                                    View notice
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </PortalLayout>
</template>
