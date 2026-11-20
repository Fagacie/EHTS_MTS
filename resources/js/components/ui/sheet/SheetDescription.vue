<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DialogDescription, type DialogDescriptionProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    DialogDescriptionProps & {
        class?: HTMLAttributes['class'];
    }
>();
</script>

<template>
    <DialogDescription
        v-bind="props"
        :class="cn('text-xs text-neutral-500', props.class)"
    >
        <slot />
    </DialogDescription>
</template>
