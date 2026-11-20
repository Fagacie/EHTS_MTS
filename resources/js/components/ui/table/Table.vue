<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div class="relative w-full overflow-x-auto">
        <table :class="cn('w-full caption-bottom border-collapse text-left text-[13.5px]', props.class)">
            <slot />
        </table>
    </div>
</template>
