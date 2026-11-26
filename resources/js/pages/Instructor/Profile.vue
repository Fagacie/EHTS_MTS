<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, ExternalLink, Save } from 'lucide-vue-next';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorProfileProps } from '@/types/etms';

const props = defineProps<InstructorProfileProps>();

const photoInput = ref<HTMLInputElement | null>(null);
const photo = ref<File | null>(null);
const photoPreview = ref<string | null>(props.profile.photoUrl);

const form = useForm({
    display_name: props.profile.displayName || props.user.name,
    professional_title: props.profile.professionalTitle || '',
    qualifications: props.profile.qualifications || '',
    short_bio: props.profile.shortBio || '',
    specialisations_text: props.profile.specialisationsText || '',
    biography: props.profile.biography || '',
});

const initials = computed(() =>
    (form.display_name || props.user.name || 'I')
        .split(/\s+/)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join(''),
);

function handlePhoto(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    photo.value = file;
    photoPreview.value = file ? URL.createObjectURL(file) : props.profile.photoUrl;
}

function submitProfile(): void {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
        ...(photo.value ? { photo: photo.value } : {}),
    })).post(props.updateUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            photo.value = null;
        },
    });
}
</script>

<template>
    <Head title="My Profile — Ecohydrotech Training" />

    <InstructorLayout breadcrumb="My Profile">
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title text-ink">My Profile</h1>
                    <p class="mt-1 text-sm text-muted">
                        {{ user.email }} · Credentials and biography shown on public training pages.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <StatusBadge
                        :tone="profile.isPublic ? 'success' : 'neutral'"
                        :label="profile.isPublic ? 'Public directory active' : 'Hidden from directory'"
                    />
                    <a
                        v-if="profile.isPublic && profile.publicProfileUrl"
                        :href="profile.publicProfileUrl"
                        target="_blank"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-line bg-surface px-3.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                    >
                        <span>View public profile</span>
                        <ExternalLink class="size-3.5 text-muted" aria-hidden="true" />
                    </a>
                </div>
            </div>

            <!-- Profile Form Card -->
            <form
                class="rounded-2xl border border-line/85 bg-surface p-6 shadow-card sm:p-8"
                @submit.prevent="submitProfile"
            >
                <div class="flex flex-col gap-8 md:flex-row md:items-start">
                    <!-- Left: Circular Avatar Uploader -->
                    <div class="flex flex-col items-center md:w-44 md:shrink-0">
                        <button
                            type="button"
                            class="group relative size-36 overflow-hidden rounded-full border border-line bg-sand-100 shadow-xs transition hover:border-navy-950/30"
                            aria-label="Upload profile photo"
                            @click="photoInput?.click()"
                        >
                            <img
                                v-if="photoPreview"
                                :src="photoPreview"
                                alt="Instructor profile photo"
                                class="size-full object-cover"
                            />
                            <span
                                v-else
                                class="grid size-full place-items-center text-3xl font-bold text-navy-950"
                            >
                                {{ initials }}
                            </span>
                            <span
                                class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-navy-950/55 text-xs font-medium text-white opacity-0 transition group-hover:opacity-100"
                            >
                                <Camera class="size-5" aria-hidden="true" />
                                <span>Change photo</span>
                            </span>
                        </button>

                        <button
                            type="button"
                            class="mt-3 text-xs font-medium text-primary-600 hover:underline"
                            @click="photoInput?.click()"
                        >
                            {{ photoPreview ? 'Update photo' : 'Upload photo' }}
                        </button>
                        <p class="mt-1 text-center text-[11px] text-muted">
                            JPG, PNG, or WebP · max 5 MB
                        </p>
                        <input
                            ref="photoInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handlePhoto"
                        />
                    </div>

                    <!-- Right: Fields -->
                    <div class="min-w-0 flex-1 space-y-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Public display name</span>
                                <input
                                    v-model="form.display_name"
                                    type="text"
                                    required
                                    maxlength="150"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                                <span v-if="form.errors.display_name" class="text-danger-600">
                                    {{ form.errors.display_name }}
                                </span>
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Professional title</span>
                                <input
                                    v-model="form.professional_title"
                                    type="text"
                                    maxlength="150"
                                    placeholder="e.g., Principal Hydrologist"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                        </div>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Academic &amp; professional qualifications</span>
                            <input
                                v-model="form.qualifications"
                                type="text"
                                maxlength="255"
                                placeholder="e.g., PhD (Hydrology), BEM P.Eng, MIEM"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Short summary</span>
                            <input
                                v-model="form.short_bio"
                                type="text"
                                maxlength="500"
                                placeholder="One-sentence summary shown on session cards"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Domain specialisations (comma-separated)</span>
                            <input
                                v-model="form.specialisations_text"
                                type="text"
                                maxlength="500"
                                placeholder="Flood Forecasting, HEC-RAS 2D, Urban Stormwater MSMA"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Biography</span>
                            <textarea
                                v-model="form.biography"
                                rows="6"
                                maxlength="5000"
                                placeholder="Full background, consulting expertise, publications, and training experience..."
                                class="rounded-xl border border-line bg-surface p-3 text-sm leading-relaxed text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <div class="flex justify-end border-t border-line/80 pt-5">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex h-10 items-center gap-2 rounded-xl bg-navy-950 px-5 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-50"
                            >
                                <Save class="size-4" aria-hidden="true" />
                                <span>{{ form.processing ? 'Saving…' : 'Save changes' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </InstructorLayout>
</template>
