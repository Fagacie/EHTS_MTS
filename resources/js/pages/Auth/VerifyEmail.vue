<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import type { AuthVerifyEmailProps } from '@/types/etms';

const props = defineProps<AuthVerifyEmailProps>();

const resending = ref(false);
const signingOut = ref(false);

function resendEmail(): void {
    if (resending.value) {
        return;
    }

    resending.value = true;
    router.post(
        props.resendUrl,
        {},
        {
            onFinish: () => {
                resending.value = false;
            },
        },
    );
}

function signOut(): void {
    if (signingOut.value) {
        return;
    }

    signingOut.value = true;
    router.post(
        props.logoutUrl,
        {},
        {
            onFinish: () => {
                signingOut.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Verify Email" />

    <AuthLayout
        heading="Verify your email address"
        subheading="Before accessing your ETMS portal, please confirm your email using the verification link we sent."
    >
        <div class="space-y-4">
            <p class="rounded-xl border border-[#272d38]/15 bg-[#272d38]/[0.02] px-4 py-3 text-[14px] text-[#272d38]/75">
                Signed in as <span class="font-bold text-[#272d38]">{{ email }}</span>.
            </p>

            <Button
                type="button"
                size="lg"
                class="w-full"
                :disabled="resending"
                @click="resendEmail"
            >
                {{ resending ? 'Sending...' : 'Resend Verification Email' }}
            </Button>

            <Button
                type="button"
                variant="outline"
                class="w-full"
                :disabled="signingOut"
                @click="signOut"
            >
                {{ signingOut ? 'Signing out...' : 'Sign Out' }}
            </Button>
        </div>
    </AuthLayout>
</template>
