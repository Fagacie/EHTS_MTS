<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, Camera, Lock, User } from 'lucide-vue-next';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
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

const fileInputRef = ref<HTMLInputElement | null>(null);
const photo = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const photoError = ref<string | null>(null);

const displayedPhotoUrl = computed(() => previewUrl.value ?? props.user.photoUrl);

const initials = computed(() => {
    const parts = (form.name || props.user.name || 'U').trim().split(/\s+/).filter(Boolean);
    return parts.slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || 'U';
});

const hasChanges = computed(() => form.isDirty || photo.value !== null);

function triggerFilePicker(): void {
    fileInputRef.value?.click();
}

function handleFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    photoError.value = null;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        photoError.value = 'Please choose a JPG, PNG, or WebP image.';
        input.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        photoError.value = 'Image must be 5 MB or smaller.';
        input.value = '';
        return;
    }

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    photo.value = file;
    previewUrl.value = URL.createObjectURL(file);
}

function clearSelectedPhoto(): void {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = null;
    photo.value = null;
    photoError.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function resetChanges(): void {
    form.reset();
    form.clearErrors();
    clearSelectedPhoto();
}

function submitProfile(): void {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
        photo: photo.value,
    })).post(props.updateUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearSelectedPhoto();
        },
    });
}

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <Head title="Profile & Billing" />

    <PortalLayout breadcrumb="Profile & Billing">
        <div class="mx-auto max-w-[1040px] space-y-6">
            <!-- Header + Tabs -->
            <header class="space-y-4">
                <div>
                    <h1 class="type-page-title">Profile</h1>
                    <p class="mt-1 text-sm text-muted">
                        Manage your personal details, photo, and billing address.
                    </p>
                </div>

                <nav
                    aria-label="Account settings"
                    class="flex items-center gap-6 border-b border-line"
                >
                    <Link
                        :href="routes.portalProfile"
                        aria-current="page"
                        class="-mb-px inline-flex items-center gap-2 border-b-2 border-navy-950 pb-3 text-sm font-semibold text-navy-950"
                    >
                        <User class="size-4" aria-hidden="true" />
                        <span>Profile &amp; Billing</span>
                    </Link>

                    <Link
                        :href="routes.portalSecurity"
                        class="-mb-px inline-flex items-center gap-2 border-b-2 border-transparent pb-3 text-sm font-medium text-muted transition-colors hover:border-line-strong hover:text-navy-950"
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
                    <p class="font-semibold text-danger-700">Please check the highlighted fields</p>
                    <p class="mt-0.5 text-xs text-ink/80">
                        Some details could not be saved. Review the messages below and try again.
                    </p>
                </div>
            </div>

            <!-- Main Profile Form -->
            <form
                class="rounded-2xl bg-white p-6 ring-1 ring-neutral-200/80 sm:p-8"
                @submit.prevent="submitProfile"
            >
                <!-- Top Section: Large Left Avatar + Right Personal Fields -->
                <div class="flex flex-col gap-8 md:flex-row md:items-start">
                    <!-- Left: Large Profile Photo -->
                    <div class="flex shrink-0 flex-col items-center md:w-48">
                        <input
                            ref="fileInputRef"
                            id="profile-photo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="sr-only"
                            @change="handleFileChange"
                        />

                        <button
                            type="button"
                            class="group relative size-32 overflow-hidden rounded-full bg-sand-100 ring-2 ring-line transition-all hover:ring-navy-950/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 sm:size-36"
                            @click="triggerFilePicker"
                        >
                            <img
                                v-if="displayedPhotoUrl"
                                :src="displayedPhotoUrl"
                                :alt="form.name || props.user.name"
                                class="size-full object-cover"
                            />
                            <span
                                v-else
                                class="flex size-full items-center justify-center bg-navy-950 text-3xl font-bold tracking-tight text-white"
                            >
                                {{ initials }}
                            </span>

                            <span
                                class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-navy-950/55 text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100"
                            >
                                <Camera class="size-5" aria-hidden="true" />
                                <span class="text-xs font-semibold">Change</span>
                            </span>
                        </button>

                        <div class="mt-3 flex flex-col items-center gap-1 text-center">
                            <button
                                type="button"
                                class="text-xs font-semibold text-primary-600 transition-colors hover:text-primary-700"
                                @click="triggerFilePicker"
                            >
                                {{ displayedPhotoUrl ? 'Change photo' : 'Upload photo' }}
                            </button>

                            <button
                                v-if="photo"
                                type="button"
                                class="text-xs font-medium text-muted transition-colors hover:text-danger-600"
                                @click="clearSelectedPhoto"
                            >
                                Cancel selection
                            </button>

                            <p class="mt-0.5 text-[11px] text-muted">
                                JPG, PNG or WebP &middot; Max 5 MB
                            </p>

                            <p v-if="photoError" class="mt-1 text-xs font-medium text-danger-600">
                                {{ photoError }}
                            </p>
                        </div>
                    </div>

                    <!-- Right: Personal Information Fields -->
                    <div class="flex-1 space-y-4">
                        <h2 class="text-sm font-semibold text-navy-950">Personal details</h2>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="profile-name" class="block text-xs font-medium text-ink">
                                    Full name <span class="text-danger-600">*</span>
                                </label>
                                <input
                                    id="profile-name"
                                    v-model="form.name"
                                    name="name"
                                    type="text"
                                    required
                                    autocomplete="name"
                                    :aria-invalid="form.errors.name ? 'true' : undefined"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    :class="{ 'border-danger-600': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs font-medium text-danger-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label for="profile-email" class="block text-xs font-medium text-ink">
                                    Email address <span class="text-danger-600">*</span>
                                </label>
                                <input
                                    id="profile-email"
                                    v-model="form.email"
                                    name="email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    :aria-invalid="form.errors.email ? 'true' : undefined"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    :class="{ 'border-danger-600': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-xs font-medium text-danger-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label for="profile-phone" class="block text-xs font-medium text-ink">
                                    Mobile phone
                                </label>
                                <input
                                    id="profile-phone"
                                    v-model="form.phone"
                                    name="phone"
                                    type="tel"
                                    autocomplete="tel"
                                    placeholder="+60 12-345 6789"
                                    class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    :class="{ 'border-danger-600': form.errors.phone }"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-xs font-medium text-danger-600">
                                    {{ form.errors.phone }}
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="profile-job-title" class="block text-xs font-medium text-ink">
                                    Role / job title
                                </label>
                                <input
                                    id="profile-job-title"
                                    v-model="form.job_title"
                                    name="job_title"
                                    type="text"
                                    autocomplete="organization-title"
                                    placeholder="e.g. Water Resources Engineer"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    :class="{ 'border-danger-600': form.errors.job_title }"
                                />
                                <p v-if="form.errors.job_title" class="mt-1 text-xs font-medium text-danger-600">
                                    {{ form.errors.job_title }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Address -->
                <div class="mt-8 border-t border-line pt-8">
                    <h2 class="text-sm font-semibold text-navy-950">Billing address</h2>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="profile-address-1" class="block text-xs font-medium text-ink">
                                Address line 1
                            </label>
                            <input
                                id="profile-address-1"
                                v-model="form.billing_address_line_1"
                                name="billing_address_line_1"
                                type="text"
                                autocomplete="address-line1"
                                placeholder="Street address or suite"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                            <p v-if="form.errors.billing_address_line_1" class="mt-1 text-xs font-medium text-danger-600">
                                {{ form.errors.billing_address_line_1 }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="profile-address-2" class="block text-xs font-medium text-ink">
                                Address line 2
                            </label>
                            <input
                                id="profile-address-2"
                                v-model="form.billing_address_line_2"
                                name="billing_address_line_2"
                                type="text"
                                autocomplete="address-line2"
                                placeholder="Apartment, building, or floor"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                            <p v-if="form.errors.billing_address_line_2" class="mt-1 text-xs font-medium text-danger-600">
                                {{ form.errors.billing_address_line_2 }}
                            </p>
                        </div>

                        <div>
                            <label for="profile-city" class="block text-xs font-medium text-ink">
                                City
                            </label>
                            <input
                                id="profile-city"
                                v-model="form.billing_city"
                                name="billing_city"
                                type="text"
                                autocomplete="address-level2"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                            <p v-if="form.errors.billing_city" class="mt-1 text-xs font-medium text-danger-600">
                                {{ form.errors.billing_city }}
                            </p>
                        </div>

                        <div>
                            <label for="profile-state" class="block text-xs font-medium text-ink">
                                State / Wilayah
                            </label>
                            <input
                                id="profile-state"
                                v-model="form.billing_state"
                                name="billing_state"
                                type="text"
                                autocomplete="address-level1"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                            <p v-if="form.errors.billing_state" class="mt-1 text-xs font-medium text-danger-600">
                                {{ form.errors.billing_state }}
                            </p>
                        </div>

                        <div>
                            <label for="profile-postcode" class="block text-xs font-medium text-ink">
                                Postcode
                            </label>
                            <input
                                id="profile-postcode"
                                v-model="form.billing_postcode"
                                name="billing_postcode"
                                type="text"
                                autocomplete="postal-code"
                                class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                            <p v-if="form.errors.billing_postcode" class="mt-1 text-xs font-medium text-danger-600">
                                {{ form.errors.billing_postcode }}
                            </p>
                        </div>

                        <div>
                            <label for="profile-country" class="block text-xs font-medium text-ink">
                                Country code
                            </label>
                            <input
                                id="profile-country"
                                v-model="form.billing_country"
                                name="billing_country"
                                type="text"
                                maxlength="2"
                                autocomplete="country"
                                placeholder="MY"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm uppercase text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                            <p v-if="form.errors.billing_country" class="mt-1 text-xs font-medium text-danger-600">
                                {{ form.errors.billing_country }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex items-center justify-end gap-3 border-t border-line pt-6">
                    <button
                        v-if="hasChanges"
                        type="button"
                        class="inline-flex h-10 items-center rounded-lg px-4 text-xs font-semibold text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                        @click="resetChanges"
                    >
                        Discard
                    </button>

                    <button
                        type="submit"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </button>
                </div>
            </form>
        </div>
    </PortalLayout>
</template>
