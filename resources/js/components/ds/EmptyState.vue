<script setup lang="ts">
import type { Component } from 'vue';
import { Inbox } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: Component;
    }>(),
    {
        description: '',
    },
);
</script>

<template>
    <div class="rounded-2xl border border-line bg-sand-50/70 px-6 py-14 text-center">
        <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-white text-navy-800 shadow-card ring-1 ring-line">
            <component :is="icon ?? Inbox" class="size-5" aria-hidden="true" />
        </div>
        <h3 class="mt-4 text-base font-semibold text-navy-900">{{ title }}</h3>
        <p v-if="description" class="mx-auto mt-1.5 max-w-md text-sm leading-relaxed text-muted">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-6 flex justify-center">
            <slot name="action" />
        </div>
    </div>
</template>
