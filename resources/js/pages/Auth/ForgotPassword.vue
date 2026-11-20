<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AuthLayout from '@/layouts/AuthLayout.vue';
import type { AuthForgotPasswordProps } from '@/types/etms';

const props = defineProps<AuthForgotPasswordProps>();

const form = useForm({
    email: '',
});

function submitLinkRequest(): void {
    form.post(props.submitUrl);
}
</script>

<template>
    <Head title="Forgot Password" />

    <AuthLayout
        heading="Reset your password"
        subheading="Enter your registered email address to receive a password reset link."
    >
        <form class="space-y-4" @submit.prevent="submitLinkRequest">
            <div>
                <Label for="email" class="mb-1.5">
                    Email address <span aria-hidden="true">*</span>
                </Label>
                <Input
                    id="email"
                    v-model="form.email"
                    name="email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    :aria-invalid="form.errors.email ? 'true' : undefined"
                />
                <p
                    v-if="form.errors.email"
                    class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                >
                    {{ form.errors.email }}
                </p>
            </div>

            <Button type="submit" size="lg" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Sending...' : 'Email Password Reset Link' }}
            </Button>
        </form>

        <Separator />

        <p class="text-center text-[14px] text-[#272d38]/65">
            Remembered your password?
            <Link
                :href="loginUrl"
                class="ml-1 font-semibold text-[#272d38] hover:underline underline-offset-4"
            >
                Back to sign in
            </Link>
        </p>
    </AuthLayout>
</template>
