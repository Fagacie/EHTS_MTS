<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import {
    DropdownMenuContent,
    type DropdownMenuContentEmits,
    type DropdownMenuContentProps,
    DropdownMenuPortal,
    useForwardPropsEmits,
} from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<
        DropdownMenuContentProps & {
            class?: HTMLAttributes['class'];
        }
    >(),
    {
        sideOffset: 6,
        align: 'end',
    },
);

const emits = defineEmits<DropdownMenuContentEmits>();
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DropdownMenuPortal>
        <DropdownMenuContent
            v-bind="{ ...$attrs, ...forwarded }"
            :class="
                cn(
                    'z-50 min-w-56 overflow-hidden rounded-xl border border-border bg-popover p-1 text-popover-foreground shadow-[var(--card-shadow)] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
                    props.class,
                )
            "
        >
            <slot />
        </DropdownMenuContent>
    </DropdownMenuPortal>
</template>
