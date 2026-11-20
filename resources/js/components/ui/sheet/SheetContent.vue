<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import {
    DialogClose,
    DialogContent,
    type DialogContentEmits,
    type DialogContentProps,
    DialogOverlay,
    DialogPortal,
    useForwardPropsEmits,
} from 'reka-ui';
import { X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<
        DialogContentProps & {
            class?: HTMLAttributes['class'];
            closeClass?: HTMLAttributes['class'];
            side?: 'left' | 'right';
        }
    >(),
    {
        side: 'right',
    },
);

const emits = defineEmits<DialogContentEmits>();
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DialogPortal>
        <DialogOverlay
            class="fixed inset-0 z-50 bg-[#1F1A2B]/45 transition-opacity duration-150 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
        />
        <DialogContent
            v-bind="{ ...$attrs, ...forwarded }"
            :class="
                cn(
                    'fixed inset-y-0 z-50 flex w-full max-w-xs flex-col gap-4 border-border bg-card p-6 text-foreground transition ease-out data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:duration-150 data-[state=open]:duration-150 focus-visible:outline-none',
                    props.side === 'right'
                        ? 'right-0 border-l data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right'
                        : 'left-0 border-r data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left',
                    props.class,
                )
            "
        >
            <slot />

            <DialogClose
                :class="
                    cn(
                        'absolute top-4 right-4 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground transition-colors duration-150 hover:bg-secondary hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/35',
                        props.closeClass,
                    )
                "
                aria-label="Close menu"
            >
                <X class="h-4 w-4" aria-hidden="true" />
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
