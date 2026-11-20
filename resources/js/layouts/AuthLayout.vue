<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    <div class="flex min-h-screen flex-col justify-center bg-white px-4 py-10 text-[#272d38] sm:px-6">
        <div :class="['mx-auto w-full', wide ? 'max-w-lg' : 'max-w-md']">
            <div class="mb-6 flex justify-center">
                <Link
                    :href="routes.home"
                    class="inline-flex items-center gap-3 rounded-lg px-2 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30"
                >
                    <img
                        :src="app.logoUrl"
                        :alt="app.brandName"
                        width="32"
                        height="32"
                        class="h-8 w-8 shrink-0"
                    >
                    <span class="text-[20px] font-bold text-[#272d38]">
                        {{ app.brandName }}
                    </span>
                </Link>
            </div>

            <Card class="overflow-hidden">
                <CardHeader v-if="heading" class="border-b border-[#272d38]/10 pb-5">
                    <CardTitle class="text-[22px] font-bold text-[#272d38]">
                        {{ heading }}
                    </CardTitle>
                    <CardDescription
                        v-if="subheading"
                        class="mt-1.5 text-[15px] leading-relaxed text-[#272d38]/65"
                    >
                        {{ subheading }}
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-5 p-6 sm:p-7">
                    <p
                        v-if="flash.status"
                        role="status"
                        class="rounded-xl border border-[#272d38]/15 bg-[#272d38]/[0.03] px-4 py-3 text-[14px] font-semibold text-[#272d38]"
                    >
                        {{ flash.status }}
                    </p>

                    <p
                        v-if="flash.error"
                        role="alert"
                        class="rounded-xl border border-[#272d38]/30 bg-white px-4 py-3 text-[14px] font-bold text-[#272d38]"
                    >
                        {{ flash.error }}
                    </p>

                    <slot />
                </CardContent>
            </Card>

            <p class="mt-6 text-center text-[14px] text-[#272d38]/65">
                <Link
                    :href="routes.home"
                    class="inline-flex items-center gap-1.5 font-semibold text-[#272d38] hover:underline underline-offset-4"
                >
                    <ArrowLeft class="h-3.5 w-3.5" aria-hidden="true" />
                    <span>Return to Ecohydrotech Home</span>
                </Link>
            </p>
        </div>
    </div>
</template>
