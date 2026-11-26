<script setup lang="ts">
export interface CertificateTemplateProps {
    certificateNumber: string;
    issuingOrganisation: string;
    traineeName: string;
    title: string;
    code?: string | null;
    recordTypeLabel: string;
    completionDateLabel: string;
    issuedDateLabel: string;
    signatoryName: string;
    signatoryTitle?: string | null;
    logoSrc?: string | null;
    signatureSrc?: string | null;
    revoked?: boolean;
}

defineProps<CertificateTemplateProps>();
</script>

<template>
    <div
        class="certificate-sheet relative overflow-hidden rounded-xl bg-[#0b2a5b] p-3.5 shadow-elevated sm:p-4"
    >
        <!-- Champagne Gold Frame -->
        <div class="border-2 border-[#c89d46] bg-[#fbf8ef] p-1.5">
            <!-- Warm Ivory Parchment Inner Canvas -->
            <div class="relative overflow-hidden border border-[#d7c7a3] bg-[#fdfbf4] px-6 py-8 sm:px-14 sm:py-10">
                <!-- Architectural Top-Left Corner Ribbon -->
                <svg
                    class="pointer-events-none absolute top-0 left-0 size-28 sm:size-44"
                    viewBox="0 0 190 190"
                    aria-hidden="true"
                >
                    <polygon points="0,0 160,0 0,160" fill="#0b2a5b" />
                    <polygon points="0,0 124,0 0,124" fill="#0f62a4" opacity="0.55" />
                    <polygon points="154,0 170,0 0,170 0,154" fill="#2d8a3e" />
                    <polygon points="172,0 177,0 0,177 0,172" fill="#c89d46" />
                </svg>

                <!-- Architectural Bottom-Right Corner Ribbon -->
                <svg
                    class="pointer-events-none absolute right-0 bottom-0 size-28 sm:size-44"
                    viewBox="0 0 190 190"
                    aria-hidden="true"
                >
                    <polygon points="190,190 30,190 190,30" fill="#0b2a5b" />
                    <polygon points="190,190 66,190 190,66" fill="#0f62a4" opacity="0.55" />
                    <polygon points="36,190 20,190 190,20 190,36" fill="#2d8a3e" />
                    <polygon points="18,190 13,190 190,13 190,18" fill="#c89d46" />
                </svg>

                <!-- Subtle Organisation Name Watermark -->
                <div
                    class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden px-12 select-none"
                    aria-hidden="true"
                >
                    <p class="font-serif text-2xl font-semibold tracking-[0.26em] text-[#0b2a5b] uppercase opacity-[0.035] sm:text-4xl">
                        {{ issuingOrganisation }}
                    </p>
                </div>

                <!-- Revoked Watermark Overlay -->
                <div
                    v-if="revoked"
                    class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center bg-[#fdfbf4]/70"
                >
                    <span class="-rotate-12 rounded-xl border-4 border-danger-600 px-8 py-2 text-3xl font-extrabold tracking-[0.2em] text-danger-600 uppercase">
                        Revoked
                    </span>
                </div>

                <!-- Main Certificate Content -->
                <div class="relative z-10 text-center">
                    <!-- Centered Company Logo -->
                    <div class="flex justify-center">
                        <img
                            v-if="logoSrc"
                            :src="logoSrc"
                            :alt="issuingOrganisation"
                            class="h-20 w-auto object-contain sm:h-24"
                        />
                        <span v-else class="font-serif text-lg font-bold text-[#0b2a5b]">
                            {{ issuingOrganisation }}
                        </span>
                    </div>

                    <!-- Classic Serif Heading -->
                    <h2 class="mt-3 font-serif text-3xl font-bold tracking-[0.18em] text-[#0b2a5b] uppercase sm:text-4xl">
                        Certificate
                    </h2>

                    <div class="mx-auto mt-2 flex items-center justify-center gap-3.5">
                        <span class="h-[1.5px] w-14 bg-[#c89d46] sm:w-20" aria-hidden="true" />
                        <p class="text-xs font-bold tracking-[0.34em] text-[#0f62a4] uppercase">
                            of Completion
                        </p>
                        <span class="h-[1.5px] w-14 bg-[#c89d46] sm:w-20" aria-hidden="true" />
                    </div>

                    <!-- Recipient & Programme -->
                    <p class="mt-6 font-serif text-sm italic text-[#4b5d73]">
                        This is proudly presented to
                    </p>

                    <p class="mx-auto mt-2 max-w-2xl border-b-[1.5px] border-[#c89d46] pb-2.5 font-serif text-2xl font-bold tracking-tight text-[#0b2a5b] sm:text-4xl">
                        {{ traineeName }}
                    </p>

                    <p class="mt-5 font-serif text-sm italic text-[#4b5d73]">
                        for successfully completing the professional training programme
                    </p>

                    <p class="mx-auto mt-2 max-w-2xl text-lg font-bold leading-snug text-[#0f62a4] sm:text-xl">
                        {{ title }}
                    </p>

                    <p class="mt-2 text-[11px] font-bold tracking-[0.16em] text-[#2d8a3e] uppercase">
                        {{ recordTypeLabel }}
                        <template v-if="code"> &middot; {{ code }}</template>
                    </p>

                    <!-- Executive 3-Column Footer with Scalloped Gold Seal -->
                    <div class="mt-10 grid gap-6 sm:grid-cols-3 sm:items-end">
                        <!-- Date & Reference -->
                        <div class="text-left">
                            <p class="tabular text-sm font-bold text-[#0b2a5b]">
                                {{ completionDateLabel }}
                            </p>
                            <div class="mt-1 max-w-52 border-t-[1.5px] border-[#0b2a5b] pt-1.5">
                                <p class="text-[10px] font-bold tracking-[0.13em] text-[#64748b] uppercase">
                                    Date of Completion
                                </p>
                            </div>
                            <p class="tabular mt-2 text-[11px] text-[#64748b]">
                                Certificate No:
                                <strong class="font-semibold text-[#0b2a5b]">{{ certificateNumber }}</strong>
                            </p>
                        </div>

                        <!-- 24-Point Scalloped Gold Foil Ribbon Seal -->
                        <div class="flex justify-center">
                            <svg
                                width="92"
                                height="104"
                                viewBox="0 0 92 104"
                                class="shrink-0"
                                aria-hidden="true"
                            >
                                <polygon points="31,64 20,101 34,92 43,101 45,68" fill="#0b2a5b" />
                                <polygon points="61,64 72,101 58,92 49,101 47,68" fill="#2d8a3e" />
                                <polygon points="46,4 52,11 61,8 64,17 73,17 73,26 81,30 78,39 84,46 78,53 81,62 73,66 73,75 64,75 61,84 52,81 46,88 40,81 31,84 28,75 19,75 19,66 11,62 14,53 8,46 14,39 11,30 19,26 19,17 28,17 31,8 40,11" fill="#c89d46" />
                                <circle cx="46" cy="46" r="29" fill="#fdfbf4" stroke="#0b2a5b" stroke-width="1.8" />
                                <circle cx="46" cy="46" r="24" fill="none" stroke="#c89d46" stroke-width="1" stroke-dasharray="2 2" />
                                <text x="46" y="40" text-anchor="middle" font-size="7.5" font-weight="700" fill="#2d8a3e" letter-spacing="1.4">VERIFIED</text>
                                <text x="46" y="51" text-anchor="middle" font-family="Georgia, serif" font-size="9.5" font-weight="700" fill="#0b2a5b">OFFICIAL</text>
                                <text x="46" y="60" text-anchor="middle" font-size="6.5" font-weight="700" fill="#b4832c" letter-spacing="1.2">SEAL</text>
                            </svg>
                        </div>

                        <!-- Authorised Signatory -->
                        <div class="flex flex-col items-center sm:items-end">
                            <div class="w-56 text-center">
                                <div class="flex h-12 items-end justify-center pb-1">
                                    <img
                                        v-if="signatureSrc"
                                        :src="signatureSrc"
                                        alt="Authorised signature"
                                        class="max-h-11 max-w-44 object-contain mix-blend-multiply"
                                    />
                                </div>
                                <div class="border-t-[1.5px] border-[#0b2a5b] pt-1.5">
                                    <p class="text-sm font-bold text-[#0b2a5b]">
                                        {{ signatoryName }}
                                    </p>
                                    <p v-if="signatoryTitle" class="mt-0.5 text-[11px] text-[#55657a]">
                                        {{ signatoryTitle }}
                                    </p>
                                </div>
                                <p class="tabular mt-2 text-[11px] text-[#64748b]">
                                    Issued {{ issuedDateLabel }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
