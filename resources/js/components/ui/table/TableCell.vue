<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <td :class="cn('px-4 py-3.5 align-middle text-[13px] text-foreground', props.class)">
        <slot />
    </td>
</template>
