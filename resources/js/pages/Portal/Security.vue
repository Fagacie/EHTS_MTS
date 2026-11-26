<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, Check, Eye, EyeOff, Lock, User } from 'lucide-vue-next';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalSecurityProps } from '@/types/etms';

const props = defineProps<PortalSecurityProps>();

const { routes } = useRoutes();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const passwordStrength = computed(() => {
    const val = form.password;
    if (!val) return { score: 0, label: '', barClass: 'bg-line' };

    let score = 0;
    if (val.length >= 8) score += 1;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score += 1;
    if (/\d/.test(val)) score += 1;
    if (/[^A-Za-z0-9]/.test(val) || val.length >= 12) score += 1;

    if (score <= 1) return { score: 1, label: 'Weak', barClass: 'bg-danger-600' };
    if (score === 2) return { score: 2, label: 'Fair', barClass: 'bg-warning-600' };
    if (score === 3) return { score: 3, label: 'Good', barClass: 'bg-primary-600' };
    return { score: 4, label: 'Strong', barClass: 'bg-success-600' };
});

const passwordsMatch = computed(
    () =>
        form.password.length > 0 &&
        form.password_confirmation.length > 0 &&
        form.password === form.password_confirmation,
);

function submitPasswordUpdate(): void {
    form.put(props.updatePasswordUrl, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Password & Security" />

    <PortalLayout breadcrumb="Password & Security">
        <div class="mx-auto max-w-[1040px] space-y-6">
            <!-- Header + Underline Settings Tabs -->
            <header class="space-y-4">
                <div>
                    <h1 class="type-page-title">Account settings</h1>
                    <p class="mt-1 text-sm text-muted">
                        Update your password and review your account security status.
                    </p>
                </div>

                <nav
                    aria-label="Account settings"
                    class="flex items-center gap-6 border-b border-line"
                >
                    <Link
                        :href="routes.portalProfile"
                        class="-mb-px inline-flex items-center gap-2 border-b-2 border-transparent pb-3 text-sm font-medium text-muted transition-colors hover:border-line-strong hover:text-navy-950"
                    >
                        <User class="size-4" aria-hidden="true" />
                        <span>Profile &amp; Billing</span>
                    </Link>

                    <Link
                        :href="routes.portalSecurity"
                        aria-current="page"
                        class="-mb-px inline-flex items-center gap-2 border-b-2 border-navy-950 pb-3 text-sm font-semibold text-navy-950"
                    >
                        <Lock class="size-4" aria-hidden="true" />
                        <span>Password &amp; Security</span>
                    </Link>
                </nav>
            </header>

            <!-- Validation Error Banner -->
            <div
                v-if="form.hasErrors"
                role="alert"
                class="flex items-start gap-3 rounded-xl bg-danger-50 px-4 py-3.5 text-sm ring-1 ring-danger-600/20"
            >
                <AlertCircle class="mt-0.5 size-4 shrink-0 text-danger-600" aria-hidden="true" />
                <div>
                    <p class="font-semibold text-danger-700">Password could not be updated</p>
                    <p class="mt-0.5 text-xs text-ink/80">
                        Please review the highlighted password fields below and try again.
                    </p>
                </div>
            </div>

            <!-- Unified Security Sheet -->
            <form
                class="overflow-hidden rounded-2xl bg-white ring-1 ring-neutral-200/80"
                @submit.prevent="submitPasswordUpdate"
            >
                <div class="divide-y divide-line">
                    <!-- Section 1: Account Status -->
                    <div class="grid gap-6 p-6 lg:grid-cols-12 lg:p-8">
                        <div class="lg:col-span-4">
                            <h2 class="text-sm font-semibold text-navy-950">Account status</h2>
                            <p class="mt-1 text-xs leading-relaxed text-muted">
                                Sign-in email and current verification state for your learner account.
                            </p>
                        </div>

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between lg:col-span-8">
                            <div>
                                <p class="text-xs text-muted">Signed-in email</p>
                                <p class="mt-0.5 break-all text-sm font-semibold text-navy-950">
                                    {{ props.user.email }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <StatusBadge :tone="props.user.isEmailVerified ? 'success' : 'warning'" dot>
                                    {{ props.user.isEmailVerified ? 'Verified' : 'Unverified' }}
                                </StatusBadge>
                                <StatusBadge :tone="props.user.isActive ? 'primary' : 'danger'" dot>
                                    {{ props.user.isActive ? 'Active' : 'Inactive' }}
                                </StatusBadge>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Change Password -->
                    <div class="grid gap-6 p-6 lg:grid-cols-12 lg:p-8">
                        <div class="lg:col-span-4">
                            <h2 class="text-sm font-semibold text-navy-950">Change password</h2>
                            <p class="mt-1 text-xs leading-relaxed text-muted">
                                Use at least 8 characters with a mix of letters, numbers, and symbols.
                            </p>
                        </div>

                        <div class="max-w-lg space-y-4 lg:col-span-8">
                            <!-- Current Password -->
                            <div>
                                <label for="security-current-password" class="block text-xs font-semibold text-navy-950">
                                    Current password <span class="text-danger-600">*</span>
                                </label>
                                <div class="relative mt-1.5">
                                    <input
                                        id="security-current-password"
                                        v-model="form.current_password"
                                        name="current_password"
                                        :type="showCurrent ? 'text' : 'password'"
                                        required
                                        autocomplete="current-password"
                                        :aria-invalid="form.errors.current_password ? 'true' : undefined"
                                        class="h-10 w-full rounded-lg border border-line-strong/80 bg-white pr-10 pl-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                        :class="{ 'border-danger-600': form.errors.current_password }"
                                    />
                                    <button
                                        type="button"
                                        :aria-label="showCurrent ? 'Hide current password' : 'Show current password'"
                                        class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted transition-colors hover:text-navy-950"
                                        @click="showCurrent = !showCurrent"
                                    >
                                        <EyeOff v-if="showCurrent" class="size-4" aria-hidden="true" />
                                        <Eye v-else class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                                <p v-if="form.errors.current_password" class="mt-1 text-xs font-medium text-danger-600">
                                    {{ form.errors.current_password }}
                                </p>
                            </div>

                            <!-- New Password -->
                            <div>
                                <label for="security-new-password" class="block text-xs font-semibold text-navy-950">
                                    New password <span class="text-danger-600">*</span>
                                </label>
                                <div class="relative mt-1.5">
                                    <input
                                        id="security-new-password"
                                        v-model="form.password"
                                        name="password"
                                        :type="showNew ? 'text' : 'password'"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        :aria-invalid="form.errors.password ? 'true' : undefined"
                                        class="h-10 w-full rounded-lg border border-line-strong/80 bg-white pr-10 pl-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                        :class="{ 'border-danger-600': form.errors.password }"
                                    />
                                    <button
                                        type="button"
                                        :aria-label="showNew ? 'Hide new password' : 'Show new password'"
                                        class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted transition-colors hover:text-navy-950"
                                        @click="showNew = !showNew"
                                    >
                                        <EyeOff v-if="showNew" class="size-4" aria-hidden="true" />
                                        <Eye v-else class="size-4" aria-hidden="true" />
                                    </button>
                                </div>

                                <!-- Reactive Password Strength Meter -->
                                <div v-if="form.password.length > 0" class="mt-2 flex items-center gap-2.5">
                                    <div class="grid flex-1 grid-cols-4 gap-1.5">
                                        <span
                                            v-for="bar in 4"
                                            :key="bar"
                                            class="h-1 rounded-full transition-colors duration-150"
                                            :class="
                                                bar <= passwordStrength.score
                                                    ? passwordStrength.barClass
                                                    : 'bg-line'
                                            "
                                        />
                                    </div>
                                    <span class="text-[11px] font-semibold text-muted">
                                        {{ passwordStrength.label }}
                                    </span>
                                </div>

                                <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-danger-600">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="security-confirm-password" class="block text-xs font-semibold text-navy-950">
                                    Confirm new password <span class="text-danger-600">*</span>
                                </label>
                                <div class="relative mt-1.5">
                                    <input
                                        id="security-confirm-password"
                                        v-model="form.password_confirmation"
                                        name="password_confirmation"
                                        :type="showConfirm ? 'text' : 'password'"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        :aria-invalid="form.errors.password_confirmation ? 'true' : undefined"
                                        class="h-10 w-full rounded-lg border border-line-strong/80 bg-white pr-10 pl-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                        :class="{ 'border-danger-600': form.errors.password_confirmation }"
                                    />
                                    <button
                                        type="button"
                                        :aria-label="showConfirm ? 'Hide confirm password' : 'Show confirm password'"
                                        class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted transition-colors hover:text-navy-950"
                                        @click="showConfirm = !showConfirm"
                                    >
                                        <EyeOff v-if="showConfirm" class="size-4" aria-hidden="true" />
                                        <Eye v-else class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                                <p v-if="form.errors.password_confirmation" class="mt-1 text-xs font-medium text-danger-600">
                                    {{ form.errors.password_confirmation }}
                                </p>
                                <p
                                    v-else-if="passwordsMatch"
                                    class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-success-700"
                                >
                                    <Check class="size-3.5" aria-hidden="true" />
                                    <span>Passwords match</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-sand-50/60 px-6 py-4 lg:px-8">
                    <p class="text-xs text-muted">
                        Your other profile and billing details remain unchanged.
                    </p>
                    <button
                        type="submit"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Updating...' : 'Update password' }}
                    </button>
                </div>
            </form>
        </div>
    </PortalLayout>
</template>
