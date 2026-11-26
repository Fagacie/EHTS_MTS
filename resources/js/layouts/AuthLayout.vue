<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, CheckCircle2 } from 'lucide-vue-next';
import { useRoutes } from '@/composables/useRoutes';
import type { EtmsSharedProps } from '@/types/etms';

defineProps<{
    heading?: string;
    subheading?: string;
    wide?: boolean;
}>();

const page = usePage<EtmsSharedProps>();
const app = computed(() => page.props.app);
const flash = computed(() => page.props.flash);
const { routes } = useRoutes();
</script>

<template>
    <div class="flex min-h-screen flex-col justify-center bg-sand-50 px-4 py-10 text-ink sm:px-6">
        <div :class="['mx-auto w-full', wide ? 'max-w-xl' : 'max-w-md']">
            <!-- Top Brand + Back to Website Pill -->
            <div class="mb-6 flex items-center justify-between gap-3">
                <Link
                    :href="routes.home"
                    class="inline-flex items-center rounded-xl py-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <img
                        :src="app.logoUrl"
                        :alt="app.brandName"
                        class="h-11 w-auto object-contain"
                    />
                    <span class="sr-only">{{ app.brandName }}</span>
                </Link>

                <Link
                    :href="routes.home"
                    class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3.5 py-1.5 text-xs font-semibold text-navy-950 shadow-xs transition-colors duration-150 hover:border-line-strong hover:bg-sand-100/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <ArrowLeft class="size-3.5 text-muted" aria-hidden="true" />
                    <span>Back to website</span>
                </Link>
            </div>

            <!-- Crisp Single-Column White Card -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                <div v-if="heading" class="border-b border-line px-6 pt-6 pb-5 sm:px-8 sm:pt-7">
                    <h1 class="text-xl font-bold tracking-tight text-navy-950 sm:text-[22px]">
                        {{ heading }}
                    </h1>
                    <p
                        v-if="subheading"
                        class="mt-1.5 text-sm leading-relaxed text-muted"
                    >
                        {{ subheading }}
                    </p>
                </div>

                <div class="space-y-5 p-6 sm:p-8">
                    <div
                        v-if="flash.status"
                        role="status"
                        class="flex items-start gap-2.5 rounded-xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700 ring-1 ring-inset ring-success-600/20"
                    >
                        <CheckCircle2 class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <span>{{ flash.status }}</span>
                    </div>

                    <div
                        v-if="flash.error"
                        role="alert"
                        class="flex items-start gap-2.5 rounded-xl bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700 ring-1 ring-inset ring-danger-600/20"
                    >
                        <AlertCircle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <span>{{ flash.error }}</span>
                    </div>

                    <slot />
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-muted">
                &copy; {{ app.year }} {{ app.brandName }}
            </p>
        </div>
    </div>
</template>
