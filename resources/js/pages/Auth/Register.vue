<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import AvatarUpload from '@/components/ds/AvatarUpload.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import type { AuthRegisterProps } from '@/types/etms';

const props = defineProps<AuthRegisterProps>();

const form = useForm<{
    name: string;
    email: string;
    phone: string;
    photo: File | null;
    password: string;
    password_confirmation: string;
}>({
    name: '',
    email: '',
    phone: '',
    photo: null,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirm = ref(false);

function submitRegistration(): void {
    form.post(props.submitUrl, {
        forceFormData: true,
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <Head title="Create Account" />

    <AuthLayout
        heading="Create your account"
        subheading="Register to book live sessions, access modules, and download certificates."
        :wide="true"
    >
        <form class="space-y-5" @submit.prevent="submitRegistration">
            <!-- Optional Circular Profile Photo Upload -->
            <div class="rounded-2xl border border-line bg-sand-50/70 p-4">
                <AvatarUpload
                    id="register-photo"
                    v-model="form.photo"
                    label="Profile photo"
                    hint="Optional · JPG, PNG, or WebP up to 5 MB"
                    :error="form.errors.photo"
                />
            </div>

            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-navy-950">
                    Full name
                </label>
                <input
                    id="name"
                    v-model="form.name"
                    name="name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Full name as printed on certificates"
                    :aria-invalid="form.errors.name ? 'true' : undefined"
                    class="h-11 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/70 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                    :class="form.errors.name ? 'border-danger-600 focus:border-danger-600 focus:ring-danger-600/15' : ''"
                />
                <p v-if="form.errors.name" class="mt-1.5 text-xs font-semibold text-danger-600">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
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
                    <label for="phone" class="mb-1.5 block text-sm font-medium text-navy-950">
                        Phone number
                        <span class="ml-1 font-normal text-muted">(Optional)</span>
                    </label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        name="phone"
                        type="text"
                        autocomplete="tel"
                        placeholder="+60 12-345 6789"
                        :aria-invalid="form.errors.phone ? 'true' : undefined"
                        class="h-11 w-full rounded-xl border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/70 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        :class="form.errors.phone ? 'border-danger-600 focus:border-danger-600 focus:ring-danger-600/15' : ''"
                    />
                    <p v-if="form.errors.phone" class="mt-1.5 text-xs font-semibold text-danger-600">
                        {{ form.errors.phone }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-navy-950">
                        Password
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
                        Confirm password
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
            </div>

            <div class="pt-1">
                <AppButton
                    type="submit"
                    variant="warm"
                    size="lg"
                    class="w-full"
                    :loading="form.processing"
                >
                    {{ form.processing ? 'Creating account...' : 'Create account' }}
                </AppButton>
            </div>
        </form>

        <div class="border-t border-line pt-5 text-center text-sm text-muted">
            Already have an account?
            <Link
                :href="loginUrl"
                class="ml-1 font-semibold text-primary-600 transition-colors hover:text-primary-700"
            >
                Sign in
            </Link>
        </div>
    </AuthLayout>
</template>
