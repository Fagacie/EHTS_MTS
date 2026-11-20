<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';

const props = defineProps<{
    href: string;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <Link
        :href="href"
        :class="
            cn(
                'rounded-md text-muted-foreground transition-colors duration-150 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/35',
                props.class,
            )
        "
    >
        <slot />
    </Link>
</template>
