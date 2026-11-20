<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, FileText, Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorCompletionProps } from '@/types/etms';

const props = defineProps<InstructorCompletionProps>();

const form = useForm({
    completion_notes: props.completionNotes || '',
});

function submitReport() {
    form.post(props.submitUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Completion Report — ${session.sessionCode} — Ecohydrotech Training`" />

    <InstructorLayout
        :breadcrumb="`${session.sessionCode} / Completion`"
        :title="`${session.sessionCode} — ${session.title}`"
        subtitle="Delivery Summary & Operational Remarks for Administrator Review (INS-05)"
    >
        <div class="mx-auto max-w-3xl space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-md bg-[#F1EDFB] px-2.5 py-1 font-mono text-xs font-bold text-[#5942B5]">
                            {{ session.sessionCode }}
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#7057D9]">
                            Session Completion Report (INS-05)
                        </span>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#292536]">
                        {{ session.title }}
                    </h1>
                </div>

                <Button as-child variant="outline" size="sm" class="h-9 rounded-xl border-[#E5E1EB] px-3.5 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]">
                    <Link :href="session.showUrl">
                        <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />
                        Back to Session
                    </Link>
                </Button>
            </div>

            <!-- Completion Notes Form Card -->
            <div class="rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div class="border-b border-[#E5E1EB] pb-4">
                    <h2 class="text-base font-bold text-[#292536]">Delivery Summary & Completion Notes</h2>
                    <p class="mt-0.5 text-xs text-[#625D6D]">
                        Record syllabus coverage, practical lab outcomes, software exercises performed, and operational remarks for administrator review.
                    </p>
                </div>

                <form @submit.prevent="submitReport" class="mt-5 space-y-4">
                    <div>
                        <label for="completion_notes" class="block text-xs font-bold uppercase tracking-wider text-[#625D6D]">
                            Session Completion Report <span class="text-red-500">*</span>
                        </label>
                        <p class="mt-0.5 text-xs text-[#625D6D]">
                            Summarize technical topics covered, practical exercises completed, and cohort observations (minimum 10 characters).
                        </p>
                        <textarea
                            id="completion_notes"
                            v-model="form.completion_notes"
                            rows="7"
                            required
                            placeholder="Summarize technical topics covered, practical exercises completed, questions raised, and cohort observations..."
                            class="mt-2 w-full rounded-xl border border-[#E5E1EB] bg-white p-3.5 text-sm leading-relaxed text-[#292536] placeholder-[#625D6D]/60 focus:border-[#7057D9] focus:outline-none focus:ring-1 focus:ring-[#7057D9]"
                        />
                        <p v-if="form.errors.completion_notes" class="mt-1 text-xs font-medium text-red-600">
                            {{ form.errors.completion_notes }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between border-t border-[#E5E1EB] pt-4">
                        <span class="text-xs text-[#625D6D]">
                            Characters: <strong class="text-[#292536]">{{ form.completion_notes.length }}</strong> / 5000
                        </span>
                        <Button
                            type="submit"
                            :disabled="form.processing || form.completion_notes.trim().length < 10"
                            class="rounded-xl bg-[#7057D9] px-6 text-xs font-bold text-white shadow-none hover:bg-[#5942B5] disabled:cursor-not-allowed disabled:bg-[#E5E1EB] disabled:text-[#625D6D]"
                        >
                            <Save class="mr-1.5 h-4 w-4" />
                            {{ form.processing ? 'Saving Report...' : 'Save Completion Report' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </InstructorLayout>
</template>
