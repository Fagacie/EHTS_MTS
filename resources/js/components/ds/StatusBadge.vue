<script setup lang="ts">
export type StatusTone = 'success' | 'warning' | 'danger' | 'primary' | 'accent' | 'warm' | 'neutral';

withDefaults(
    defineProps<{
        tone?: StatusTone;
        dot?: boolean;
    }>(),
    {
        tone: 'neutral',
        dot: false,
    },
);

const toneClasses: Record<StatusTone, string> = {
    success: 'bg-success-50 text-success-700 ring-1 ring-inset ring-success-600/15',
    warning: 'bg-warning-50 text-warning-700 ring-1 ring-inset ring-warning-600/15',
    danger: 'bg-danger-50 text-danger-700 ring-1 ring-inset ring-danger-600/15',
    primary: 'bg-primary-50 text-primary-700 ring-1 ring-inset ring-primary-600/15',
    accent: 'bg-accent-50 text-accent-700 ring-1 ring-inset ring-accent-600/15',
    warm: 'bg-warm-50 text-warm-600 ring-1 ring-inset ring-warm-500/20',
    neutral: 'bg-sand-100 text-ink ring-1 ring-inset ring-line',
};

const dotClasses: Record<StatusTone, string> = {
    success: 'bg-success-600',
    warning: 'bg-warning-600',
    danger: 'bg-danger-600',
    primary: 'bg-primary-600',
    accent: 'bg-accent-600',
    warm: 'bg-warm-500',
    neutral: 'bg-muted',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 whitespace-nowrap"
        :class="toneClasses[tone]"
    >
        <span
            v-if="dot"
            class="size-1.5 rounded-full"
            :class="dotClasses[tone]"
            aria-hidden="true"
        />
        <slot />
    </span>
</template>
