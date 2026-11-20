<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Info } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/select';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { CheckoutModuleProps } from '@/types/etms';

const props = defineProps<CheckoutModuleProps>();

const form = useForm({
    customer_type: props.defaults.customerType,
    phone: props.defaults.phone,
    company_name: props.defaults.companyName,
    billing_address_line1: props.defaults.billingAddressLine1,
    billing_city: props.defaults.billingCity,
    billing_state: props.defaults.billingState,
    billing_postcode: props.defaults.billingPostcode,
});

const isCompany = computed(() => form.customer_type === 'company');

function submitCheckout(): void {
    form.post(props.submitUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Module Purchase Checkout" />

    <PortalLayout breadcrumb="Module Checkout">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <Link
                :href="module.detailUrl"
                class="inline-flex items-center gap-1.5 text-[13.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
            >
                <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                <span>Back to Module Overview</span>
            </Link>

            <header class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge v-if="module.categoryName" variant="outline">
                        {{ module.categoryName }}
                    </Badge>
                    <Badge variant="secondary" class="tabular-nums">
                        {{ module.code }}
                    </Badge>
                </div>
                <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                    Independent Module Checkout
                </h1>
                <p class="text-[13.5px] text-[#625D6D]">
                    Complete your billing details to unlock lifetime digital access to this module's learning materials and videos.
                </p>
            </header>

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
                            Complete all required registration and billing fields before proceeding to payment.
                        </p>
                    </div>
                </div>
            </Card>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <form class="space-y-5" @submit.prevent="submitCheckout">
                        <Card>
                            <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                                <CardTitle>1. Learner &amp; Billing Classification</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4 pt-4">
                                <div class="grid gap-4 rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] p-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-[12.5px] font-semibold text-[#625D6D]">Learner Name</p>
                                        <p class="mt-0.5 text-[14px] font-bold text-[#282338]">{{ learner.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[12.5px] font-semibold text-[#625D6D]">Account Email</p>
                                        <p class="mt-0.5 break-all text-[13.5px] text-[#292536]">{{ learner.email }}</p>
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label for="checkout-module-customer-type" class="mb-1.5">
                                            Account Type <span aria-hidden="true">*</span>
                                        </Label>
                                        <NativeSelect
                                            id="checkout-module-customer-type"
                                            v-model="form.customer_type"
                                            name="customer_type"
                                            required
                                        >
                                            <option value="individual">Individual</option>
                                            <option value="company">Company / Organisation</option>
                                        </NativeSelect>
                                        <p
                                            v-if="form.errors.customer_type"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.customer_type }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="checkout-module-phone" class="mb-1.5">
                                            Contact Phone Number <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-module-phone"
                                            v-model="form.phone"
                                            name="phone"
                                            type="tel"
                                            required
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

                                    <div v-if="isCompany" class="sm:col-span-2">
                                        <Label for="checkout-module-company-name" class="mb-1.5">
                                            Company / Organisation Name <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-module-company-name"
                                            v-model="form.company_name"
                                            name="company_name"
                                            type="text"
                                            :required="isCompany"
                                            autocomplete="organization"
                                            :aria-invalid="form.errors.company_name ? 'true' : undefined"
                                        />
                                        <p
                                            v-if="form.errors.company_name"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.company_name }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                                <CardTitle>2. Billing Address</CardTitle>
                            </CardHeader>
                            <CardContent class="pt-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <Label for="checkout-module-address" class="mb-1.5">
                                            Billing Street Address <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-module-address"
                                            v-model="form.billing_address_line1"
                                            name="billing_address_line1"
                                            type="text"
                                            required
                                            autocomplete="address-line1"
                                            :aria-invalid="form.errors.billing_address_line1 ? 'true' : undefined"
                                        />
                                        <p
                                            v-if="form.errors.billing_address_line1"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_address_line1 }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="checkout-module-city" class="mb-1.5">
                                            City <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-module-city"
                                            v-model="form.billing_city"
                                            name="billing_city"
                                            type="text"
                                            required
                                            autocomplete="address-level2"
                                            :aria-invalid="form.errors.billing_city ? 'true' : undefined"
                                        />
                                        <p
                                            v-if="form.errors.billing_city"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_city }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="checkout-module-state" class="mb-1.5">
                                            State / Wilayah <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-module-state"
                                            v-model="form.billing_state"
                                            name="billing_state"
                                            type="text"
                                            required
                                            autocomplete="address-level1"
                                            :aria-invalid="form.errors.billing_state ? 'true' : undefined"
                                        />
                                        <p
                                            v-if="form.errors.billing_state"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_state }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="checkout-module-postcode" class="mb-1.5">
                                            Postcode <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-module-postcode"
                                            v-model="form.billing_postcode"
                                            name="billing_postcode"
                                            type="text"
                                            required
                                            autocomplete="postal-code"
                                            :aria-invalid="form.errors.billing_postcode ? 'true' : undefined"
                                        />
                                        <p
                                            v-if="form.errors.billing_postcode"
                                            class="mt-1.5 text-[12.5px] font-semibold text-[#A33C48]"
                                        >
                                            {{ form.errors.billing_postcode }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter class="flex flex-wrap items-center justify-between gap-4 border-t border-[#EEEAF2] px-6 py-4">
                                <p class="text-[12.5px] text-[#625D6D]">
                                    Access unlocks immediately upon verified online payment confirmation.
                                </p>
                                <Button type="submit" size="lg" :disabled="form.processing">
                                    {{ form.processing ? 'Connecting...' : `Proceed to Pay ${module.priceLabel}` }}
                                </Button>
                            </CardFooter>
                        </Card>
                    </form>
                </div>

                <div class="space-y-5">
                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Module Purchase Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-4">
                            <div>
                                <h2 class="text-[16px] font-bold leading-snug text-[#282338]">
                                    {{ module.title }}
                                </h2>
                                <p
                                    v-if="module.summary"
                                    class="mt-1 text-[13px] leading-relaxed text-[#625D6D]"
                                >
                                    {{ module.summary }}
                                </p>
                            </div>

                            <dl class="space-y-3 border-t border-[#EEEAF2] pt-4 text-[13.5px]">
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-[#625D6D]">Access Type</dt>
                                    <dd class="font-bold text-[#282338]">Lifetime Digital Access</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-[#625D6D]">Video Lessons</dt>
                                    <dd class="font-bold text-[#282338] tabular-nums">
                                        {{ module.videosCount }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-[#625D6D]">Downloadable Materials</dt>
                                    <dd class="font-bold text-[#282338] tabular-nums">
                                        {{ module.materialsCount }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="flex items-center justify-between border-t border-[#EEEAF2] pt-4">
                                <span class="text-[14px] font-bold text-[#282338]">Total Payable</span>
                                <span class="text-[18px] font-extrabold text-[#282338] tabular-nums">
                                    {{ module.priceLabel }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <Info class="mt-0.5 h-5 w-5 shrink-0 text-[#5942B5]" aria-hidden="true" />
                            <div>
                                <p class="text-[14.5px] font-bold text-[#282338]">Self-Paced Digital Learning</p>
                                <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                                    Independent module purchases include on-demand video lessons and reference materials, separate from physical session seat reservations.
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
