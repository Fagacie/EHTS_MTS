<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Upload, X } from 'lucide-vue-next';
import CertificateTemplate from '@/components/ds/CertificateTemplate.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface Props extends EtmsSharedProps {
    settings: {
        issuing_organisation: string;
        signatory_name: string;
        signatory_title: string;
        logo_path: string;
        signature_path: string;
    };
    signatureDataUri?: string | null;
}

const props = defineProps<Props>();
const form = reactive({
    issuing_organisation: props.settings.issuing_organisation,
    signatory_name: props.settings.signatory_name,
    signatory_title: props.settings.signatory_title,
});
const busy = ref(false);

const sampleTraineeName = ref('Amina binti Rahman');
const signature = ref<File | null>(null);
const signatureInputRef = ref<HTMLInputElement | null>(null);
const signatureObjectUrl = ref<string | null>(null);

const existingSignatureUrl = computed(() => {
    const p = props.settings.signature_path?.trim();
    if (!p) return null;
    if (p.startsWith('http://') || p.startsWith('https://') || p.startsWith('/')) return p;
    return `/storage/${p}`;
});

const previewSignatureSrc = computed(
    () => signatureObjectUrl.value ?? props.signatureDataUri ?? existingSignatureUrl.value,
);

function handleSignature(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (signatureObjectUrl.value) {
        URL.revokeObjectURL(signatureObjectUrl.value);
        signatureObjectUrl.value = null;
    }
    signature.value = file;
    if (file) {
        signatureObjectUrl.value = URL.createObjectURL(file);
    }
}

function clearSignatureSelection(): void {
    if (signatureObjectUrl.value) {
        URL.revokeObjectURL(signatureObjectUrl.value);
        signatureObjectUrl.value = null;
    }
    signature.value = null;
    if (signatureInputRef.value) {
        signatureInputRef.value.value = '';
    }
}

function submit(): void {
    busy.value = true;
    router.post(
        '/admin-vue/certificate-settings',
        { ...form, signature: signature.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                busy.value = false;
            },
        },
    );
}

onBeforeUnmount(() => {
    if (signatureObjectUrl.value) {
        URL.revokeObjectURL(signatureObjectUrl.value);
    }
});
</script>

<template>
    <Head title="Certificate Signatory Settings" />

    <AdminLayout breadcrumb="Certificate Settings">
        <div class="mx-auto max-w-[1160px] space-y-6">
            <!-- Header -->
            <div class="space-y-3">
                <Link
                    href="/admin-vue/certificates"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Certificates</span>
                </Link>

                <header>
                    <h1 class="type-page-title">Certificate signatory settings</h1>
                    <p class="mt-1 text-sm text-muted">
                        Edit signatory details below and preview how newly issued certificates will appear.
                    </p>
                </header>
            </div>

            <!-- Compact Configuration Form -->
            <form
                class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                @submit.prevent="submit"
            >
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label for="cert-org" class="block text-xs font-medium text-ink">
                            Issuing organisation <span class="text-danger-600">*</span>
                        </label>
                        <input
                            id="cert-org"
                            v-model="form.issuing_organisation"
                            type="text"
                            required
                            maxlength="200"
                            class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>

                    <div>
                        <label for="cert-signatory" class="block text-xs font-medium text-ink">
                            Authorised signatory name <span class="text-danger-600">*</span>
                        </label>
                        <input
                            id="cert-signatory"
                            v-model="form.signatory_name"
                            type="text"
                            required
                            maxlength="200"
                            class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>

                    <div>
                        <label for="cert-title" class="block text-xs font-medium text-ink">
                            Signatory designation <span class="text-danger-600">*</span>
                        </label>
                        <input
                            id="cert-title"
                            v-model="form.signatory_title"
                            type="text"
                            required
                            maxlength="200"
                            class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <input
                            ref="signatureInputRef"
                            type="file"
                            accept=".jpg,.jpeg,.svg,image/jpeg,image/svg+xml"
                            class="sr-only"
                            @change="handleSignature"
                        />
                        <button
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-sand-50 px-3.5 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-sand-100"
                            @click="signatureInputRef?.click()"
                        >
                            <Upload class="size-3.5 shrink-0" aria-hidden="true" />
                            <span>{{ signature?.name || 'Upload signature image (JPG / SVG)' }}</span>
                        </button>

                        <button
                            v-if="signature"
                            type="button"
                            class="inline-flex h-9 items-center gap-1 rounded-lg px-2.5 text-xs font-medium text-muted hover:text-danger-600"
                            @click="clearSignatureSelection"
                        >
                            <X class="size-3.5" aria-hidden="true" />
                            <span>Remove</span>
                        </button>
                    </div>

                    <button
                        type="submit"
                        :disabled="busy"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                    >
                        {{ busy ? 'Saving...' : 'Save configuration' }}
                    </button>
                </div>
            </form>

            <!-- Full-Width Live Certificate Template Preview -->
            <section class="space-y-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-navy-950">Live certificate template</h2>
                        <p class="text-xs text-muted">Changes to organisation, signatory name, designation, or signature appear instantly below.</p>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-muted">
                        <span>Preview name:</span>
                        <input
                            v-model="sampleTraineeName"
                            type="text"
                            class="h-8 w-52 rounded-lg border border-line bg-white px-2.5 text-xs font-medium text-navy-950 focus:border-primary-600 focus:outline-none"
                        />
                    </label>
                </div>

                <CertificateTemplate
                    certificate-number="EHT-2026-SAMPLE"
                    :issuing-organisation="form.issuing_organisation || 'Ecohydrotech Solutions Sdn. Bhd.'"
                    :trainee-name="sampleTraineeName || 'Trainee Name'"
                    title="Applied Urban Stormwater Modelling & MSMA Design"
                    code="EHT-SWM-2026"
                    record-type-label="Live Training Session"
                    completion-date-label="26 Nov 2026"
                    issued-date-label="26 Nov 2026"
                    :signatory-name="form.signatory_name || 'Authorised Signatory'"
                    :signatory-title="form.signatory_title"
                    logo-src="/images/ecohydrotech-logo.png"
                    :signature-src="previewSignatureSrc"
                />
            </section>
        </div>
    </AdminLayout>
</template>
