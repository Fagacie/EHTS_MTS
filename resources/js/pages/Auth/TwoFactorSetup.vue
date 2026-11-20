<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import type { AuthTwoFactorSetupProps } from '@/types/etms';

const props = defineProps<AuthTwoFactorSetupProps>();

const form = useForm({
    code: '',
});

function submitSetup(): void {
    form.post(props.confirmUrl);
}
</script>

<template>
    <Head title="Administrator MFA Setup" />

    <AuthLayout
        heading="Enable Administrator MFA"
        subheading="Set up time-based two-factor authentication before accessing the Administration Panel."
    >
        <div class="space-y-5">
            <div class="flex flex-col items-center rounded-xl border border-[#272d38]/15 bg-[#272d38]/[0.02] p-5 text-center">
                <img
                    :src="qrCodeDataUri"
                    alt="Authenticator QR Code"
                    class="h-44 w-44 rounded-xl border border-[#272d38]/15 bg-white p-2"
                >
                <p class="mt-3 text-[14px] leading-relaxed text-[#272d38]/70">
                    Scan this QR code in Google Authenticator, 1Password, or Microsoft Authenticator, or enter the key manually:
                </p>
                <span class="mt-2 inline-block rounded-lg border border-[#272d38]/15 bg-white px-3 py-1.5 text-[14px] font-bold text-[#272d38] tabular-nums">
                    {{ secret }}
                </span>
            </div>

            <form class="space-y-4" @submit.prevent="submitSetup">
                <div>
                    <Label for="code" class="mb-1.5">
                        6-Digit Authenticator Code <span aria-hidden="true">*</span>
                    </Label>
                    <Input
                        id="code"
                        v-model="form.code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        required
                        autofocus
                        placeholder="000000"
                        :aria-invalid="form.errors.code ? 'true' : undefined"
                        class="h-12 text-center text-[20px] font-bold tracking-widest tabular-nums"
                    />
                    <p
                        v-if="form.errors.code"
                        class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                    >
                        {{ form.errors.code }}
                    </p>
                </div>

                <Button type="submit" size="lg" class="w-full" :disabled="form.processing">
                    {{ form.processing ? 'Verifying...' : 'Verify & Activate MFA' }}
                </Button>
            </form>
        </div>
    </AuthLayout>
</template>
