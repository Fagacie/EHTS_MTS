    <script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type BannerVariant = 'stormwater' | 'basin' | 'ecohydrology';

const props = withDefaults(
    defineProps<{
        coverImageUrl: string | null;
        categorySlug: string;
        title: string;
        aspectClass?: string;
        borderedBottom?: boolean;
    }>(),
    {
        aspectClass: 'aspect-[16/9]',
        borderedBottom: true,
    },
);

const variant = computed<BannerVariant>(() => {
    const slug = props.categorySlug.toLowerCase();

    if (slug.includes('stormwater') || slug.includes('msma')) {
        return 'stormwater';
    }

    if (slug.includes('river') || slug.includes('flood') || slug.includes('basin')) {
        return 'basin';
    }

    if (slug.includes('water') || slug.includes('eco')) {
        return 'ecohydrology';
    }

    const hash = Array.from(slug).reduce((sum, ch) => sum + ch.charCodeAt(0), 0);
    const order: BannerVariant[] = ['stormwater', 'basin', 'ecohydrology'];

    return order[hash % order.length] ?? 'stormwater';
});
</script>

<template>
    <div
        :class="
            cn(
                'relative w-full overflow-hidden bg-[#272d38]/[0.03]',
                aspectClass,
                borderedBottom && 'border-b border-[#272d38]/10',
            )
        "
    >
        <img
            v-if="coverImageUrl"
            :src="coverImageUrl"
            :alt="title"
            loading="lazy"
            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02] motion-reduce:transition-none motion-reduce:group-hover:scale-100"
        >

        <svg
            v-else-if="variant === 'stormwater'"
            viewBox="0 0 640 360"
            fill="none"
            aria-hidden="true"
            class="h-full w-full"
        >
            <g stroke="#272d38" stroke-opacity="0.06" stroke-width="1">
                <line x1="128" y1="0" x2="128" y2="360" />
                <line x1="256" y1="0" x2="256" y2="360" />
                <line x1="384" y1="0" x2="384" y2="360" />
                <line x1="512" y1="0" x2="512" y2="360" />
                <line x1="0" y1="90" x2="640" y2="90" />
                <line x1="0" y1="180" x2="640" y2="180" />
                <line x1="0" y1="270" x2="640" y2="270" />
            </g>
            <g stroke="#272d38" stroke-linecap="round">
                <path
                    d="M-20 264 C 95 264, 155 138, 260 138 C 365 138, 435 234, 660 234"
                    stroke-opacity="0.22"
                    stroke-width="1.5"
                />
                <path
                    d="M-20 238 C 110 238, 170 112, 275 112 C 380 112, 450 208, 660 208"
                    stroke-opacity="0.14"
                    stroke-width="1.25"
                />
                <path
                    d="M-20 290 C 85 290, 145 164, 248 164 C 350 164, 425 260, 660 260"
                    stroke-opacity="0.12"
                    stroke-width="1.25"
                />
                <path
                    d="M-20 210 C 125 210, 185 88, 292 88 C 398 88, 468 180, 660 180"
                    stroke-opacity="0.09"
                    stroke-width="1"
                />
                <path
                    d="M-20 316 C 78 316, 138 190, 238 190 C 338 190, 415 286, 660 286"
                    stroke-opacity="0.08"
                    stroke-width="1"
                />
            </g>
            <circle cx="260" cy="138" r="3" fill="#272d38" fill-opacity="0.24" />
            <line x1="256" y1="84" x2="264" y2="84" stroke="#272d38" stroke-opacity="0.2" stroke-width="1" />
            <line x1="260" y1="80" x2="260" y2="88" stroke="#272d38" stroke-opacity="0.2" stroke-width="1" />
        </svg>

        <svg
            v-else-if="variant === 'basin'"
            viewBox="0 0 640 360"
            fill="none"
            aria-hidden="true"
            class="h-full w-full"
        >
            <g stroke="#272d38" stroke-opacity="0.06" stroke-width="1">
                <line x1="160" y1="0" x2="160" y2="360" />
                <line x1="320" y1="0" x2="320" y2="360" />
                <line x1="480" y1="0" x2="480" y2="360" />
                <line x1="0" y1="120" x2="640" y2="120" />
                <line x1="0" y1="240" x2="640" y2="240" />
            </g>
            <g stroke="#272d38" stroke-linecap="round">
                <path
                    d="M-30 92 C 110 70, 175 186, 315 172 C 455 158, 505 248, 670 232"
                    stroke-opacity="0.22"
                    stroke-width="1.5"
                />
                <path
                    d="M-30 66 C 122 44, 188 158, 326 144 C 466 130, 518 220, 670 204"
                    stroke-opacity="0.14"
                    stroke-width="1.25"
                />
                <path
                    d="M-30 122 C 98 100, 162 214, 304 200 C 442 186, 492 276, 670 260"
                    stroke-opacity="0.14"
                    stroke-width="1.25"
                />
                <path
                    d="M-30 156 C 88 138, 152 246, 294 232 C 432 218, 482 308, 670 292"
                    stroke-opacity="0.09"
                    stroke-width="1"
                />
                <path
                    d="M-30 40 C 135 20, 200 130, 338 116 C 478 102, 530 192, 670 176"
                    stroke-opacity="0.08"
                    stroke-width="1"
                />
            </g>
            <circle cx="315" cy="172" r="3" fill="#272d38" fill-opacity="0.24" />
        </svg>

        <svg
            v-else
            viewBox="0 0 640 360"
            fill="none"
            aria-hidden="true"
            class="h-full w-full"
        >
            <g stroke="#272d38" stroke-opacity="0.06" stroke-width="1">
                <line x1="0" y1="180" x2="640" y2="180" />
                <line x1="320" y1="0" x2="320" y2="360" />
            </g>
            <g stroke="#272d38">
                <ellipse cx="420" cy="175" rx="76" ry="52" stroke-opacity="0.2" stroke-width="1.5" />
                <ellipse cx="412" cy="178" rx="124" ry="84" stroke-opacity="0.14" stroke-width="1.25" />
                <ellipse cx="400" cy="182" rx="176" ry="118" stroke-opacity="0.11" stroke-width="1.15" />
                <ellipse cx="386" cy="186" rx="236" ry="154" stroke-opacity="0.08" stroke-width="1" />
                <path
                    d="M-20 245 C 120 220, 210 156, 368 174 C 468 186, 540 152, 660 120"
                    stroke-opacity="0.18"
                    stroke-width="1.25"
                    stroke-dasharray="4 4"
                />
            </g>
            <circle cx="420" cy="175" r="3" fill="#272d38" fill-opacity="0.24" />
        </svg>
    </div>
</template>
