<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalCertificatesIndexProps } from '@/types/etms';

defineProps<PortalCertificatesIndexProps>();

const { routes } = useRoutes();
</script>

<template>
    <Head title="Certificates" />

    <PortalLayout breadcrumb="Certificates">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header>
                <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                    Certificates
                </h1>
                <p class="mt-1 text-[13.5px] text-[#625D6D]">
                    View your verified Ecohydrotech training certificates and download official PDF copies.
                </p>
            </header>

            <Card
                v-if="issuedCertificates.length === 0 && revokedCertificates.length === 0"
                class="flex flex-col items-center justify-center px-6 py-12 text-center"
            >
                <p class="text-[16px] font-bold text-[#282338]">
                    No certificates issued yet
                </p>
                <p class="mt-1.5 max-w-lg text-[13.5px] text-[#625D6D]">
                    Certificates become available here once your session attendance is verified or your eligible module training record is issued by Ecohydrotech administration.
                </p>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <Button as-child variant="outline">
                        <Link :href="routes.portalBookings">
                            View My Bookings
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="routes.catalogue">
                            Browse Training
                        </Link>
                    </Button>
                </div>
            </Card>

            <template v-else>
                <section class="space-y-4">
                    <h2 class="text-[16px] font-bold text-[#282338]">
                        Issued Certificates
                        <span class="ml-1 text-[13.5px] font-semibold text-[#625D6D] tabular-nums">
                            ({{ issuedCertificates.length }})
                        </span>
                    </h2>

                    <Card v-if="issuedCertificates.length === 0" class="p-6">
                        <p class="text-[13.5px] text-[#625D6D]">
                            You do not currently have any active issued certificates.
                        </p>
                    </Card>

                    <div v-else class="grid gap-4 md:grid-cols-2">
                        <Card
                            v-for="certificate in issuedCertificates"
                            :key="certificate.id"
                            class="flex h-full flex-col justify-between"
                        >
                            <CardContent class="space-y-4 p-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <Badge variant="outline">
                                        {{ certificate.recordTypeLabel }}
                                    </Badge>
                                    <PortalStatusBadge status="issued" label="Official Issued" />
                                </div>

                                <div>
                                    <p class="text-[12.5px] font-bold text-[#5942B5] tabular-nums">
                                        {{ certificate.certificateNumber }}
                                    </p>
                                    <h3 class="mt-1 text-[16px] font-bold leading-snug text-[#282338]">
                                        {{ certificate.title }}
                                    </h3>
                                    <p
                                        v-if="certificate.code"
                                        class="mt-1 text-[12.5px] text-[#625D6D] tabular-nums"
                                    >
                                        Programme Code: {{ certificate.code }}
                                    </p>
                                </div>

                                <dl class="grid gap-3 border-t border-[#EEEAF2] pt-3.5 text-[13px] sm:grid-cols-2">
                                    <div>
                                        <dt class="text-[#625D6D]">Completion Date</dt>
                                        <dd class="mt-0.5 font-bold text-[#282338] tabular-nums">
                                            {{ certificate.completionDateLabel }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-[#625D6D]">Issued Date</dt>
                                        <dd class="mt-0.5 font-bold text-[#282338] tabular-nums">
                                            {{ certificate.issuedDateLabel }}
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>

                            <CardFooter class="flex flex-wrap items-center gap-2.5 border-t border-[#EEEAF2] px-5 py-3.5">
                                <Button as-child variant="outline" size="sm">
                                    <Link :href="certificate.viewUrl">
                                        Preview Record
                                    </Link>
                                </Button>
                                <Button
                                    v-if="certificate.downloadUrl"
                                    as="a"
                                    :href="certificate.downloadUrl"
                                    size="sm"
                                >
                                    <Download class="h-3.5 w-3.5" aria-hidden="true" />
                                    <span>Download Official PDF</span>
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                </section>

                <section v-if="revokedCertificates.length > 0" class="space-y-4">
                    <h2 class="text-[16px] font-bold text-[#282338]">
                        Revoked Certificates
                        <span class="ml-1 text-[13.5px] font-semibold text-[#625D6D] tabular-nums">
                            ({{ revokedCertificates.length }})
                        </span>
                    </h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <Card
                            v-for="certificate in revokedCertificates"
                            :key="certificate.id"
                            class="flex h-full flex-col justify-between"
                        >
                            <CardContent class="space-y-4 p-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-[12.5px] font-semibold text-[#625D6D] tabular-nums">
                                        {{ certificate.certificateNumber }}
                                    </span>
                                    <PortalStatusBadge status="revoked" label="Revoked" />
                                </div>

                                <div>
                                    <h3 class="text-[16px] font-bold leading-snug text-[#282338]">
                                        {{ certificate.title }}
                                    </h3>
                                    <p
                                        v-if="certificate.revocationReason"
                                        class="mt-2 rounded-lg border border-[#ECC4CA] bg-[#FBECEE] px-3 py-2 text-[12.5px] text-[#A33C48]"
                                    >
                                        {{ certificate.revocationReason }}
                                    </p>
                                </div>

                                <p class="text-[12.5px] text-[#625D6D] tabular-nums">
                                    Revoked on {{ certificate.revokedAtLabel ?? '—' }}
                                </p>
                            </CardContent>

                            <CardFooter class="border-t border-[#EEEAF2] px-5 py-3.5">
                                <Button as-child variant="outline" size="sm">
                                    <Link :href="certificate.viewUrl">
                                        View Record Notice
                                    </Link>
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>
                </section>
            </template>
        </div>
    </PortalLayout>
</template>
