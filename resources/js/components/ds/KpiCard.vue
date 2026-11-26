<script setup lang="ts">
import type { Component } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from 'lucide-vue-next';

export type KpiVariant = 'default' | 'highlight' | 'dark';

withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        caption?: string;
        icon?: Component;
        variant?: KpiVariant;
        href?: string | null;
    }>(),
    {
        caption: '',
        variant: 'default',
        href: null,
    },
);

const cardClasses: Record<KpiVariant, string> = {
    default: 'bg-surface text-ink border border-line/85 shadow-card',
    highlight: 'bg-warm-500 text-navy-950 shadow-elevated',
    dark: 'bg-navy-900 text-white shadow-elevated',
};

const iconWellClasses: Record<KpiVariant, string> = {
    default: 'bg-primary-50 text-primary-600',
    highlight: 'bg-navy-950/12 text-navy-950',
    dark: 'bg-white/12 text-accent-100',
};

const labelClasses: Record<KpiVariant, string> = {
    default: 'text-muted',
    highlight: 'text-navy-950/80',
    dark: 'text-white/70',
};

const captionClasses: Record<KpiVariant, string> = {
    default: 'text-muted',
    highlight: 'text-navy-950/75',
    dark: 'text-white/65',
};

const linkButtonClasses: Record<KpiVariant, string> = {
    default: 'bg-sand-100 text-navy-950 hover:bg-navy-950 hover:text-white',
    highlight: 'bg-navy-950/12 text-navy-950 hover:bg-navy-950 hover:text-white',
    dark: 'bg-white/12 text-white hover:bg-white hover:text-navy-950',
};
</script>

<template>
    <div
        class="relative flex flex-col justify-between rounded-2xl p-5 transition-all duration-150"
        :class="cardClasses[variant]"
    >
        <div class="flex items-start justify-between gap-3">
            <div
                v-if="icon"
                class="flex size-10 items-center justify-center rounded-xl"
                :class="iconWellClasses[variant]"
            >
                <component :is="icon" class="size-5" aria-hidden="true" />
            </div>

            <Link
                v-if="href"
                :href="href"
                :aria-label="`Open ${label}`"
                class="inline-flex size-8 items-center justify-center rounded-full transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                :class="linkButtonClasses[variant]"
            >
                <ArrowUpRight class="size-4" aria-hidden="true" />
            </Link>
        </div>

        <div class="mt-4">
            <p class="text-xs font-semibold tracking-wide" :class="labelClasses[variant]">
                {{ label }}
            </p>
            <p class="tabular mt-1 text-3xl font-bold tracking-tight">
                {{ value }}
            </p>
            <p v-if="caption" class="mt-1.5 text-xs font-medium" :class="captionClasses[variant]">
                {{ caption }}
            </p>
        </div>
    </div>
</template>
