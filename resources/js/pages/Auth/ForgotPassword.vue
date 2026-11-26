<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppButton from '@/components/ds/AppButton.vue';
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
        subheading="Enter your email address and we'll send you a reset link."
    >
        <form class="space-y-4" @submit.prevent="submitLinkRequest">
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-navy-950">
                    Email address
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    name="email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="name@company.com"
                    :aria-invalid="form.errors.email ? 'true' : undefined"
                    class="h-11 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/70 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                    :class="form.errors.email ? 'border-danger-600 focus:border-danger-600 focus:ring-danger-600/15' : ''"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-xs font-semibold text-danger-600">
                    {{ form.errors.email }}
                </p>
            </div>

            <div class="pt-1">
                <AppButton
                    type="submit"
                    variant="primary"
                    size="lg"
                    class="w-full"
                    :loading="form.processing"
                >
                    {{ form.processing ? 'Sending...' : 'Send reset link' }}
                </AppButton>
            </div>
        </form>

        <div class="border-t border-line pt-5 text-center text-sm text-muted">
            Remembered your password?
            <Link
                :href="loginUrl"
                class="ml-1 font-semibold text-primary-600 transition-colors hover:text-primary-700"
            >
                Back to sign in
            </Link>
        </div>
    </AuthLayout>
</template>
