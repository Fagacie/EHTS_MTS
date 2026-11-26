<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import type { AuthLoginProps } from '@/types/etms';

const props = defineProps<AuthLoginProps>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

function submitLogin(): void {
    form.post(props.submitUrl, {
        onFinish: () => {
            form.reset('password');
        },
    });
}
</script>

<template>
    <Head title="Sign In" />

    <AuthLayout
        heading="Sign in"
        subheading="Access your learner, instructor, or administrator account."
    >
        <form class="space-y-4" @submit.prevent="submitLogin">
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

            <div>
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <label for="password" class="block text-sm font-medium text-navy-950">
                        Password
                    </label>
                    <Link
                        :href="forgotPasswordUrl"
                        class="text-xs font-semibold text-primary-600 transition-colors hover:text-primary-700"
                    >
                        Forgot password?
                    </Link>
                </div>

                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        :aria-invalid="form.errors.password ? 'true' : undefined"
                        class="h-11 w-full rounded-xl border border-line-strong/80 bg-white pr-11 pl-3.5 text-sm text-ink placeholder:text-muted/70 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        :class="form.errors.password ? 'border-danger-600 focus:border-danger-600 focus:ring-danger-600/15' : ''"
                    />
                    <button
                        type="button"
                        :aria-pressed="showPassword"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 flex w-11 cursor-pointer items-center justify-center rounded-r-xl text-muted transition-colors hover:text-navy-950 focus-visible:outline-2 focus-visible:outline-primary-600"
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

            <label class="flex cursor-pointer items-center gap-2.5 pt-0.5 text-sm text-muted select-none">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    name="remember"
                    class="size-4 rounded border-line-strong text-navy-950 accent-navy-950 focus:ring-primary-600"
                />
                <span>Keep me signed in on this device</span>
            </label>

            <div class="pt-1">
                <AppButton
                    type="submit"
                    variant="primary"
                    size="lg"
                    class="w-full"
                    :loading="form.processing"
                >
                    {{ form.processing ? 'Signing in...' : 'Sign in' }}
                </AppButton>
            </div>
        </form>

        <div class="border-t border-line pt-5 text-center text-sm text-muted">
            New here?
            <Link
                :href="registerUrl"
                class="ml-1 font-semibold text-primary-600 transition-colors hover:text-primary-700"
            >
                Create an account
            </Link>
        </div>
    </AuthLayout>
</template>
