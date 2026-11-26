<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorCompletionProps } from '@/types/etms';

const props = defineProps<InstructorCompletionProps>();

const form = useForm({
    completion_notes: props.completionNotes || '',
});

function submitReport(): void {
    form.post(props.submitUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Completion Report — ${session.sessionCode} — Ecohydrotech Training`" />

    <InstructorLayout :breadcrumb="`${session.sessionCode} / Completion`">
        <div class="mx-auto max-w-3xl space-y-6">
            <!-- Header -->
            <div>
                <Link
                    :href="session.showUrl"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted transition hover:text-ink"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Back to {{ session.sessionCode }}</span>
                </Link>
                <h1 class="type-page-title mt-1.5 text-ink">Completion Report</h1>
                <p class="mt-1 text-sm text-muted">
                    {{ session.sessionCode }} · {{ session.title }}
                </p>
            </div>

            <!-- Form Card -->
            <section class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card sm:p-6">
                <div class="border-b border-line/80 pb-3.5">
                    <h2 class="text-base font-semibold text-ink">Delivery summary &amp; remarks</h2>
                    <p class="mt-0.5 text-xs text-muted">
                        Record syllabus coverage, practical lab outcomes, software exercises completed, and cohort observations for administrator review.
                    </p>
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submitReport">
                    <label class="flex flex-col gap-1.5 text-xs font-medium text-muted">
                        <span>Completion notes (minimum 10 characters)</span>
                        <textarea
                            v-model="form.completion_notes"
                            rows="8"
                            required
                            maxlength="5000"
                            placeholder="Summarize technical topics covered, practical exercises completed, questions raised, and cohort observations..."
                            class="rounded-xl border border-line bg-surface p-3.5 text-sm leading-relaxed text-ink focus:border-primary-600 focus:outline-none"
                        />
                        <span v-if="form.errors.completion_notes" class="text-danger-600">
                            {{ form.errors.completion_notes }}
                        </span>
                    </label>

                    <div class="flex items-center justify-between border-t border-line/80 pt-4">
                        <span class="tabular text-xs text-muted">
                            {{ form.completion_notes.length }} / 5000 characters
                        </span>
                        <button
                            type="submit"
                            :disabled="form.processing || form.completion_notes.trim().length < 10"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-navy-950 px-5 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-45"
                        >
                            <Save class="size-4" aria-hidden="true" />
                            <span>{{ form.processing ? 'Saving…' : 'Save completion report' }}</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </InstructorLayout>
</template>
