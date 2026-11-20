<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { ChevronDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    class?: HTMLAttributes['class'];
    wrapperClass?: HTMLAttributes['class'];
    modelValue?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function handleChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div :class="cn('relative', wrapperClass)">
        <select
            v-bind="$attrs"
            :value="modelValue"
            :class="
                cn(
                    'flex h-10 w-full appearance-none rounded-lg border border-input bg-card py-2 pr-9 pl-3.5 text-[14px] text-foreground transition-colors duration-150 focus-visible:border-ring focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-ring/15 disabled:cursor-not-allowed disabled:opacity-50',
                    props.class,
                )
            "
            @change="handleChange"
        >
            <slot />
        </select>
        <ChevronDown
            class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />
    </div>
</template>
