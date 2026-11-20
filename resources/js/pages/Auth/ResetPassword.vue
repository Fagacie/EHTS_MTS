<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        heading="Set new password"
        subheading="Choose a strong password for your ETMS account."
    >
        <form class="space-y-4" @submit.prevent="submitReset">
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

            <div>
                <Label for="password" class="mb-1.5">
                    New password <span aria-hidden="true">*</span>
                </Label>
                <div class="relative">
                    <Input
                        id="password"
                        v-model="form.password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        :aria-invalid="form.errors.password ? 'true' : undefined"
                        class="pr-16"
                    />
                    <button
                        type="button"
                        :aria-pressed="showPassword"
                        :aria-label="showPassword ? 'Hide new password' : 'Show new password'"
                        class="absolute inset-y-0 right-0 flex min-w-14 items-center justify-center rounded-r-lg text-[13px] font-semibold text-[#272d38]/65 hover:text-[#272d38]"
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

            <div>
                <Label for="password_confirmation" class="mb-1.5">
                    Confirm new password <span aria-hidden="true">*</span>
                </Label>
                <div class="relative">
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        name="password_confirmation"
                        :type="showConfirm ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        class="pr-16"
                    />
                    <button
                        type="button"
                        :aria-pressed="showConfirm"
                        :aria-label="showConfirm ? 'Hide confirm new password' : 'Show confirm new password'"
                        class="absolute inset-y-0 right-0 flex min-w-14 items-center justify-center rounded-r-lg text-[13px] font-semibold text-[#272d38]/65 hover:text-[#272d38]"
                        @click="showConfirm = !showConfirm"
                    >
                        {{ showConfirm ? 'Hide' : 'Show' }}
                    </button>
                </div>
            </div>

            <Button type="submit" size="lg" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Resetting...' : 'Reset Password' }}
            </Button>
        </form>
    </AuthLayout>
</template>
