<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <tr :class="cn('transition-colors duration-150 hover:bg-[var(--table-row-hover)]', props.class)">
        <slot />
    </tr>
</template>
