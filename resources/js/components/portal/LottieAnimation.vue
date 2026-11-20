<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { AnimationItem } from 'lottie-web';
import { useReducedMotion } from '@/composables/useReducedMotion';

const props = withDefaults(
    defineProps<{
        src: string;
        loop?: boolean;
        className?: string;
    }>(),
    {
        loop: true,
        className: 'h-24 w-28',
    },
);

const container = ref<HTMLDivElement | null>(null);
const failed = ref(false);
const { prefersReducedMotion } = useReducedMotion();

let animationInstance: AnimationItem | null = null;
let destroyed = false;

async function initAnimation(): Promise<void> {
    if (!container.value || typeof window === 'undefined') {
        return;
    }

    try {
        const [{ default: lottie }, response] = await Promise.all([
            import('lottie-web/build/player/lottie_light'),
            fetch(props.src, { headers: { Accept: 'application/json' } }),
        ]);

        if (!response.ok || destroyed || !container.value) {
            failed.value = true;
            return;
        }

        const animationData = await response.json();
        if (destroyed || !container.value) {
            return;
        }

        animationInstance?.destroy();
        animationInstance = lottie.loadAnimation({
            container: container.value,
            renderer: 'svg',
            loop: props.loop && !prefersReducedMotion.value,
            autoplay: !prefersReducedMotion.value,
            animationData,
            rendererSettings: {
                preserveAspectRatio: 'xMidYMid meet',
            },
        });

        if (prefersReducedMotion.value) {
            animationInstance.goToAndStop(45, true);
        }
    } catch {
        failed.value = true;
    }
}

watch(prefersReducedMotion, (reduced) => {
    if (!animationInstance) {
        return;
    }

    if (reduced) {
        animationInstance.pause();
    } else {
        animationInstance.play();
    }
});

onMounted(() => {
    void initAnimation();
});

onBeforeUnmount(() => {
    destroyed = true;
    animationInstance?.destroy();
    animationInstance = null;
});
</script>

<template>
    <div
        v-if="!failed"
        ref="container"
        :class="className"
        aria-hidden="true"
    />
</template>
