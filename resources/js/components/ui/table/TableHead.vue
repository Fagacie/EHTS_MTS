<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <th
        scope="col"
        :class="
            cn(
                'px-4 py-3 text-left text-[12px] font-bold tracking-normal text-muted-foreground whitespace-nowrap',
                props.class,
            )
        "
    >
        <slot />
    </th>
</template>
