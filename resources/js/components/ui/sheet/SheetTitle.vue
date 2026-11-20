<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { DialogTitle, type DialogTitleProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    DialogTitleProps & {
        class?: HTMLAttributes['class'];
    }
>();
</script>

<template>
    <DialogTitle
        v-bind="props"
        :class="cn('text-[16px] font-bold text-current', props.class)"
    >
        <slot />
    </DialogTitle>
</template>
