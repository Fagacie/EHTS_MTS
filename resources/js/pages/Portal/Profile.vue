<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { AlertCircle } from 'lucide-vue-next';
import AvatarUpload from '@/components/ds/AvatarUpload.vue';
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
import { cn } from '@/lib/utils';
import type { PortalProfileProps } from '@/types/etms';

const props = defineProps<PortalProfileProps>();

const { routes } = useRoutes();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.profile.phone,
    job_title: props.profile.jobTitle,
    billing_contact_name: props.profile.billingContactName,
    billing_email: props.profile.billingEmail,
    billing_phone: props.profile.billingPhone,
    billing_address_line_1: props.profile.billingAddressLine1,
    billing_address_line_2: props.profile.billingAddressLine2,
    billing_city: props.profile.billingCity,
    billing_state: props.profile.billingState,
    billing_postcode: props.profile.billingPostcode,
    billing_country: props.profile.billingCountry || 'MY',
});

const photo = ref<File | null>(null);


function submitProfile(): void {
    router.post(props.updateUrl, {
        _method: 'PUT',
        name: form.name,
        email: form.email,
        phone: form.phone,
        job_title: form.job_title,
        billing_contact_name: form.billing_contact_name,
        billing_email: form.billing_email,
        billing_phone: form.billing_phone,
        billing_address_line_1: form.billing_address_line_1,
        billing_address_line_2: form.billing_address_line_2,
        billing_city: form.billing_city,
        billing_state: form.billing_state,
        billing_postcode: form.billing_postcode,
        billing_country: form.billing_country,
        photo: photo.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Profile & Billing Details" />

    <PortalLayout breadcrumb="Profile & Billing">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header>
                <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                    Profile &amp; Billing Details
                </h1>
                <p class="mt-1 text-[13.5px] text-[#625D6D]">
                    Keep your trainee contact details and billing address updated for faster registration and certificate records.
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
                        aria-current="page"
                        class="inline-flex min-h-8.5 items-center rounded-[8px] bg-[#7057D9] px-3.5 py-1.5 text-[12.5px] font-bold text-white transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                    >
                        Profile &amp; Billing
                    </Link>
                    <Link
                        :href="routes.portalSecurity"
                        class="inline-flex min-h-8.5 items-center rounded-[8px] border border-[#E5E1EB] bg-white px-3.5 py-1.5 text-[12.5px] font-bold text-[#403B4B] transition-colors duration-150 hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
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
                            Please check the highlighted details
                        </p>
                        <p class="mt-1 text-[13px] text-[#403B4B]">
                            Some fields could not be saved. Review the messages below and try again.
                        </p>
                    </div>
                </div>
            </Card>

            <form class="space-y-5" @submit.prevent="submitProfile">
                <div class="grid gap-5 lg:grid-cols-3">
                    <div class="space-y-5 lg:col-span-2">
                        <Card>
                            <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                                <CardTitle>Personal Details</CardTitle>
                                <CardDescription>
                                    Your full name appears on training registrations and issued certificates.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="pt-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <AvatarUpload
                                            id="profile-photo"
                                            v-model="photo"
                                            :initial-url="props.user.photoUrl"
                                            label="Profile photo"
                                            hint="Optional · JPG, PNG, or WebP up to 5 MB"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <Label for="profile-name" class="mb-1.5">
                                            Full Name <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="profile-name"
                                            v-model="form.name"
                                            name="name"
                                            type="text"
                                            required
                                            autocomplete="name"
                                            :aria-invalid="form.errors.name ? 'true' : undefined"
                                            :aria-describedby="form.errors.name ? 'profile-name-error' : 'profile-name-hint'"
                                        />
                                        <p
                                            v-if="form.errors.name"
                                            id="profile-name-error"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.name }}
                                        </p>
                                        <p
                                            v-else
                                            id="profile-name-hint"
                                            class="mt-1.5 text-[12px] text-[#625D6D]"
                                        >
                                            Use your official full name as you wish it to appear on certificates.
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="profile-email" class="mb-1.5">
                                            Email Address <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="profile-email"
                                            v-model="form.email"
                                            name="email"
                                            type="email"
                                            required
                                            autocomplete="email"
                                            :aria-invalid="form.errors.email ? 'true' : undefined"
                                            :aria-describedby="form.errors.email ? 'profile-email-error' : 'profile-email-hint'"
                                        />
                                        <p
                                            v-if="form.errors.email"
                                            id="profile-email-error"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.email }}
                                        </p>
                                        <p
                                            v-else
                                            id="profile-email-hint"
                                            class="mt-1.5 text-[12px] text-[#625D6D]"
                                        >
                                            Changing your email requires re-verification.
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="profile-phone" class="mb-1.5">
                                            Mobile / Contact Phone
                                            <span class="ml-1 font-normal text-[#625D6D]">(Optional)</span>
                                        </Label>
                                        <Input
                                            id="profile-phone"
                                            v-model="form.phone"
                                            name="phone"
                                            type="tel"
                                            autocomplete="tel"
                                            placeholder="+60 12-345 6789"
                                            :aria-invalid="form.errors.phone ? 'true' : undefined"
                                        />
                                        <p
                                            v-if="form.errors.phone"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.phone }}
                                        </p>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <Label for="profile-job-title" class="mb-1.5">
                                            Role / Job Title
                                            <span class="ml-1 font-normal text-[#625D6D]">(Optional)</span>
                                        </Label>
                                        <Input
                                            id="profile-job-title"
                                            v-model="form.job_title"
                                            name="job_title"
                                            type="text"
                                            autocomplete="organization-title"
                                            placeholder="e.g. Water Resources Engineer"
                                            :aria-invalid="form.errors.job_title ? 'true' : undefined"
                                        />
                                        <p
                                            v-if="form.errors.job_title"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.job_title }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                                <CardTitle>Billing Address</CardTitle>
                                <CardDescription>
                                    Saved for your future session and module checkout orders.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="pt-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <Label for="profile-address-1" class="mb-1.5">
                                            Address Line 1
                                            <span class="ml-1 font-normal text-[#625D6D]">(Optional)</span>
                                        </Label>
                                        <Input
                                            id="profile-address-1"
                                            v-model="form.billing_address_line_1"
                                            name="billing_address_line_1"
                                            type="text"
                                            autocomplete="address-line1"
                                            placeholder="Street address or suite"
                                        />
                                        <p
                                            v-if="form.errors.billing_address_line_1"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_address_line_1 }}
                                        </p>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <Label for="profile-address-2" class="mb-1.5">
                                            Address Line 2
                                            <span class="ml-1 font-normal text-[#625D6D]">(Optional)</span>
                                        </Label>
                                        <Input
                                            id="profile-address-2"
                                            v-model="form.billing_address_line_2"
                                            name="billing_address_line_2"
                                            type="text"
                                            autocomplete="address-line2"
                                            placeholder="Apartment, building, or floor"
                                        />
                                        <p
                                            v-if="form.errors.billing_address_line_2"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_address_line_2 }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="profile-city" class="mb-1.5">
                                            City
                                            <span class="ml-1 font-normal text-[#625D6D]">(Optional)</span>
                                        </Label>
                                        <Input
                                            id="profile-city"
                                            v-model="form.billing_city"
                                            name="billing_city"
                                            type="text"
                                            autocomplete="address-level2"
                                        />
                                        <p
                                            v-if="form.errors.billing_city"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_city }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="profile-state" class="mb-1.5">
                                            State / Wilayah
                                            <span class="ml-1 font-normal text-[#625D6D]">(Optional)</span>
                                        </Label>
                                        <Input
                                            id="profile-state"
                                            v-model="form.billing_state"
                                            name="billing_state"
                                            type="text"
                                            autocomplete="address-level1"
                                        />
                                        <p
                                            v-if="form.errors.billing_state"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_state }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="profile-postcode" class="mb-1.5">
                                            Postcode
                                            <span class="ml-1 font-normal text-[#625D6D]">(Optional)</span>
                                        </Label>
                                        <Input
                                            id="profile-postcode"
                                            v-model="form.billing_postcode"
                                            name="billing_postcode"
                                            type="text"
                                            autocomplete="postal-code"
                                        />
                                        <p
                                            v-if="form.errors.billing_postcode"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_postcode }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="profile-country" class="mb-1.5">
                                            Country Code
                                        </Label>
                                        <Input
                                            id="profile-country"
                                            v-model="form.billing_country"
                                            name="billing_country"
                                            type="text"
                                            maxlength="2"
                                            autocomplete="country"
                                            aria-describedby="profile-country-hint"
                                        />
                                        <p
                                            v-if="form.errors.billing_country"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_country }}
                                        </p>
                                        <p
                                            v-else
                                            id="profile-country-hint"
                                            class="mt-1.5 text-[12px] text-[#625D6D]"
                                        >
                                            Two-letter country code (e.g. MY).
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter class="flex flex-wrap items-center justify-between gap-3 border-t border-[#EEEAF2] px-6 py-4">
                                <p class="text-[12.5px] text-[#625D6D]">
                                    Fields marked <span class="font-bold text-[#282338]">*</span> are required.
                                </p>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Saving...' : 'Save Profile Changes' }}
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>

                    <div class="space-y-5">
                        <Card>
                            <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                                <CardTitle>Account Summary</CardTitle>
                            </CardHeader>
                            <CardContent class="pt-4">
                                <dl class="space-y-3 text-[13.5px]">
                                    <div>
                                        <dt class="text-[12.5px] font-semibold text-[#625D6D]">Signed-in Email</dt>
                                        <dd class="mt-0.5 break-all font-bold text-[#282338]">
                                            {{ props.user.email }}
                                        </dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-[12.5px] font-semibold text-[#625D6D]">Email Verification</dt>
                                        <dd>
                                            <PortalStatusBadge
                                                :status="props.user.isEmailVerified ? 'verified' : 'warning'"
                                                :label="props.user.isEmailVerified ? 'Verified' : 'Unverified'"
                                            />
                                        </dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-[12.5px] font-semibold text-[#625D6D]">Checkout Profile</dt>
                                        <dd>
                                            <PortalStatusBadge
                                                :status="props.profile.isComplete ? 'completed' : 'warning'"
                                                :label="props.profile.isComplete ? 'Ready' : 'Incomplete'"
                                            />
                                        </dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </PortalLayout>
</template>
