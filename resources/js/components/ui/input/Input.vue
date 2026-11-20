<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    class?: HTMLAttributes['class'];
    modelValue?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function handleInput(event: Event): void {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <input
        v-bind="$attrs"
        :value="modelValue"
        :class="
            cn(
                'flex h-10 w-full rounded-lg border border-input bg-card px-3.5 py-2 text-[14px] text-foreground placeholder:text-muted-foreground transition-colors duration-150 focus-visible:border-ring focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-ring/15 disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
        @input="handleInput"
    >
</template>
