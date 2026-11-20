import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { EtmsSharedProps } from '@/types/etms';

export function useRoutes() {
    const page = usePage<EtmsSharedProps>();

    const routes = computed(() => page.props.routes);
    const currentPath = computed(() => page.props.currentPath || '/');

    function isActive(matchPrefix: string): boolean {
        if (matchPrefix.startsWith('#')) {
            return false;
        }

        if (matchPrefix === '/') {
            return currentPath.value === '/';
        }

        return (
            currentPath.value === matchPrefix ||
            currentPath.value.startsWith(`${matchPrefix}/`)
        );
    }

    return {
        routes,
        currentPath,
        isActive,
    };
}
