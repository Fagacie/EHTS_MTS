<script setup lang="ts">
import { computed } from 'vue';
import { UserRound } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        src?: string | null;
        name?: string | null;
        size?: 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        src: null,
        name: null,
        size: 'md',
    },
);

const sizeClasses = computed(() => ({
    sm: 'size-8',
    md: 'size-10',
    lg: 'size-16',
    xl: 'size-22',
}[props.size]));

const iconClasses = computed(() => ({
    sm: 'size-4',
    md: 'size-5',
    lg: 'size-7',
    xl: 'size-9',
}[props.size]));
</script>

<template>
    <div
        class="relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-sand-100 text-navy-700 ring-1 ring-line"
        :class="sizeClasses"
    >
        <img
            v-if="src"
            :src="src"
            :alt="name ? `${name} avatar` : 'User avatar'"
            class="h-full w-full object-cover"
        />
        <UserRound
            v-else
            :class="iconClasses"
            aria-hidden="true"
        />
    </div>
</template>
