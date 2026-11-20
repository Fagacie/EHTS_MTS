import { onBeforeUnmount, onMounted, ref } from 'vue';

export function useReducedMotion() {
    const prefersReducedMotion = ref(false);
    let mediaQuery: MediaQueryList | null = null;

    const handleChange = (event: MediaQueryListEvent) => {
        prefersReducedMotion.value = event.matches;
    };

    onMounted(() => {
        if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
            return;
        }

        mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        prefersReducedMotion.value = mediaQuery.matches;

        if (typeof mediaQuery.addEventListener === 'function') {
            mediaQuery.addEventListener('change', handleChange);
        }
    });

    onBeforeUnmount(() => {
        if (mediaQuery && typeof mediaQuery.removeEventListener === 'function') {
            mediaQuery.removeEventListener('change', handleChange);
        }
    });

    return {
        prefersReducedMotion,
    };
}
