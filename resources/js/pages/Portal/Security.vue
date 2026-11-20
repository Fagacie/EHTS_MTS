<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header>
                <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                    Password &amp; Security
                </h1>
                <p class="mt-1 text-[13.5px] text-[#625D6D]">
                    Keep your Ecohydrotech account secure by updating your password periodically.
                </p>
            </header>

            <Card class="p-4 sm:px-5">
                <div
                    class="flex flex-wrap items-center gap-2"
                    role="navigation"
                    aria-label="Account settings"
                >
                    <Link
                        :href="routes.portalProfile"
                        class="inline-flex min-h-8.5 items-center rounded-[8px] border border-[#E5E1EB] bg-white px-3.5 py-1.5 text-[12.5px] font-bold text-[#403B4B] transition-colors duration-150 hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    >
                        Profile &amp; Billing
                    </Link>
                    <Link
                        :href="routes.portalSecurity"
                        aria-current="page"
                        class="inline-flex min-h-8.5 items-center rounded-[8px] bg-[#7057D9] px-3.5 py-1.5 text-[12.5px] font-bold text-white transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    >
                        Password &amp; Security
                    </Link>
                </div>
            </Card>

            <Card
                v-if="form.hasErrors"
                role="alert"
                class="border-[#ECC4CA] bg-[#FBECEE] p-4 shadow-none sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#A33C48]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#A33C48]">
                            Password update could not be saved
                        </p>
                        <p class="mt-1 text-[13px] text-[#403B4B]">
                            Please review the highlighted password fields below and try again.
                        </p>
                    </div>
                </div>
            </Card>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <form @submit.prevent="submitPasswordUpdate">
                        <Card>
                            <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                                <CardTitle>Change Password</CardTitle>
                                <CardDescription>
                                    Choose a strong password that you do not reuse on other websites.
                                </CardDescription>
                            </CardHeader>

                            <CardContent class="space-y-4 pt-4">
                                <div>
                                    <Label for="security-current-password" class="mb-1.5">
                                        Current Password <span aria-hidden="true">*</span>
                                    </Label>
                                    <div class="relative">
                                        <Input
                                            id="security-current-password"
                                            v-model="form.current_password"
                                            name="current_password"
                                            :type="showCurrent ? 'text' : 'password'"
                                            required
                                            autocomplete="current-password"
                                            :aria-invalid="form.errors.current_password ? 'true' : undefined"
                                            :aria-describedby="form.errors.current_password ? 'security-current-password-error' : undefined"
                                            class="pr-16"
                                        />
                                        <button
                                            type="button"
                                            :aria-pressed="showCurrent"
                                            :aria-label="showCurrent ? 'Hide current password' : 'Show current password'"
                                            class="absolute inset-y-0 right-0 flex min-w-14 items-center justify-center rounded-r-lg text-[12.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                                            @click="showCurrent = !showCurrent"
                                        >
                                            {{ showCurrent ? 'Hide' : 'Show' }}
                                        </button>
                                    </div>
                                    <p
                                        v-if="form.errors.current_password"
                                        id="security-current-password-error"
                                        class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                    >
                                        {{ form.errors.current_password }}
                                    </p>
                                </div>

                                <div>
                                    <Label for="security-new-password" class="mb-1.5">
                                        New Password <span aria-hidden="true">*</span>
                                    </Label>
                                    <div class="relative">
                                        <Input
                                            id="security-new-password"
                                            v-model="form.password"
                                            name="password"
                                            :type="showNew ? 'text' : 'password'"
                                            required
                                            minlength="8"
                                            autocomplete="new-password"
                                            :aria-invalid="form.errors.password ? 'true' : undefined"
                                            :aria-describedby="form.errors.password ? 'security-new-password-error' : 'security-new-password-hint'"
                                            class="pr-16"
                                        />
                                        <button
                                            type="button"
                                            :aria-pressed="showNew"
                                            :aria-label="showNew ? 'Hide new password' : 'Show new password'"
                                            class="absolute inset-y-0 right-0 flex min-w-14 items-center justify-center rounded-r-lg text-[12.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                                            @click="showNew = !showNew"
                                        >
                                            {{ showNew ? 'Hide' : 'Show' }}
                                        </button>
                                    </div>
                                    <p
                                        v-if="form.errors.password"
                                        id="security-new-password-error"
                                        class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                    >
                                        {{ form.errors.password }}
                                    </p>
                                    <p
                                        v-else
                                        id="security-new-password-hint"
                                        class="mt-1.5 text-[12px] text-[#625D6D]"
                                    >
                                        Use at least 8 characters with a mix of letters, numbers, or symbols.
                                    </p>
                                </div>

                                <div>
                                    <Label for="security-confirm-password" class="mb-1.5">
                                        Confirm New Password <span aria-hidden="true">*</span>
                                    </Label>
                                    <div class="relative">
                                        <Input
                                            id="security-confirm-password"
                                            v-model="form.password_confirmation"
                                            name="password_confirmation"
                                            :type="showConfirm ? 'text' : 'password'"
                                            required
                                            minlength="8"
                                            autocomplete="new-password"
                                            :aria-invalid="form.errors.password_confirmation ? 'true' : undefined"
                                            :aria-describedby="form.errors.password_confirmation ? 'security-confirm-password-error' : undefined"
                                            class="pr-16"
                                        />
                                        <button
                                            type="button"
                                            :aria-pressed="showConfirm"
                                            :aria-label="showConfirm ? 'Hide confirm new password' : 'Show confirm new password'"
                                            class="absolute inset-y-0 right-0 flex min-w-14 items-center justify-center rounded-r-lg text-[12.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                                            @click="showConfirm = !showConfirm"
                                        >
                                            {{ showConfirm ? 'Hide' : 'Show' }}
                                        </button>
                                    </div>
                                    <p
                                        v-if="form.errors.password_confirmation"
                                        id="security-confirm-password-error"
                                        class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                    >
                                        {{ form.errors.password_confirmation }}
                                    </p>
                                </div>
                            </CardContent>

                            <CardFooter class="flex flex-wrap items-center justify-between gap-3 border-t border-[#EEEAF2] px-6 py-4">
                                <p class="text-[12.5px] text-[#625D6D]">
                                    Your other active login details remain unchanged.
                                </p>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Updating...' : 'Update Password' }}
                                </Button>
                            </CardFooter>
                        </Card>
                    </form>
                </div>

                <div class="space-y-5">
                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Account Status</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <dl class="space-y-3 text-[13.5px]">
                                <div>
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Account Email</dt>
                                    <dd class="mt-0.5 break-all font-bold text-[#282338]">
                                        {{ props.user.email }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Email Status</dt>
                                    <dd>
                                        <PortalStatusBadge
                                            :status="props.user.isEmailVerified ? 'verified' : 'warning'"
                                            :label="props.user.isEmailVerified ? 'Verified' : 'Unverified'"
                                        />
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Account State</dt>
                                    <dd>
                                        <PortalStatusBadge
                                            :status="props.user.isActive ? 'active' : 'revoked'"
                                            :label="props.user.isActive ? 'Active' : 'Inactive'"
                                        />
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Security Tips</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <ul class="list-disc space-y-2 pl-4 text-[13px] leading-relaxed text-[#403B4B]">
                                <li>Avoid reusing passwords across work and personal accounts.</li>
                                <li>Review your recent orders and notifications periodically.</li>
                                <li>Always sign out when accessing the portal on shared computers.</li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
