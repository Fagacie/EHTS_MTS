<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Info } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
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
import { NativeSelect } from '@/components/ui/select';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { CheckoutSessionProps } from '@/types/etms';

const props = defineProps<CheckoutSessionProps>();

const form = useForm({
    phone: props.defaults.phone,
    billing_address_line1: props.defaults.billingAddressLine1,
    billing_city: props.defaults.billingCity,
    billing_state: props.defaults.billingState,
    billing_postcode: props.defaults.billingPostcode,
});


function submitCheckout(): void {
    form.post(props.submitUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Session Booking Checkout" />

    <PortalLayout breadcrumb="Session Checkout">
        <div class="mx-auto max-w-[1440px] space-y-5">
            <Link
                :href="session.detailUrl"
                class="inline-flex items-center gap-1.5 text-[13.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
            >
                <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                <span>Back to Session Details</span>
            </Link>

            <header class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge v-if="session.moduleCode" variant="secondary">
                        {{ session.moduleCode }}
                    </Badge>
                    <Badge v-if="session.sessionCode" variant="outline">
                        {{ session.sessionCode }}
                    </Badge>
                </div>
                <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                    Session Booking Checkout
                </h1>
                <p class="text-[13.5px] text-[#625D6D]">
                    Confirm your registration details to temporarily reserve your seat and proceed to online payment.
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

            <Card
                v-if="existingHold"
                role="status"
                class="flex flex-col gap-4 border-[#E8CFA1] bg-[#FBF2E3] p-4 shadow-none sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-[#8B5A17]" aria-hidden="true" />
                    <div>
                        <p class="text-[14.5px] font-bold text-[#292536] tabular-nums">
                            Active Seat Reservation Found ({{ existingHold.bookingReference }})
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                            You already hold a seat for this session
                            <template v-if="existingHold.expiresAtLabel">
                                until <span class="font-semibold tabular-nums">{{ existingHold.expiresAtLabel }}</span>
                            </template>
                            . You can resume payment on your existing reservation.
                        </p>
                    </div>
                </div>

                <Button as-child size="sm" class="shrink-0">
                    <Link :href="existingHold.detailUrl">
                        Resume Existing Hold
                    </Link>
                </Button>
            </Card>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <form class="space-y-5" @submit.prevent="submitCheckout">
                        <Card>
                            <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                                <CardTitle>1. Attendee &amp; Registration Type</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4 pt-4">
                                <div class="grid gap-4 rounded-xl border border-[#E5E1EB] bg-[#FAF9FC] p-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-[12.5px] font-semibold text-[#625D6D]">Attendee Name</p>
                                        <p class="mt-0.5 text-[14px] font-bold text-[#282338]">{{ attendee.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[12.5px] font-semibold text-[#625D6D]">Account Email</p>
                                        <p class="mt-0.5 break-all text-[13.5px] text-[#292536]">{{ attendee.email }}</p>
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <Label for="checkout-session-phone" class="mb-1.5">
                                            Contact Phone Number <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-session-phone"
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

                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                                <CardTitle>2. Billing Address</CardTitle>
                                <CardDescription>
                                    Saved automatically to your trainee profile for future bookings.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="pt-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <Label for="checkout-session-address" class="mb-1.5">
                                            Billing Street Address <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-session-address"
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
                                        <Label for="checkout-session-city" class="mb-1.5">
                                            City <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-session-city"
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
                                        <Label for="checkout-session-state" class="mb-1.5">
                                            State / Wilayah <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-session-state"
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
                                        <Label for="checkout-session-postcode" class="mb-1.5">
                                            Postcode <span aria-hidden="true">*</span>
                                        </Label>
                                        <Input
                                            id="checkout-session-postcode"
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
                                    Proceeding reserves your seat for 15 minutes during online payment.
                                </p>
                                <Button type="submit" size="lg" :disabled="form.processing">
                                    {{ form.processing ? 'Reserving Seat...' : `Reserve Seat & Pay ${session.priceLabel}` }}
                                </Button>
                            </CardFooter>
                        </Card>
                    </form>
                </div>

                <div class="space-y-5">
                    <Card>
                        <CardHeader class="border-b border-[#EEEAF2] pb-3.5">
                            <CardTitle>Session Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-4">
                            <div>
                                <h2 class="text-[16px] font-bold leading-snug text-[#282338]">
                                    {{ session.title }}
                                </h2>
                                <p
                                    v-if="session.moduleTitle"
                                    class="mt-0.5 text-[13px] text-[#625D6D]"
                                >
                                    {{ session.moduleTitle }}
                                </p>
                            </div>

                            <dl class="space-y-3 border-t border-[#EEEAF2] pt-4 text-[13.5px]">
                                <div>
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Schedule</dt>
                                    <dd class="mt-0.5 font-bold text-[#282338] tabular-nums">
                                        {{ session.scheduleLabel }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-[12.5px] font-semibold text-[#625D6D]">Venue</dt>
                                    <dd class="mt-0.5 text-[#292536]">{{ session.venueLabel }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-[#625D6D]">Available Seats</dt>
                                    <dd class="font-bold text-[#282338] tabular-nums">
                                        {{ session.remainingSeats }} remaining
                                    </dd>
                                </div>
                            </dl>

                            <div class="flex items-center justify-between border-t border-[#EEEAF2] pt-4">
                                <span class="text-[14px] font-bold text-[#282338]">Total Payable</span>
                                <span class="text-[18px] font-extrabold text-[#282338] tabular-nums">
                                    {{ session.priceLabel }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <Info class="mt-0.5 h-5 w-5 shrink-0 text-[#5942B5]" aria-hidden="true" />
                            <div>
                                <p class="text-[14.5px] font-bold text-[#282338]">15-Minute Seat Hold</p>
                                <p class="mt-1 text-[13px] leading-relaxed text-[#403B4B]">
                                    Submitting creates a pending booking and holds one seat for 15 minutes while you complete online payment via Billplz. Verified attendance also unlocks post-session recordings when enabled for the session. Self-paced digital modules are available separately.
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
