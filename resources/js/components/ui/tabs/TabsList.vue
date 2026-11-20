<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { TabsList, type TabsListProps, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    TabsListProps & {
        class?: HTMLAttributes['class'];
    }
>();

const forwarded = useForwardProps(props);
</script>

<template>
    <TabsList
        v-bind="forwarded"
        :class="
            cn(
                'inline-flex flex-wrap items-center gap-0.5 rounded-lg bg-muted p-1 text-muted-foreground',
                props.class,
            )
        "
    >
        <slot />
    </TabsList>
</template>
