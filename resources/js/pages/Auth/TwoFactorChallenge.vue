<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import type { AuthTwoFactorChallengeProps } from '@/types/etms';

const props = defineProps<AuthTwoFactorChallengeProps>();

const form = useForm({
    code: '',
    recovery_code: '',
});

const signingOut = ref(false);

function submitChallenge(): void {
    form.post(props.verifyUrl);
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
    <Head title="Administrator MFA Verification" />

    <AuthLayout
        heading="Two-Factor Verification"
        subheading="Enter the 6-digit code from your authenticator app or a recovery code to unlock the Administration Panel."
    >
        <form class="space-y-4" @submit.prevent="submitChallenge">
            <div>
                <Label for="code" class="mb-1.5">Authenticator Code</Label>
                <Input
                    id="code"
                    v-model="form.code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    autofocus
                    placeholder="6-digit code"
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

            <div class="relative py-1">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-[#272d38]/12" />
                </div>
                <div class="relative flex justify-center">
                    <span class="bg-white px-2.5 text-[13px] text-[#272d38]/65">
                        or use recovery code
                    </span>
                </div>
            </div>

            <div>
                <Label for="recovery_code" class="mb-1.5">Recovery Code</Label>
                <Input
                    id="recovery_code"
                    v-model="form.recovery_code"
                    name="recovery_code"
                    type="text"
                    placeholder="XXXX-XXXX"
                    :aria-invalid="form.errors.recovery_code ? 'true' : undefined"
                    class="tabular-nums"
                />
                <p
                    v-if="form.errors.recovery_code"
                    class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                >
                    {{ form.errors.recovery_code }}
                </p>
            </div>

            <Button type="submit" size="lg" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Verifying...' : 'Verify & Continue to Admin Panel' }}
            </Button>
        </form>

        <Button
            type="button"
            variant="ghost"
            size="sm"
            class="w-full"
            :disabled="signingOut"
            @click="signOut"
        >
            {{ signingOut ? 'Signing out...' : 'Cancel & Sign Out' }}
        </Button>
    </AuthLayout>
</template>
