<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import type { AuthResetPasswordProps } from '@/types/etms';

const props = defineProps<AuthResetPasswordProps>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirm = ref(false);

function submitReset(): void {
    form.post(props.submitUrl, {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <Head title="Set New Password" />

    <AuthLayout
        heading="Set a new password"
        subheading="Choose a strong password for your account."
    >
        <form class="space-y-4" @submit.prevent="submitReset">
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
                    autocomplete="email"
                    :aria-invalid="form.errors.email ? 'true' : undefined"
                    class="h-11 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                    :class="form.errors.email ? 'border-danger-600 focus:border-danger-600 focus:ring-danger-600/15' : ''"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-xs font-semibold text-danger-600">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-navy-950">
                    New password
                </label>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="Minimum 8 characters"
                        :aria-invalid="form.errors.password ? 'true' : undefined"
                        class="h-11 w-full rounded-xl border border-line-strong/80 bg-white pr-11 pl-3.5 text-sm text-ink placeholder:text-muted/70 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        :class="form.errors.password ? 'border-danger-600 focus:border-danger-600 focus:ring-danger-600/15' : ''"
                    />
                    <button
                        type="button"
                        :aria-pressed="showPassword"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 flex w-11 cursor-pointer items-center justify-center rounded-r-xl text-muted transition-colors hover:text-navy-950"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="size-4" aria-hidden="true" />
                        <Eye v-else class="size-4" aria-hidden="true" />
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-1.5 text-xs font-semibold text-danger-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-navy-950">
                    Confirm new password
                </label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        name="password_confirmation"
                        :type="showConfirm ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="Re-enter password"
                        class="h-11 w-full rounded-xl border border-line-strong/80 bg-white pr-11 pl-3.5 text-sm text-ink placeholder:text-muted/70 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                    />
                    <button
                        type="button"
                        :aria-pressed="showConfirm"
                        :aria-label="showConfirm ? 'Hide confirm password' : 'Show confirm password'"
                        class="absolute inset-y-0 right-0 flex w-11 cursor-pointer items-center justify-center rounded-r-xl text-muted transition-colors hover:text-navy-950"
                        @click="showConfirm = !showConfirm"
                    >
                        <EyeOff v-if="showConfirm" class="size-4" aria-hidden="true" />
                        <Eye v-else class="size-4" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <div class="pt-1">
                <AppButton
                    type="submit"
                    variant="primary"
                    size="lg"
                    class="w-full"
                    :loading="form.processing"
                >
                    {{ form.processing ? 'Resetting...' : 'Reset password' }}
                </AppButton>
            </div>
        </form>
    </AuthLayout>
</template>
