<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Download } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardFooter } from '@/components/ui/card';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { EtmsSharedProps, PortalCertificateShowProps } from '@/types/etms';

const props = defineProps<PortalCertificateShowProps>();

const page = usePage<EtmsSharedProps>();
const { routes } = useRoutes();

const logoSrc = computed(() => props.logoDataUri || page.props.app.logoUrl);
</script>

<template>
    <Head :title="`Certificate ${certificate.certificateNumber}`" />

    <PortalLayout :breadcrumb="certificate.certificateNumber">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    :href="routes.portalCertificates"
                    class="inline-flex items-center gap-1.5 text-[13.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Back to Certificates</span>
                </Link>

                <PortalStatusBadge
                    :status="certificate.isIssued ? 'issued' : 'revoked'"
                    :label="certificate.isIssued ? 'Official Issued' : 'Revoked'"
                />
            </div>

            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        {{ certificate.title }}
                    </h1>
                    <p class="mt-1 text-[13.5px] text-[#625D6D] tabular-nums">
                        Certificate {{ certificate.certificateNumber }} &middot; {{ certificate.recordTypeLabel }}
                    </p>
                </div>

                <Button
                    v-if="certificate.isIssued && certificate.downloadUrl"
                    as="a"
                    :href="certificate.downloadUrl"
                    class="shrink-0"
                >
                    <Download class="h-4 w-4" aria-hidden="true" />
                    <span>Download Official PDF</span>
                </Button>
            </header>

            <Card
                v-if="certificate.isRevoked"
                role="status"
                class="border-[#ECC4CA] bg-[#FBECEE] p-4 shadow-none sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#A33C48]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#A33C48]">
                            This Certificate Has Been Revoked
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            {{
                                certificate.revocationReason ||
                                'This certificate has been invalidated by Ecohydrotech administration and is no longer valid for download or verification.'
                            }}
                        </p>
                        <p
                            v-if="certificate.revokedAtLabel"
                            class="mt-1 text-[12.5px] font-bold text-[#A33C48] tabular-nums"
                        >
                            Revoked on {{ certificate.revokedAtLabel }}
                        </p>
                    </div>
                </div>
            </Card>

            <Card class="overflow-hidden">
                <div class="bg-[#FAF9FC] p-4 sm:p-8">
                    <div class="rounded-xl border border-[#E5E1EB] bg-white p-6 shadow-xs sm:p-10">
                        <div class="border-b border-[#EEEAF2] pb-6 text-center">
                            <div v-if="logoSrc" class="mx-auto mb-4 flex justify-center">
                                <img
                                    :src="logoSrc"
                                    :alt="certificate.issuingOrganisation"
                                    class="h-12 w-auto object-contain"
                                >
                            </div>

                            <p class="text-[13.5px] font-bold text-[#5942B5]">
                                {{ certificate.issuingOrganisation }}
                            </p>
                            <h2 class="mt-2 text-[24px] font-extrabold text-[#282338] sm:text-[30px]">
                                Certificate of Completion
                            </h2>
                            <p class="mt-2 text-[13.5px] text-[#625D6D]">
                                This certifies that the participant named below has successfully completed the prescribed technical programme.
                            </p>
                        </div>

                        <div class="py-8 text-center">
                            <p class="text-[12.5px] font-bold uppercase tracking-wider text-[#625D6D]">Awarded to</p>
                            <p class="mt-2 text-[24px] font-extrabold text-[#282338] sm:text-[30px]">
                                {{ certificate.traineeName }}
                            </p>

                            <p class="mt-6 text-[12.5px] font-bold uppercase tracking-wider text-[#625D6D]">Programme Completed</p>
                            <p class="mt-1.5 text-[20px] font-bold text-[#282338]">
                                {{ certificate.title }}
                            </p>
                            <p
                                v-if="certificate.code"
                                class="mt-1 text-[13px] font-semibold text-[#625D6D] tabular-nums"
                            >
                                Code: {{ certificate.code }} &middot; {{ certificate.recordTypeLabel }}
                            </p>
                        </div>

                        <div class="grid gap-6 border-t border-[#EEEAF2] pt-6 sm:grid-cols-3">
                            <div>
                                <p class="text-[12.5px] font-semibold text-[#625D6D]">Completion Date</p>
                                <p class="mt-1 text-[14px] font-bold text-[#282338] tabular-nums">
                                    {{ certificate.completionDateLabel }}
                                </p>
                            </div>

                            <div>
                                <p class="text-[12.5px] font-semibold text-[#625D6D]">Issued On</p>
                                <p class="mt-1 text-[14px] font-bold text-[#282338] tabular-nums">
                                    {{ certificate.issuedDateLabel }}
                                </p>
                            </div>

                            <div>
                                <p class="text-[12.5px] font-semibold text-[#625D6D]">Authorised Signatory</p>
                                <p class="mt-1 text-[14px] font-bold text-[#282338]">
                                    {{ certificate.signatoryName }}
                                </p>
                                <p
                                    v-if="certificate.signatoryTitle"
                                    class="text-[12.5px] text-[#625D6D]"
                                >
                                    {{ certificate.signatoryTitle }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <CardFooter class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E5E1EB] bg-white px-6 py-4 text-[13px] text-[#625D6D]">
                    <div>
                        Certificate Reference:
                        <span class="font-bold text-[#282338] tabular-nums">{{ certificate.certificateNumber }}</span>
                    </div>
                    <div
                        v-if="certificate.isIssued && certificate.fileSizeLabel"
                        class="tabular-nums"
                    >
                        PDF Document Size: {{ certificate.fileSizeLabel }}
                    </div>
                </CardFooter>
            </Card>
        </div>
    </PortalLayout>
</template>
