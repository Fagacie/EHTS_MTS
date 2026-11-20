<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Label as RekaLabel, type LabelProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    LabelProps & {
        class?: HTMLAttributes['class'];
    }
>();
</script>

<template>
    <RekaLabel
        :for="props.for"
        :as-child="props.asChild"
        :as="props.as"
        :class="
            cn(
                'block text-[13.5px] font-semibold leading-none text-foreground peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
                props.class,
            )
        "
    >
        <slot />
    </RekaLabel>
</template>
