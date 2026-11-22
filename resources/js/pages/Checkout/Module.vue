<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, BookOpen, FileText, PlayCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FocusedLayout from '@/layouts/FocusedLayout.vue';
import type { CheckoutModuleProps } from '@/types/etms';

const props = defineProps<CheckoutModuleProps>();

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
    <Head title="Standalone Module Checkout" />

    <FocusedLayout>
        <div class="space-y-6">
            <div>
                <Link
                    :href="module.detailUrl"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-neutral-600 transition-colors duration-150 hover:text-[#0F4C81]"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    <span>Back to Module Overview</span>
                </Link>

                <div class="mt-3 flex flex-wrap items-center gap-2.5">
                    <span class="rounded-[6px] border border-neutral-200 bg-white px-2.5 py-0.5 font-mono text-xs font-medium text-neutral-700">
                        {{ module.code }}
                    </span>
                </div>

                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-900 sm:text-3xl">
                    Standalone Module Checkout
                </h1>
                <p class="mt-1 text-sm text-neutral-600">
                    Complete your billing details to unlock lifetime digital access to this standalone module.
                </p>
            </div>

            <div
                v-if="form.hasErrors"
                role="alert"
                class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900"
            >
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-red-600" aria-hidden="true" />
                <p>Please check the highlighted contact and billing fields before proceeding.</p>
            </div>

            <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
                <form
                    class="rounded-lg border border-neutral-200 bg-white p-6 lg:col-span-7"
                    @submit.prevent="submitCheckout"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-neutral-200 pb-4 text-sm">
                        <div>
                            <span class="text-neutral-500">Learner: </span>
                            <span class="font-semibold text-neutral-900">{{ learner.name }}</span>
                        </div>
                        <span class="text-neutral-600">{{ learner.email }}</span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <Label for="checkout-module-phone" class="mb-1.5 block text-sm font-medium text-neutral-800">
                                Contact Phone Number <span class="text-red-600">*</span>
                            </Label>
                            <Input
                                id="checkout-module-phone"
                                v-model="form.phone"
                                name="phone"
                                type="tel"
                                required
                                autocomplete="tel"
                                placeholder="+60 12-345 6789"
                                class="h-10 rounded-[6px] border-neutral-300"
                                :aria-invalid="form.errors.phone ? 'true' : undefined"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-xs font-medium text-red-600">
                                {{ form.errors.phone }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <Label for="checkout-module-address" class="mb-1.5 block text-sm font-medium text-neutral-800">
                                Billing Street Address <span class="text-red-600">*</span>
                            </Label>
                            <Input
                                id="checkout-module-address"
                                v-model="form.billing_address_line1"
                                name="billing_address_line1"
                                type="text"
                                required
                                autocomplete="address-line1"
                                class="h-10 rounded-[6px] border-neutral-300"
                                :aria-invalid="form.errors.billing_address_line1 ? 'true' : undefined"
                            />
                            <p v-if="form.errors.billing_address_line1" class="mt-1 text-xs font-medium text-red-600">
                                {{ form.errors.billing_address_line1 }}
                            </p>
                        </div>

                        <div>
                            <Label for="checkout-module-city" class="mb-1.5 block text-sm font-medium text-neutral-800">
                                City <span class="text-red-600">*</span>
                            </Label>
                            <Input
                                id="checkout-module-city"
                                v-model="form.billing_city"
                                name="billing_city"
                                type="text"
                                required
                                autocomplete="address-level2"
                                class="h-10 rounded-[6px] border-neutral-300"
                                :aria-invalid="form.errors.billing_city ? 'true' : undefined"
                            />
                            <p v-if="form.errors.billing_city" class="mt-1 text-xs font-medium text-red-600">
                                {{ form.errors.billing_city }}
                            </p>
                        </div>

                        <div>
                            <Label for="checkout-module-state" class="mb-1.5 block text-sm font-medium text-neutral-800">
                                State / Region <span class="text-red-600">*</span>
                            </Label>
                            <Input
                                id="checkout-module-state"
                                v-model="form.billing_state"
                                name="billing_state"
                                type="text"
                                required
                                autocomplete="address-level1"
                                class="h-10 rounded-[6px] border-neutral-300"
                                :aria-invalid="form.errors.billing_state ? 'true' : undefined"
                            />
                            <p v-if="form.errors.billing_state" class="mt-1 text-xs font-medium text-red-600">
                                {{ form.errors.billing_state }}
                            </p>
                        </div>

                        <div>
                            <Label for="checkout-module-postcode" class="mb-1.5 block text-sm font-medium text-neutral-800">
                                Postcode <span class="text-red-600">*</span>
                            </Label>
                            <Input
                                id="checkout-module-postcode"
                                v-model="form.billing_postcode"
                                name="billing_postcode"
                                type="text"
                                required
                                autocomplete="postal-code"
                                class="h-10 rounded-[6px] border-neutral-300"
                                :aria-invalid="form.errors.billing_postcode ? 'true' : undefined"
                            />
                            <p v-if="form.errors.billing_postcode" class="mt-1 text-xs font-medium text-red-600">
                                {{ form.errors.billing_postcode }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-neutral-500">
                            Access unlocks immediately upon payment confirmation.
                        </p>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="h-10 rounded-[6px] bg-[#0F4C81] px-5 text-sm font-medium text-white transition-colors duration-150 hover:bg-[#0F4C81]/90"
                        >
                            {{ form.processing ? 'Connecting...' : `Proceed to Pay ${module.priceLabel}` }}
                        </Button>
                    </div>
                </form>

                <aside class="rounded-lg border border-neutral-200 bg-white p-6 lg:sticky lg:top-6 lg:col-span-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                        Order Summary
                    </p>
                    <h2 class="mt-2 text-lg font-semibold leading-snug text-neutral-900">
                        {{ module.title }}
                    </h2>
                    <p v-if="module.summary" class="mt-1.5 text-sm leading-relaxed text-neutral-600">
                        {{ module.summary }}
                    </p>

                    <dl class="mt-5 space-y-3 border-t border-neutral-200 pt-4 text-sm">
                        <div class="flex items-center justify-between gap-2.5">
                            <dt class="flex items-center gap-2 text-neutral-600">
                                <BookOpen class="h-4 w-4 text-neutral-400" aria-hidden="true" />
                                <span>Access</span>
                            </dt>
                            <dd class="font-medium text-neutral-900">Lifetime Digital Access</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2.5">
                            <dt class="flex items-center gap-2 text-neutral-600">
                                <PlayCircle class="h-4 w-4 text-neutral-400" aria-hidden="true" />
                                <span>Video Lessons</span>
                            </dt>
                            <dd class="font-medium text-neutral-900 tabular-nums">{{ module.videosCount }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-2.5">
                            <dt class="flex items-center gap-2 text-neutral-600">
                                <FileText class="h-4 w-4 text-neutral-400" aria-hidden="true" />
                                <span>Materials</span>
                            </dt>
                            <dd class="font-medium text-neutral-900 tabular-nums">{{ module.materialsCount }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex items-center justify-between border-t border-neutral-200 pt-4">
                        <span class="text-sm font-semibold text-neutral-900">Total Payable</span>
                        <span class="text-xl font-bold text-neutral-900 tabular-nums">
                            {{ module.priceLabel }}
                        </span>
                    </div>
                </aside>
            </div>
        </div>
    </FocusedLayout>
</template>
