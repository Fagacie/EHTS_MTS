<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
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
        heading="Sign in to ETMS"
        subheading="Access your Trainee, Instructor, or Administrator account."
    >
        <form class="space-y-4" @submit.prevent="submitLogin">
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
                    placeholder="name@company.com.my"
                    :aria-invalid="form.errors.email ? 'true' : undefined"
                    :aria-describedby="form.errors.email ? 'login-email-error' : undefined"
                />
                <p
                    v-if="form.errors.email"
                    id="login-email-error"
                    class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                >
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <div class="mb-1.5 flex items-center justify-between gap-2">
                    <Label for="password">
                        Password <span aria-hidden="true">*</span>
                    </Label>
                    <Link
                        :href="forgotPasswordUrl"
                        class="text-[13px] font-semibold text-[#272d38] hover:underline underline-offset-4"
                    >
                        Forgot password?
                    </Link>
                </div>

                <div class="relative">
                    <Input
                        id="password"
                        v-model="form.password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        :aria-invalid="form.errors.password ? 'true' : undefined"
                        class="pr-16"
                    />
                    <button
                        type="button"
                        :aria-pressed="showPassword"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 flex min-w-14 items-center justify-center rounded-r-lg text-[13px] font-semibold text-[#272d38]/65 transition-colors hover:text-[#272d38] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                        @click="showPassword = !showPassword"
                    >
                        {{ showPassword ? 'Hide' : 'Show' }}
                    </button>
                </div>

                <p
                    v-if="form.errors.password"
                    class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                >
                    {{ form.errors.password }}
                </p>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 text-[14px] text-[#272d38]/75">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded accent-[#272d38]"
                >
                <span>Keep me signed in on this device</span>
            </label>

            <Button type="submit" size="lg" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Signing in...' : 'Sign In' }}
            </Button>
        </form>

        <Separator />

        <p class="text-center text-[14px] text-[#272d38]/65">
            New to Ecohydrotech Training?
            <Link
                :href="registerUrl"
                class="ml-1 font-semibold text-[#272d38] hover:underline underline-offset-4"
            >
                Create a customer account
            </Link>
        </p>
    </AuthLayout>
</template>
