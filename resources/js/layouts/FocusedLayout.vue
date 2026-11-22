<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { EtmsSharedProps } from '@/types/etms';

defineProps<{
    title?: string;
}>();

const page = usePage<EtmsSharedProps>();
const app = computed(() => page.props.app);
const flash = computed(() => page.props.flash);
</script>

<template>
    <div class="min-h-screen bg-[#FAFAFA] font-sans text-neutral-900">
        <header class="border-b border-neutral-200 bg-white">
            <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-2.5">
                    <img
                        v-if="app.logoUrl"
                        :src="app.logoUrl"
                        alt=""
                        width="24"
                        height="24"
                        class="h-6 w-6 rounded-[6px] object-contain"
                        aria-hidden="true"
                    >
                    <span class="text-sm font-semibold tracking-tight text-neutral-900">
                        {{ app.brandName }}
                    </span>
                </div>
                <span class="text-xs font-medium text-neutral-500">
                    Secure Checkout
                </span>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10">
            <div
                v-if="flash?.status"
                role="status"
                class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900"
            >
                {{ flash.status }}
            </div>

            <div
                v-if="flash?.error"
                role="alert"
                class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900"
            >
                {{ flash.error }}
            </div>

            <slot />
        </main>
    </div>
</template>
