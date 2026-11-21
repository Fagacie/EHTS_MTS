<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    CheckCircle2,
    ExternalLink,
    Save,
    UserCheck,
} from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorProfileProps } from '@/types/etms';

const props = defineProps<InstructorProfileProps>();
const photo = ref<File | null>(null);
const photoPreview = ref<string | null>(props.profile.photoUrl);

function handlePhoto(event: Event): void {
    photo.value = (event.target as HTMLInputElement).files?.[0] ?? null;
    photoPreview.value = photo.value ? URL.createObjectURL(photo.value) : props.profile.photoUrl;
}

const form = useForm({
    display_name: props.profile.displayName || props.user.name,
    professional_title: props.profile.professionalTitle || '',
    qualifications: props.profile.qualifications || '',
    short_bio: props.profile.shortBio || '',
    specialisations_text: props.profile.specialisationsText || '',
    biography: props.profile.biography || '',
});

function submitProfile() {
    router.post(props.updateUrl, {
        _method: 'PUT',
        display_name: form.display_name,
        professional_title: form.professional_title,
        qualifications: form.qualifications,
        short_bio: form.short_bio,
        specialisations_text: form.specialisations_text,
        biography: form.biography,
        photo: photo.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="My Instructor Profile — Ecohydrotech Training" />

    <InstructorLayout
        breadcrumb="Instructor Profile"
        title="My Instructor Profile"
        subtitle="Maintain your professional biography, qualifications, and domain specialisations displayed on public session pages (INS-07, FR-15)."
    >
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-[#E5E1EB] bg-white p-5">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-[#292536]">
                        My Instructor Profile
                    </h1>
                    <p class="mt-1 text-sm text-[#625D6D]">
                        Professional credentials & public profile management for {{ user.email }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <PortalStatusBadge
                        :status="profile.isPublic ? 'published' : 'draft'"
                        :label="profile.isPublic ? 'Published on Public Directory' : 'Hidden from Public Directory'"
                    />
                    <a
                        v-if="profile.isPublic && profile.publicProfileUrl"
                        :href="profile.publicProfileUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-[#5942B5] hover:underline"
                    >
                        View Public Profile
                        <ExternalLink class="h-3.5 w-3.5" />
                    </a>
                </div>
            </div>

            <!-- Profile Form Card -->
            <div class="rounded-lg border border-[#E5E1EB] bg-white p-5 sm:p-6">
                <div class="border-b border-[#E5E1EB] pb-4">
                    <h2 class="text-base font-bold text-[#292536]">Professional Credentials & Biography</h2>
                    <p class="mt-0.5 text-xs text-[#625D6D]">
                        Public directory listing and session brochure visibility is governed by administrators.
                    </p>
                </div>

                <form @submit.prevent="submitProfile" class="mt-6 space-y-5">
                    <div class="flex items-center gap-4 rounded-lg border border-[#E5E1EB] bg-[#FAF9FC] p-4">
                        <img
                            v-if="photoPreview"
                            :src="photoPreview"
                            alt="Instructor profile photo"
                            class="h-24 w-24 rounded-full object-cover border border-[#E5E1EB]"
                        />
                        <span v-else class="grid h-24 w-24 place-items-center rounded-full bg-[#F1EDFB] text-2xl font-bold text-[#5942B5]">
                            {{ (props.profile.displayName || props.user.name).slice(0, 1).toUpperCase() }}
                        </span>
                        <div>
                            <label for="instructor-photo" class="block text-sm font-bold text-[#292536]">Profile photo</label>
                            <p class="mt-1 text-xs text-[#625D6D]">PNG, JPG or WebP. Maximum 5 MB.</p>
                            <input
                                id="instructor-photo"
                                name="photo"
                                type="file"
                                accept="image/*"
                                class="mt-2 block max-w-xs text-xs text-[#625D6D]"
                                @change="handlePhoto"
                            />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="display_name" class="block text-sm font-medium text-[#403B4B]">
                                Public Display Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="display_name"
                                type="text"
                                v-model="form.display_name"
                                required
                                class="mt-1 h-10 w-full rounded-md border border-[#E5E1EB] bg-white px-3 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-2 focus:ring-[#7057D9]/15"
                            />
                            <p v-if="form.errors.display_name" class="mt-1 text-xs text-red-600">
                                {{ form.errors.display_name }}
                            </p>
                        </div>

                        <div>
                            <label for="professional_title" class="block text-sm font-medium text-[#403B4B]">
                                Professional Title
                            </label>
                            <input
                                id="professional_title"
                                type="text"
                                v-model="form.professional_title"
                                placeholder="Principal Hydrologist / Senior Water Resources Engineer"
                                class="mt-1 h-10 w-full rounded-md border border-[#E5E1EB] bg-white px-3 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-2 focus:ring-[#7057D9]/15"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="qualifications" class="block text-sm font-medium text-[#403B4B]">
                            Academic & Professional Qualifications
                        </label>
                        <input
                            id="qualifications"
                            type="text"
                            v-model="form.qualifications"
                            placeholder="PhD (Hydrology), BEM P.Eng, MIEM, Certified Flood Modeller"
                            class="mt-1 h-10 w-full rounded-md border border-[#E5E1EB] bg-white px-3 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-2 focus:ring-[#7057D9]/15"
                        />
                    </div>

                    <div>
                        <label for="short_bio" class="block text-sm font-medium text-[#403B4B]">
                            Short Summary Bio
                        </label>
                        <input
                            id="short_bio"
                            type="text"
                            v-model="form.short_bio"
                            placeholder="1-sentence summary displayed on session cards and marketing brochures"
                            class="mt-1 h-10 w-full rounded-md border border-[#E5E1EB] bg-white px-3 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-2 focus:ring-[#7057D9]/15"
                        />
                    </div>

                    <div>
                        <label for="specialisations_text" class="block text-sm font-medium text-[#403B4B]">
                            Domain Specialisations (Comma-Separated)
                        </label>
                        <input
                            id="specialisations_text"
                            type="text"
                            v-model="form.specialisations_text"
                            placeholder="Flood Forecasting, HEC-RAS 2D, Urban Stormwater MSMA, Hydrodynamic Modelling"
                            class="mt-1 h-10 w-full rounded-md border border-[#E5E1EB] bg-white px-3 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-2 focus:ring-[#7057D9]/15"
                        />
                    </div>

                    <div>
                        <label for="biography" class="block text-sm font-medium text-[#403B4B]">
                            Professional Biography
                        </label>
                        <textarea
                            id="biography"
                            v-model="form.biography"
                            rows="6"
                            placeholder="Provide your full background, consulting expertise, research publications, and training experience..."
                            class="mt-1 w-full rounded-md border border-[#E5E1EB] bg-white p-3 text-sm leading-relaxed text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-2 focus:ring-[#7057D9]/15"
                        />
                    </div>

                    <div class="flex justify-end border-t border-[#E5E1EB] pt-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-[#7057D9] px-5 py-2 text-sm font-medium text-white hover:bg-[#5942B5] disabled:bg-[#E5E1EB]"
                        >
                            <Save class="mr-1.5 h-4 w-4" />
                            {{ form.processing ? 'Saving...' : 'Save Profile Changes' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </InstructorLayout>
</template>
