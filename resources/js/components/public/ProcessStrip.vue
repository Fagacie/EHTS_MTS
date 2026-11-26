<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        reservationHoldMinutes?: number;
    }>(),
    {
        reservationHoldMinutes: 15,
    },
);

const steps = [
    {
        number: '01',
        title: 'Select a session or module',
        description: 'Pick an upcoming live intake or a standalone self-paced module.',
    },
    {
        number: '02',
        title: 'Reserve & complete checkout',
        description: `Your seat is held for ${props.reservationHoldMinutes} minutes while payment is confirmed in MYR.`,
    },
    {
        number: '03',
        title: 'Attend & access materials',
        description: 'Join in person and access manuals or session recordings inside your portal.',
    },
    {
        number: '04',
        title: 'Download your certificate',
        description: 'Receive a serialized digital certificate once attendance is verified.',
    },
];
</script>

<template>
    <section aria-labelledby="process-strip-heading" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-line bg-sand-50 px-6 py-8 sm:px-8 sm:py-10">
            <h2 id="process-strip-heading" class="type-section-title text-navy-950">
                How enrolment works
            </h2>

            <ol class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4 lg:gap-0 lg:divide-x lg:divide-line">
                <li
                    v-for="(step, idx) in steps"
                    :key="step.number"
                    class="flex flex-col"
                    :class="idx > 0 ? 'lg:pl-6' : ''"
                >
                    <div class="lg:pr-6">
                        <span class="tabular text-xs font-bold tracking-[0.1em] text-coral-600">
                            {{ step.number }}
                        </span>
                        <h3 class="mt-2 text-sm font-semibold text-navy-950">
                            {{ step.title }}
                        </h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-muted">
                            {{ step.description }}
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </section>
</template>
