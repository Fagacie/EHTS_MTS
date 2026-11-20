<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { TabsTrigger, type TabsTriggerProps, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    TabsTriggerProps & {
        class?: HTMLAttributes['class'];
    }
>();

const forwarded = useForwardProps(props);
</script>

<template>
    <TabsTrigger
        v-bind="forwarded"
        :class="
            cn(
                'inline-flex items-center justify-center rounded-md px-3 py-1.5 text-[12px] font-bold whitespace-nowrap transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/35 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-[var(--tab-active-bg)] data-[state=active]:text-[var(--tab-active-fg)] data-[state=active]:shadow-[0_1px_2px_rgba(40,35,56,0.08)]',
                props.class,
            )
        "
    >
        <slot />
    </TabsTrigger>
</template>
