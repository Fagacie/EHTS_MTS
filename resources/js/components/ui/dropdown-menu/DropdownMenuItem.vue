<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import {
    DropdownMenuItem,
    type DropdownMenuItemEmits,
    type DropdownMenuItemProps,
    useForwardPropsEmits,
} from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    DropdownMenuItemProps & {
        class?: HTMLAttributes['class'];
        inset?: boolean;
    }
>();

const emits = defineEmits<DropdownMenuItemEmits>();
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DropdownMenuItem
        v-bind="forwarded"
        :class="
            cn(
                'relative flex cursor-pointer items-center gap-2 rounded-md px-2.5 py-2 text-[13.5px] text-foreground outline-none transition-colors duration-150 select-none data-[highlighted]:bg-secondary data-[highlighted]:text-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                props.inset && 'pl-8',
                props.class,
            )
        "
    >
        <slot />
    </DropdownMenuItem>
</template>
