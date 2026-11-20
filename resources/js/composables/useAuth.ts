import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import type { EtmsSharedProps } from '@/types/etms';

export function useAuth() {
    const page = usePage<EtmsSharedProps>();
    const loggingOut = ref(false);

    const user = computed(() => page.props.auth.user);
    const isAuthenticated = computed(() => user.value !== null);
    const portals = computed(() => user.value?.availablePortals ?? []);
    const hasMultiplePortals = computed(() => portals.value.length > 1);
    const isCustomer = computed(() => user.value?.isCustomer ?? false);
    const isInstructor = computed(() => user.value?.isInstructor ?? false);
    const isAdministrator = computed(() => user.value?.isAdministrator ?? false);
    const defaultDashboardUrl = computed(
        () => user.value?.defaultDashboardUrl ?? page.props.routes.home,
    );

    function logout(): void {
        if (loggingOut.value) {
            return;
        }

        loggingOut.value = true;

        router.post(
            page.props.routes.logout,
            {},
            {
                onFinish: () => {
                    loggingOut.value = false;
                },
            },
        );
    }

    return {
        user,
        isAuthenticated,
        portals,
        hasMultiplePortals,
        isCustomer,
        isInstructor,
        isAdministrator,
        defaultDashboardUrl,
        loggingOut,
        logout,
    };
}
