<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

export type ButtonVariant = 'primary' | 'secondary' | 'warm' | 'outline' | 'ghost' | 'destructive';
export type ButtonSize = 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        variant?: ButtonVariant;
        size?: ButtonSize;
        pill?: boolean;
        iconOnly?: boolean;
        loading?: boolean;
        disabled?: boolean;
        type?: 'button' | 'submit' | 'reset';
        href?: string | null;
        external?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        pill: false,
        iconOnly: false,
        loading: false,
        disabled: false,
        type: 'button',
        href: null,
        external: false,
    },
);

const isDisabled = computed(() => props.disabled || props.loading);

const variantClasses: Record<ButtonVariant, string> = {
    primary: 'bg-navy-950 text-white hover:bg-navy-800 shadow-xs',
    secondary: 'bg-primary-600 text-white hover:bg-primary-700 shadow-xs',
    warm: 'bg-coral-500 text-white hover:bg-coral-600 shadow-xs',
    outline: 'border border-line-strong bg-white text-ink hover:bg-sand-50 hover:border-navy-700/35',
    ghost: 'bg-transparent text-ink hover:bg-sand-100',
    destructive: 'bg-danger-600 text-white hover:bg-danger-700 shadow-xs',
};

const sizeClasses = computed(() => {
    if (props.iconOnly) {
        return {
            sm: 'size-8',
            md: 'size-10',
            lg: 'size-11',
        }[props.size];
    }

    return {
        sm: 'h-8.5 px-3.5 text-xs gap-1.5',
        md: 'h-10 px-4 text-sm gap-2',
        lg: 'h-11.5 px-5.5 text-sm gap-2',
    }[props.size];
});

const buttonClasses = computed(() => [
    'inline-flex shrink-0 items-center justify-center font-semibold transition-all duration-150 select-none',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600',
    'active:scale-[0.99]',
    props.pill ? 'rounded-full' : 'rounded-xl',
    variantClasses[props.variant],
    sizeClasses.value,
    isDisabled.value ? 'pointer-events-none opacity-55' : 'cursor-pointer',
]);
</script>

<template>
    <a
        v-if="href && external"
        :href="href"
        :class="buttonClasses"
        :aria-disabled="isDisabled || undefined"
    >
        <LoaderCircle v-if="loading" class="size-4 animate-spin" aria-hidden="true" />
        <slot />
    </a>

    <Link
        v-else-if="href"
        :href="href"
        :class="buttonClasses"
        :aria-disabled="isDisabled || undefined"
    >
        <LoaderCircle v-if="loading" class="size-4 animate-spin" aria-hidden="true" />
        <slot />
    </Link>

    <button
        v-else
        :type="type"
        :disabled="isDisabled"
        :class="buttonClasses"
    >
        <LoaderCircle v-if="loading" class="size-4 animate-spin" aria-hidden="true" />
        <slot />
    </button>
</template>
