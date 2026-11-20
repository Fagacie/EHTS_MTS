<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicFooter from '@/components/public/PublicFooter.vue';
import PublicHeader from '@/components/public/PublicHeader.vue';
import type { EtmsSharedProps } from '@/types/etms';

const page = usePage<EtmsSharedProps>();
const flash = computed(() => page.props.flash);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-[#272d38]">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-[#272d38] focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:outline-none"
        >
            Skip to main content
        </a>

        <PublicHeader />

        <div
            v-if="flash.status || flash.error"
            class="border-b border-[#272d38]/10 bg-white py-3"
        >
            <div class="mx-auto max-w-6xl space-y-2 px-4 sm:px-6 lg:px-8">
                <p
                    v-if="flash.status"
                    role="status"
                    class="rounded-xl border border-[#272d38]/15 bg-white px-4 py-2.5 text-[15px] text-[#272d38]"
                >
                    {{ flash.status }}
                </p>
                <p
                    v-if="flash.error"
                    role="alert"
                    class="rounded-xl border border-[#272d38]/30 bg-white px-4 py-2.5 text-[15px] font-semibold text-[#272d38]"
                >
                    {{ flash.error }}
                </p>
            </div>
        </div>

        <main id="main-content" class="flex-1 bg-white">
            <slot />
        </main>

        <PublicFooter />
    </div>
</template>
