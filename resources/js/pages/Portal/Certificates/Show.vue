<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import {
    AlertCircle,
    ArrowLeft,
    Check,
    Copy,
    Download,
    Maximize2,
    Printer,
    X,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import CertificateTemplate from '@/components/ds/CertificateTemplate.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { EtmsSharedProps, PortalCertificateShowProps } from '@/types/etms';

const props = defineProps<PortalCertificateShowProps>();

const page = usePage<EtmsSharedProps>();
const { routes } = useRoutes();

const copied = ref(false);
const fullscreenOpen = ref(false);

const logoSrc = computed(() => props.logoDataUri || page.props.app.logoUrl);

async function copyCertificateNumber(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.certificate.certificateNumber);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        // Ignore clipboard unavailability
    }
}

function printCertificate(): void {
    window.print();
}
</script>

<template>
    <Head :title="`Certificate ${certificate.certificateNumber}`" />

    <PortalLayout :breadcrumb="certificate.certificateNumber">
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Top Navigation & Actions Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="routes.portalCertificates"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted transition-colors hover:text-navy-950"
                    >
                        <ArrowLeft class="size-3.5" aria-hidden="true" />
                        <span>Back to Certificates</span>
                    </Link>

                    <StatusBadge :tone="certificate.isIssued ? 'success' : 'danger'" dot>
                        {{ certificate.isIssued ? 'Official Issued' : 'Revoked' }}
                    </StatusBadge>
                </div>

                <!-- Interactive Vue Action Controls -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-full border border-line bg-white px-3.5 text-xs font-semibold text-navy-950 shadow-xs transition-colors hover:bg-sand-100"
                        @click="copyCertificateNumber"
                    >
                        <Check v-if="copied" class="size-3.5 text-success-600" aria-hidden="true" />
                        <Copy v-else class="size-3.5 text-muted" aria-hidden="true" />
                        <span>{{ copied ? 'Copied ID' : 'Copy ID' }}</span>
                    </button>

                    <DialogRoot v-model:open="fullscreenOpen">
                        <DialogTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-full border border-line bg-white px-3.5 text-xs font-semibold text-navy-950 shadow-xs transition-colors hover:bg-sand-100"
                            >
                                <Maximize2 class="size-3.5 text-muted" aria-hidden="true" />
                                <span>Fullscreen</span>
                            </button>
                        </DialogTrigger>

                        <DialogPortal>
                            <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/70 backdrop-blur-xs" />
                            <DialogContent
                                class="fixed inset-4 z-50 m-auto flex max-h-[92vh] max-w-6xl flex-col overflow-auto rounded-2xl bg-sand-50 p-4 shadow-floating focus:outline-none sm:p-6"
                            >
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <DialogTitle class="text-sm font-bold text-navy-950">
                                        {{ certificate.title }} &middot; {{ certificate.certificateNumber }}
                                    </DialogTitle>
                                    <DialogClose
                                        aria-label="Close fullscreen preview"
                                        class="inline-flex size-8 cursor-pointer items-center justify-center rounded-full bg-white text-muted shadow-xs ring-1 ring-line hover:text-navy-950"
                                    >
                                        <X class="size-4" aria-hidden="true" />
                                    </DialogClose>
                                </div>

                                <CertificateTemplate
                                    :certificate-number="certificate.certificateNumber"
                                    :issuing-organisation="certificate.issuingOrganisation"
                                    :trainee-name="certificate.traineeName"
                                    :title="certificate.title"
                                    :code="certificate.code"
                                    :record-type-label="certificate.recordTypeLabel"
                                    :completion-date-label="certificate.completionDateLabel"
                                    :issued-date-label="certificate.issuedDateLabel"
                                    :signatory-name="certificate.signatoryName"
                                    :signatory-title="certificate.signatoryTitle"
                                    :logo-src="logoSrc"
                                    :signature-src="signatureDataUri"
                                    :revoked="certificate.isRevoked"
                                />
                            </DialogContent>
                        </DialogPortal>
                    </DialogRoot>

                    <button
                        v-if="certificate.isIssued"
                        type="button"
                        class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-full border border-line bg-white px-3.5 text-xs font-semibold text-navy-950 shadow-xs transition-colors hover:bg-sand-100"
                        @click="printCertificate"
                    >
                        <Printer class="size-3.5 text-muted" aria-hidden="true" />
                        <span>Print</span>
                    </button>

                    <AppButton
                        v-if="certificate.isIssued && certificate.downloadUrl"
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
            </div>

            <!-- Revoked Alert -->
            <div
                v-if="certificate.isRevoked"
                role="status"
                class="flex items-start gap-3 rounded-2xl bg-danger-50 p-4 ring-1 ring-inset ring-danger-600/20 sm:px-5"
            >
                <AlertCircle class="mt-0.5 size-5 shrink-0 text-danger-600" aria-hidden="true" />
                <div>
                    <p class="text-sm font-bold text-danger-700">This certificate has been revoked</p>
                    <p class="mt-0.5 text-xs text-danger-700/90">
                        {{ certificate.revocationReason || 'This certificate is no longer valid for download or verification.' }}
                    </p>
                </div>
            </div>

            <!-- Live Colorful Certificate Preview -->
            <CertificateTemplate
                :certificate-number="certificate.certificateNumber"
                :issuing-organisation="certificate.issuingOrganisation"
                :trainee-name="certificate.traineeName"
                :title="certificate.title"
                :code="certificate.code"
                :record-type-label="certificate.recordTypeLabel"
                :completion-date-label="certificate.completionDateLabel"
                :issued-date-label="certificate.issuedDateLabel"
                :signatory-name="certificate.signatoryName"
                :signatory-title="certificate.signatoryTitle"
                :logo-src="logoSrc"
                :signature-src="signatureDataUri"
                :revoked="certificate.isRevoked"
            />

            <!-- Subtle Metadata Footer -->
            <div class="flex flex-wrap items-center justify-between gap-2 px-1 text-xs text-muted">
                <span>
                    Certificate Reference:
                    <strong class="tabular-nums-mono text-navy-950">{{ certificate.certificateNumber }}</strong>
                </span>
                <span v-if="certificate.isIssued && certificate.fileSizeLabel" class="tabular">
                    Official PDF Size: {{ certificate.fileSizeLabel }}
                </span>
            </div>
        </div>
    </PortalLayout>
</template>
