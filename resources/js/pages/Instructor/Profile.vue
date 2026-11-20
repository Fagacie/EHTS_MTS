<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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

const form = useForm({
    display_name: props.profile.displayName || props.user.name,
    professional_title: props.profile.professionalTitle || '',
    qualifications: props.profile.qualifications || '',
    short_bio: props.profile.shortBio || '',
    specialisations_text: props.profile.specialisationsText || '',
    biography: props.profile.biography || '',
});

function submitProfile() {
    form.put(props.updateUrl, {
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
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
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
            <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div class="border-b border-[#E5E1EB] pb-4">
                    <h2 class="text-base font-bold text-[#292536]">Professional Credentials & Biography</h2>
                    <p class="mt-0.5 text-xs text-[#625D6D]">
                        Public directory listing and session brochure visibility is governed by administrators.
                    </p>
                </div>

                <form @submit.prevent="submitProfile" class="mt-6 space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="display_name" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                                Public Display Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="display_name"
                                type="text"
                                v-model="form.display_name"
                                required
                                class="mt-1 w-full rounded-xl border border-[#E5E1EB] bg-white px-3.5 py-2 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                            />
                            <p v-if="form.errors.display_name" class="mt-1 text-xs text-red-600">
                                {{ form.errors.display_name }}
                            </p>
                        </div>

                        <div>
                            <label for="professional_title" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                                Professional Title
                            </label>
                            <input
                                id="professional_title"
                                type="text"
                                v-model="form.professional_title"
                                placeholder="Principal Hydrologist / Senior Water Resources Engineer"
                                class="mt-1 w-full rounded-xl border border-[#E5E1EB] bg-white px-3.5 py-2 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="qualifications" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                            Academic & Professional Qualifications
                        </label>
                        <input
                            id="qualifications"
                            type="text"
                            v-model="form.qualifications"
                            placeholder="PhD (Hydrology), BEM P.Eng, MIEM, Certified Flood Modeller"
                            class="mt-1 w-full rounded-xl border border-[#E5E1EB] bg-white px-3.5 py-2 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                        />
                    </div>

                    <div>
                        <label for="short_bio" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                            Short Summary Bio
                        </label>
                        <input
                            id="short_bio"
                            type="text"
                            v-model="form.short_bio"
                            placeholder="1-sentence summary displayed on session cards and marketing brochures"
                            class="mt-1 w-full rounded-xl border border-[#E5E1EB] bg-white px-3.5 py-2 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                        />
                    </div>

                    <div>
                        <label for="specialisations_text" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                            Domain Specialisations (Comma-Separated)
                        </label>
                        <input
                            id="specialisations_text"
                            type="text"
                            v-model="form.specialisations_text"
                            placeholder="Flood Forecasting, HEC-RAS 2D, Urban Stormwater MSMA, Hydrodynamic Modelling"
                            class="mt-1 w-full rounded-xl border border-[#E5E1EB] bg-white px-3.5 py-2 text-sm text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                        />
                    </div>

                    <div>
                        <label for="biography" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                            Professional Biography
                        </label>
                        <textarea
                            id="biography"
                            v-model="form.biography"
                            rows="6"
                            placeholder="Provide your full background, consulting expertise, research publications, and training experience..."
                            class="mt-1 w-full rounded-xl border border-[#E5E1EB] bg-white p-3.5 text-sm leading-relaxed text-[#292536] focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                        />
                    </div>

                    <div class="flex justify-end border-t border-[#E5E1EB] pt-4">
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-[#7057D9] px-6 text-xs font-bold text-white shadow-none hover:bg-[#5942B5] disabled:bg-[#E5E1EB]"
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
