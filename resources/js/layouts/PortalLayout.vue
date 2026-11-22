<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Award,
    Bell,
    BookOpen,
    CalendarCheck,
    Compass,
    CreditCard,
    LayoutDashboard,
    LogOut,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    Search,
    Settings,
    ShieldCheck,
    UserCheck,
    Video,
    X,
} from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useAuth } from '@/composables/useAuth';
import { useRoutes } from '@/composables/useRoutes';
import { cn, getInitials } from '@/lib/utils';
import type {
    EtmsSharedProps,
    PortalNotificationItem,
    PortalSearchResponse,
    PortalSearchResultItem,
} from '@/types/etms';

interface NavEntry {
    label: string;
    href: string;
    icon: unknown;
    active: boolean;
    isInertia: boolean;
    badge?: number;
}

interface SearchCategoryGroup {
    key: keyof PortalSearchResponse['results'];
    label: string;
    items: PortalSearchResultItem[];
}

const props = withDefaults(
    defineProps<{
        breadcrumb?: string;
        title?: string;
        subtitle?: string;
        unreadCount?: number;
        searchQuery?: string;
    }>(),
    {
        breadcrumb: 'Dashboard',
        searchQuery: '',
    },
);

const emit = defineEmits<{
    'update:searchQuery': [value: string];
}>();

const STORAGE_KEY = 'etms_portal_sidebar_collapsed';

const collapsed = ref(false);
const mobileMenuOpen = ref(false);
const mobileSearchOpen = ref(false);
const notifMenuOpen = ref(false);
const busyNotificationId = ref<string | null>(null);
const busyMarkAll = ref(false);

const localSearch = ref(props.searchQuery);
const searchOpen = ref(false);
const searchLoading = ref(false);
const searchError = ref<string | null>(null);
const searchResponse = ref<PortalSearchResponse | null>(null);
const searchWrapperRef = ref<HTMLElement | null>(null);

let searchTimer: ReturnType<typeof setTimeout> | null = null;
let searchAbortController: AbortController | null = null;

const page = usePage<EtmsSharedProps>();
const app = computed(() => page.props.app);
const flash = computed(() => page.props.flash);
const portalShell = computed(() => page.props.portal ?? null);
const {
    user,
    portals,
    hasMultiplePortals,
    loggingOut,
    logout,
} = useAuth();
const { routes, currentPath, isActive } = useRoutes();

const unreadCountResolved = computed(
    () => props.unreadCount ?? user.value?.unreadNotificationsCount ?? 0,
);

const userInitials = computed(() => getInitials(user.value?.name ?? 'User'));

const recentNotifications = computed(
    () => portalShell.value?.recentNotifications ?? [],
);

const trimmedSearch = computed(() => localSearch.value.trim());

const searchGroups = computed<SearchCategoryGroup[]>(() => {
    const res = searchResponse.value?.results;
    if (!res) {
        return [];
    }

    const rawGroups: SearchCategoryGroup[] = [
        { key: 'bookings', label: 'Bookings', items: res.bookings },
        { key: 'orders', label: 'Orders', items: res.orders },
        { key: 'certificates', label: 'Certificates', items: res.certificates },
        { key: 'modules', label: 'My Learning', items: res.modules },
        { key: 'catalogue', label: 'Training Catalogue', items: res.catalogue },
    ];

    return rawGroups.filter((group) => group.items.length > 0);
});

const workspaceNav = computed<NavEntry[]>(() => [
    {
        label: 'Dashboard',
        href: routes.value.portalDashboard,
        icon: LayoutDashboard,
        active: currentPath.value === '/portal',
        isInertia: true,
    },
    {
        label: 'My Physical Sessions',
        href: routes.value.portalBookings,
        icon: CalendarCheck,
        active: isActive('/portal/bookings'),
        isInertia: true,
    },
    {
        label: 'Recordings & Resources',
        href: routes.value.portalRecordings,
        icon: Video,
        active: isActive('/portal/recordings'),
        isInertia: true,
    },
    {
        label: 'My Certificates',
        href: routes.value.portalCertificates,
        icon: Award,
        active: isActive('/portal/certificates'),
        isInertia: true,
    },
    {
        label: 'My Learning Modules',
        href: routes.value.portalModules,
        icon: BookOpen,
        active: isActive('/portal/modules'),
        isInertia: true,
    },
    {
        label: 'Orders & Payments',
        href: routes.value.portalOrders,
        icon: CreditCard,
        active: isActive('/portal/orders'),
        isInertia: true,
    },
    {
        label: 'Browse Training',
        href: routes.value.sessions,
        icon: Compass,
        active: isActive('/training') || isActive('/sessions'),
        isInertia: true,
    },
]);

const utilityNav = computed<NavEntry[]>(() => [
    {
        label: 'Notifications',
        href: routes.value.portalNotifications,
        icon: Bell,
        active: isActive('/portal/notifications'),
        isInertia: true,
        badge: unreadCountResolved.value > 0 ? unreadCountResolved.value : undefined,
    },
    {
        label: 'Settings',
        href: routes.value.portalProfile,
        icon: Settings,
        active: isActive('/portal/profile') || isActive('/portal/security'),
        isInertia: true,
    },
]);

watch(
    () => props.searchQuery,
    (next) => {
        if (next !== localSearch.value) {
            localSearch.value = next;
        }
    },
);

async function runGlobalSearch(queryText: string): Promise<void> {
    if (searchAbortController) {
        searchAbortController.abort();
    }

    if (queryText.length < 2) {
        searchLoading.value = false;
        searchError.value = null;
        searchResponse.value = null;
        return;
    }

    searchAbortController = new AbortController();
    searchLoading.value = true;
    searchError.value = null;

    try {
        const endpoint = `${routes.value.portalSearch}?q=${encodeURIComponent(queryText)}`;
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: searchAbortController.signal,
        });

        if (!response.ok) {
            throw new Error(`Search failed (${response.status})`);
        }

        const payload = (await response.json()) as PortalSearchResponse;
        searchResponse.value = payload;
    } catch (error) {
        if ((error as { name?: string })?.name === 'AbortError') {
            return;
        }
        searchError.value = 'Unable to search right now. Try again.';
    } finally {
        searchLoading.value = false;
    }
}

function handleSearchInput(value: string): void {
    localSearch.value = value;
    emit('update:searchQuery', value);

    const clean = value.trim();
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    if (clean.length < 2) {
        searchOpen.value = false;
        searchLoading.value = false;
        searchError.value = null;
        searchResponse.value = null;
        return;
    }

    searchOpen.value = true;
    searchLoading.value = true;
    searchTimer = setTimeout(() => {
        void runGlobalSearch(clean);
    }, 220);
}

function clearSearch(): void {
    handleSearchInput('');
    searchOpen.value = false;
}

function closeSearchPopover(): void {
    searchOpen.value = false;
}

function handleDocumentClick(event: MouseEvent): void {
    const target = event.target as Node | null;
    if (searchWrapperRef.value && target && !searchWrapperRef.value.contains(target)) {
        searchOpen.value = false;
    }
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        searchOpen.value = false;
    }
}

onMounted(() => {
    try {
        collapsed.value = window.localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        collapsed.value = false;
    }

    document.addEventListener('click', handleDocumentClick);
    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }
    if (searchAbortController) {
        searchAbortController.abort();
    }
    document.removeEventListener('click', handleDocumentClick);
    document.removeEventListener('keydown', handleKeydown);
});

function toggleSidebarCollapse(): void {
    collapsed.value = !collapsed.value;
    try {
        window.localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0');
    } catch {
        // Ignore storage write restriction
    }
}

function closeMobileMenu(): void {
    mobileMenuOpen.value = false;
}

function toggleMobileSearch(): void {
    mobileSearchOpen.value = !mobileSearchOpen.value;
    if (!mobileSearchOpen.value) {
        searchOpen.value = false;
    }
}

function markAllNotificationsRead(): void {
    if (busyMarkAll.value || !portalShell.value?.markAllReadUrl) {
        return;
    }

    busyMarkAll.value = true;
    router.post(
        portalShell.value.markAllReadUrl,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                busyMarkAll.value = false;
            },
        },
    );
}

function openNotificationItem(item: PortalNotificationItem): void {
    notifMenuOpen.value = false;

    if (!item.isUnread && item.actionUrl) {
        if (item.actionIsInertia) {
            router.visit(item.actionUrl);
        } else {
            window.location.assign(item.actionUrl);
        }
        return;
    }

    if (busyNotificationId.value === item.id) {
        return;
    }

    busyNotificationId.value = item.id;
    router.post(
        item.markReadUrl,
        item.actionUrl ? { open: 1 } : {},
        {
            preserveScroll: !item.actionUrl,
            onFinish: () => {
                busyNotificationId.value = null;
            },
        },
    );
}
</script>

<template>
    <div class="portal-violet min-h-screen bg-[#F7F6FA] font-sans text-[#292536] lg:flex">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-[#7057D9] focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white focus:outline-none"
        >
            Skip to main content
        </a>

        <aside
            :class="
                cn(
                    'hidden lg:block lg:shrink-0 lg:border-r lg:border-[#E5E1EB] lg:bg-[#F7F6FA] lg:text-[#292536] lg:transition-[width] lg:duration-150',
                    collapsed ? 'lg:w-[74px]' : 'lg:w-[238px]',
                )
            "
            aria-label="Trainee Portal Navigation"
        >
            <div class="sticky top-0 flex h-screen flex-col">
                <div :class="cn('px-4 pt-5 pb-5', collapsed && 'flex justify-center px-2')">
                    <Link
                        :href="routes.portalDashboard"
                        title="Ecohydrotech Trainee Portal"
                        aria-label="Ecohydrotech Trainee Portal"
                        :class="
                            cn(
                                'inline-flex items-center gap-2.5 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                collapsed && 'justify-center',
                            )
                        "
                    >
                        <img
                            :src="app.logoUrl"
                            alt=""
                            width="32"
                            height="32"
                            class="h-8 w-8 shrink-0 rounded-[8px] object-contain"
                            aria-hidden="true"
                        >
                        <span v-if="!collapsed" class="truncate text-[16px] font-extrabold tracking-tight text-[#282338]">
                            Ecohydrotech
                        </span>
                    </Link>
                </div>

                <nav class="flex flex-1 flex-col overflow-y-auto px-3 pb-4" aria-label="Portal Sidebar">
                    <ul class="space-y-1">
                        <li v-for="item in workspaceNav" :key="item.label">
                            <component
                                :is="item.isInertia ? Link : 'a'"
                                :href="item.href"
                                :title="collapsed ? item.label : undefined"
                                :aria-current="item.active ? 'page' : undefined"
                                :class="
                                    cn(
                                        'group flex items-center justify-between gap-2.5 rounded-[8px] px-3 py-2.5 text-[13.5px] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                        item.active
                                            ? 'bg-[#EAE5F8] font-bold text-[#5942B5]'
                                            : 'font-medium text-[#403B4B] hover:bg-[#EAE5F8]/60 hover:text-[#282338]',
                                        collapsed && 'justify-center px-2',
                                    )
                                "
                            >
                                <span :class="cn('flex min-w-0 items-center gap-3', collapsed && 'gap-0')">
                                    <component
                                        :is="item.icon"
                                        :class="
                                            cn(
                                                'h-[17px] w-[17px] shrink-0 transition-colors',
                                                item.active
                                                    ? 'text-[#5942B5]'
                                                    : 'text-[#625D6D] group-hover:text-[#5942B5]',
                                            )
                                        "
                                        aria-hidden="true"
                                    />
                                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                                </span>
                            </component>
                        </li>
                    </ul>

                    <div class="my-4 h-px bg-[#E5E1EB]" aria-hidden="true" />

                    <ul class="space-y-1">
                        <li v-for="item in utilityNav" :key="item.label">
                            <component
                                :is="item.isInertia ? Link : 'a'"
                                :href="item.href"
                                :title="collapsed ? item.label : undefined"
                                :aria-current="item.active ? 'page' : undefined"
                                :class="
                                    cn(
                                        'group flex items-center justify-between gap-2.5 rounded-[8px] px-3 py-2.5 text-[13.5px] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                        item.active
                                            ? 'bg-[#EAE5F8] font-bold text-[#5942B5]'
                                            : 'font-medium text-[#403B4B] hover:bg-[#EAE5F8]/60 hover:text-[#282338]',
                                        collapsed && 'justify-center px-2',
                                    )
                                "
                            >
                                <span :class="cn('flex min-w-0 items-center gap-3', collapsed && 'gap-0')">
                                    <component
                                        :is="item.icon"
                                        :class="
                                            cn(
                                                'h-[17px] w-[17px] shrink-0 transition-colors',
                                                item.active
                                                    ? 'text-[#5942B5]'
                                                    : 'text-[#625D6D] group-hover:text-[#5942B5]',
                                            )
                                        "
                                        aria-hidden="true"
                                    />
                                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                                </span>

                                <span
                                    v-if="!collapsed && item.badge"
                                    class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#7057D9] px-1.5 text-[11px] font-bold text-white tabular-nums"
                                >
                                    {{ item.badge > 99 ? '99+' : item.badge }}
                                </span>
                            </component>
                        </li>
                    </ul>

                    <div class="mt-auto border-t border-[#E5E1EB] pt-3">
                        <button
                            type="button"
                            :disabled="loggingOut"
                            :title="collapsed ? 'Sign Out' : undefined"
                            :class="
                                cn(
                                    'group flex w-full items-center gap-3 rounded-[8px] px-3 py-2 text-left text-[13.5px] font-medium text-[#403B4B] transition-colors duration-150 hover:bg-[#FBECEE] hover:text-[#A33C48] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40 disabled:opacity-50',
                                    collapsed && 'justify-center px-2',
                                )
                            "
                            @click="logout"
                        >
                            <LogOut class="h-[17px] w-[17px] shrink-0 text-[#625D6D] group-hover:text-[#A33C48]" aria-hidden="true" />
                            <span v-if="!collapsed">{{ loggingOut ? 'Signing out...' : 'Sign Out' }}</span>
                        </button>
                    </div>
                </nav>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col bg-[#F7F6FA]">
            <header ref="searchWrapperRef" class="px-4 pt-5 pb-3 sm:px-6 lg:px-8">
                <div class="relative mx-auto max-w-[1440px]">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="lg:hidden">
                                <Sheet v-model:open="mobileMenuOpen">
                                    <SheetTrigger as-child>
                                        <button
                                            type="button"
                                            class="inline-flex h-[39px] w-[39px] items-center justify-center rounded-[9px] border border-[#E5E1EB] bg-white text-[#282338] transition-colors hover:border-[#B9ADE9] hover:bg-[#FAF9FE] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                                            aria-label="Open navigation menu"
                                        >
                                            <Menu class="h-[18px] w-[18px]" aria-hidden="true" />
                                        </button>
                                    </SheetTrigger>
                                    <SheetContent
                                        side="left"
                                        class="portal-violet flex flex-col overflow-y-auto border-[#E5E1EB] bg-[#F7F6FA] p-0 font-sans text-[#292536]"
                                        close-class="border-[#E5E1EB] bg-white text-[#403B4B] hover:bg-[#F1EDFB] hover:text-[#282338] focus-visible:ring-[#7057D9]/40"
                                    >
                                        <SheetHeader class="border-b border-[#E5E1EB] px-5 py-4 text-left">
                                            <Link
                                                :href="routes.portalDashboard"
                                                class="inline-flex items-center gap-2.5 pr-8 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                                                @click="closeMobileMenu"
                                            >
                                                <img
                                                    :src="app.logoUrl"
                                                    alt=""
                                                    width="32"
                                                    height="32"
                                                    class="h-8 w-8 shrink-0 rounded-[8px] object-contain"
                                                    aria-hidden="true"
                                                >
                                                <SheetTitle class="text-[16px] font-extrabold tracking-tight text-[#282338]">
                                                    Ecohydrotech
                                                </SheetTitle>
                                            </Link>
                                            <SheetDescription class="sr-only">
                                                Trainee Portal Navigation
                                            </SheetDescription>
                                        </SheetHeader>

                                        <nav class="flex flex-1 flex-col px-3.5 py-4" aria-label="Mobile Portal Navigation">
                                            <div class="space-y-1">
                                                <component
                                                    :is="item.isInertia ? Link : 'a'"
                                                    v-for="item in workspaceNav"
                                                    :key="item.label"
                                                    :href="item.href"
                                                    :aria-current="item.active ? 'page' : undefined"
                                                    :class="
                                                        cn(
                                                            'group flex items-center gap-3 rounded-[8px] px-3 py-2.5 text-[14px] transition-colors',
                                                            item.active
                                                                ? 'bg-[#EAE5F8] font-bold text-[#5942B5]'
                                                                : 'font-medium text-[#403B4B] hover:bg-[#EAE5F8]/60 hover:text-[#282338]',
                                                        )
                                                    "
                                                    @click="closeMobileMenu"
                                                >
                                                    <component
                                                        :is="item.icon"
                                                        :class="
                                                            cn(
                                                                'h-[17px] w-[17px] shrink-0 transition-colors',
                                                                item.active
                                                                    ? 'text-[#5942B5]'
                                                                    : 'text-[#625D6D] group-hover:text-[#5942B5]',
                                                            )
                                                        "
                                                        aria-hidden="true"
                                                    />
                                                    <span>{{ item.label }}</span>
                                                </component>
                                            </div>

                                            <div class="my-4 h-px bg-[#E5E1EB]" aria-hidden="true" />

                                            <div class="space-y-1">
                                                <component
                                                    :is="item.isInertia ? Link : 'a'"
                                                    v-for="item in utilityNav"
                                                    :key="item.label"
                                                    :href="item.href"
                                                    :aria-current="item.active ? 'page' : undefined"
                                                    :class="
                                                        cn(
                                                            'group flex items-center justify-between gap-3 rounded-[8px] px-3 py-2.5 text-[14px] transition-colors',
                                                            item.active
                                                                ? 'bg-[#EAE5F8] font-bold text-[#5942B5]'
                                                                : 'font-medium text-[#403B4B] hover:bg-[#EAE5F8]/60 hover:text-[#282338]',
                                                        )
                                                    "
                                                    @click="closeMobileMenu"
                                                >
                                                    <span class="flex items-center gap-3">
                                                        <component
                                                            :is="item.icon"
                                                            :class="
                                                                cn(
                                                                    'h-[17px] w-[17px] shrink-0 transition-colors',
                                                                    item.active
                                                                        ? 'text-[#5942B5]'
                                                                        : 'text-[#625D6D] group-hover:text-[#5942B5]',
                                                                )
                                                            "
                                                            aria-hidden="true"
                                                        />
                                                        <span>{{ item.label }}</span>
                                                    </span>
                                                    <span
                                                        v-if="item.badge"
                                                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#7057D9] px-1.5 text-[11px] font-bold text-white tabular-nums"
                                                    >
                                                        {{ item.badge > 99 ? '99+' : item.badge }}
                                                    </span>
                                                </component>
                                            </div>
                                        </nav>

                                        <div class="mt-auto border-t border-[#E5E1EB] p-4">
                                            <button
                                                type="button"
                                                :disabled="loggingOut"
                                                class="flex w-full items-center justify-center gap-2 rounded-[8px] border border-[#E5E1EB] bg-white px-4 py-2.5 text-[13.5px] font-bold text-[#403B4B] transition-colors hover:border-[#ECC4CA] hover:bg-[#FBECEE] hover:text-[#A33C48] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40 disabled:opacity-50"
                                                @click="logout"
                                            >
                                                <LogOut class="h-4 w-4" aria-hidden="true" />
                                                <span>{{ loggingOut ? 'Signing out...' : 'Sign Out' }}</span>
                                            </button>
                                        </div>
                                    </SheetContent>
                                </Sheet>
                            </div>

                            <button
                                type="button"
                                class="hidden h-[36px] w-[36px] shrink-0 items-center justify-center rounded-[9px] border border-[#E5E1EB] bg-white text-[#625D6D] transition-colors hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40 lg:inline-flex"
                                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                                :aria-expanded="!collapsed"
                                @click="toggleSidebarCollapse"
                            >
                                <PanelLeftOpen v-if="collapsed" class="h-[17px] w-[17px]" aria-hidden="true" />
                                <PanelLeftClose v-else class="h-[17px] w-[17px]" aria-hidden="true" />
                            </button>

                            <div v-if="props.title" class="min-w-0 flex-1">
                                <h1 class="text-[18px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[24px]">
                                    {{ props.title }}
                                </h1>
                                <p v-if="props.subtitle" class="mt-0.5 text-[12px] leading-snug text-[#625D6D] sm:text-[13px]">
                                    {{ props.subtitle }}
                                </p>
                            </div>

                            <nav v-else class="flex min-w-0 items-center gap-1.5 text-[14px]" aria-label="Breadcrumb">
                                <Link
                                    :href="routes.portalDashboard"
                                    class="font-semibold text-[#5942B5] transition-colors hover:text-[#7057D9] hover:underline underline-offset-4"
                                >
                                    Portal
                                </Link>
                                <span class="text-[#625D6D]/60" aria-hidden="true">/</span>
                                <span class="truncate font-bold text-[#282338]">{{ breadcrumb }}</span>
                            </nav>
                        </div>

                        <div class="relative flex shrink-0 items-center gap-2.5">
                            <div class="relative hidden sm:block">
                                <Search
                                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[#625D6D]"
                                    aria-hidden="true"
                                />
                                <input
                                    :value="localSearch"
                                    type="text"
                                    role="searchbox"
                                    autocomplete="off"
                                    placeholder="Search activities or courses"
                                    aria-label="Search activities or courses"
                                    class="h-[39px] w-[220px] rounded-[9px] border border-[#E5E1EB] bg-white pr-8 pl-9 text-[13px] text-[#292536] placeholder:text-[#625D6D] transition-colors focus:border-[#7057D9] focus:outline-none focus:ring-3 focus:ring-[#7057D9]/15 md:w-[275px]"
                                    @input="handleSearchInput(($event.target as HTMLInputElement).value)"
                                    @focus="trimmedSearch.length >= 2 && (searchOpen = true)"
                                >
                                <button
                                    v-if="localSearch.length > 0"
                                    type="button"
                                    aria-label="Clear search"
                                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-1 text-[#625D6D] hover:bg-[#F1EDFB] hover:text-[#282338]"
                                    @click="clearSearch"
                                >
                                    <X class="h-3.5 w-3.5" aria-hidden="true" />
                                </button>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-[39px] w-[39px] items-center justify-center rounded-[9px] border border-[#E5E1EB] bg-white text-[#282338] transition-colors hover:border-[#B9ADE9] hover:bg-[#FAF9FE] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40 sm:hidden"
                                aria-label="Toggle search"
                                :aria-expanded="mobileSearchOpen"
                                @click="toggleMobileSearch"
                            >
                                <Search class="h-4 w-4" aria-hidden="true" />
                            </button>

                        <DropdownMenu v-model:open="notifMenuOpen">
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="relative inline-flex h-[39px] w-[39px] items-center justify-center rounded-[9px] border border-[#E5E1EB] bg-white text-[#282338] transition-colors hover:border-[#B9ADE9] hover:bg-[#FAF9FE] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                                    aria-label="Notifications"
                                >
                                    <Bell class="h-[17px] w-[17px]" aria-hidden="true" />
                                    <span
                                        v-if="unreadCountResolved > 0"
                                        class="absolute top-1.5 right-2 h-2 w-2 rounded-full bg-[#B94D66]"
                                    >
                                        <span class="sr-only">{{ unreadCountResolved }} unread notifications</span>
                                    </span>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="portal-violet w-[340px] max-w-[calc(100vw-24px)] p-0 font-sans">
                                <div class="flex items-center justify-between gap-2 border-b border-[#E5E1EB] px-3.5 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[13.5px] font-bold text-[#282338]">Notifications</span>
                                        <span
                                            v-if="unreadCountResolved > 0"
                                            class="inline-flex items-center rounded-full bg-[#EAE5F8] px-2 py-0.5 text-[11px] font-bold text-[#5942B5] tabular-nums"
                                        >
                                            {{ unreadCountResolved }} unread
                                        </span>
                                    </div>
                                    <button
                                        v-if="unreadCountResolved > 0"
                                        type="button"
                                        :disabled="busyMarkAll"
                                        class="text-[12px] font-bold text-[#5942B5] hover:text-[#7057D9] hover:underline disabled:opacity-50"
                                        @click="markAllNotificationsRead"
                                    >
                                        {{ busyMarkAll ? 'Updating...' : 'Mark all read' }}
                                    </button>
                                </div>

                                <div v-if="recentNotifications.length > 0" class="max-h-72 divide-y divide-[#EEEAF2] overflow-y-auto">
                                    <button
                                        v-for="item in recentNotifications"
                                        :key="item.id"
                                        type="button"
                                        :disabled="busyNotificationId === item.id"
                                        class="flex w-full items-start gap-2.5 px-3.5 py-2.5 text-left transition-colors hover:bg-[#FAF9FC] disabled:opacity-60"
                                        @click="openNotificationItem(item)"
                                    >
                                        <span
                                            :class="
                                                cn(
                                                    'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                                    item.isUnread ? 'bg-[#7057D9]' : 'bg-[#E5E1EB]',
                                                )
                                            "
                                            aria-hidden="true"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-[13px] font-bold text-[#292536]">
                                                {{ item.title }}
                                            </p>
                                            <p v-if="item.message" class="mt-0.5 line-clamp-2 text-[12px] leading-snug text-[#403B4B]">
                                                {{ item.message }}
                                            </p>
                                            <p class="mt-1 text-[11px] text-[#625D6D] tabular-nums">
                                                {{ item.createdAtLabel }}
                                            </p>
                                        </div>
                                    </button>
                                </div>

                                <div v-else class="px-4 py-6 text-center">
                                    <p class="text-[13px] font-semibold text-[#292536]">No recent notifications</p>
                                    <p class="mt-0.5 text-[12px] text-[#625D6D]">
                                        Booking updates and certificate alerts appear here.
                                    </p>
                                </div>

                                <div class="border-t border-[#E5E1EB] bg-[#FAF9FC] px-3.5 py-2.5">
                                    <Link
                                        :href="routes.portalNotifications"
                                        class="block text-center text-[12.5px] font-bold text-[#5942B5] hover:text-[#7057D9] hover:underline"
                                        @click="notifMenuOpen = false"
                                    >
                                        View all notifications &rarr;
                                    </Link>
                                </div>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full border border-[#D9D0F2] bg-[#E8E2F8] text-[13px] font-extrabold text-[#5942B5] transition-colors hover:border-[#7057D9] hover:bg-[#EAE5F8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                                    :aria-label="`User profile menu for ${user?.name ?? 'User'}`"
                                >
                                    <img v-if="user?.photoUrl" :src="user.photoUrl" alt="" class="h-full w-full rounded-full object-cover"/>
                                    <span v-else>{{ userInitials }}</span>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="portal-violet w-64 font-sans">
                                <DropdownMenuLabel class="py-2">
                                    <p class="truncate text-[14px] font-bold text-[#282338]">
                                        {{ user?.name }}
                                    </p>
                                    <p class="truncate text-[12px] font-normal text-[#625D6D]">
                                        {{ user?.email }}
                                    </p>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link :href="routes.portalProfile" class="flex items-center gap-2.5">
                                        <UserCheck class="h-4 w-4 text-[#5942B5]" aria-hidden="true" />
                                        <span>Profile &amp; Billing</span>
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link :href="routes.portalSecurity" class="flex items-center gap-2.5">
                                        <ShieldCheck class="h-4 w-4 text-[#5942B5]" aria-hidden="true" />
                                        <span>Password &amp; Security</span>
                                    </Link>
                                </DropdownMenuItem>
                                <template v-if="hasMultiplePortals">
                                    <DropdownMenuSeparator />
                                    <DropdownMenuLabel class="text-[11px] font-bold uppercase tracking-wider text-[#625D6D]">
                                        Switch Portal
                                    </DropdownMenuLabel>
                                    <DropdownMenuItem
                                        v-for="portal in portals"
                                        :key="portal.slug"
                                        as-child
                                    >
                                        <a :href="portal.url" class="flex flex-col items-start gap-0.5">
                                            <span class="text-[13.5px] font-bold text-[#282338]">
                                                {{ portal.label }}
                                            </span>
                                            <span class="text-[12px] text-[#625D6D]">
                                                {{ portal.description }}
                                            </span>
                                        </a>
                                    </DropdownMenuItem>
                                </template>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    :disabled="loggingOut"
                                    class="font-bold text-[#A33C48]"
                                    @select.prevent="logout"
                                >
                                    {{ loggingOut ? 'Signing out...' : 'Sign Out' }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        </div>
                    </div>

                    <div v-if="mobileSearchOpen" class="mt-3 sm:hidden">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[#625D6D]"
                                aria-hidden="true"
                            />
                            <input
                                :value="localSearch"
                                type="text"
                                role="searchbox"
                                autocomplete="off"
                                placeholder="Search activities or courses"
                                aria-label="Search activities or courses"
                                class="h-[40px] w-full rounded-[9px] border border-[#E5E1EB] bg-white pr-8 pl-9 text-[13.5px] text-[#292536] placeholder:text-[#625D6D] focus:border-[#7057D9] focus:outline-none focus:ring-3 focus:ring-[#7057D9]/15"
                                @input="handleSearchInput(($event.target as HTMLInputElement).value)"
                                @focus="trimmedSearch.length >= 2 && (searchOpen = true)"
                            >
                            <button
                                v-if="localSearch.length > 0"
                                type="button"
                                aria-label="Clear search"
                                class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-1 text-[#625D6D] hover:bg-[#F1EDFB] hover:text-[#282338]"
                                @click="clearSearch"
                            >
                                <X class="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="searchOpen && trimmedSearch.length >= 2"
                        role="region"
                        aria-label="Search results"
                        class="absolute inset-x-0 top-full z-40 mt-2 max-h-[70vh] overflow-y-auto rounded-[12px] border border-[#E5E1EB] bg-white p-2 shadow-[0_14px_34px_rgba(40,35,56,0.14)] sm:inset-x-auto sm:right-0 sm:w-[390px]"
                    >
                        <div v-if="searchLoading" class="px-3 py-5 text-center text-[13px] text-[#625D6D]">
                            Searching your records &amp; courses...
                        </div>

                        <div v-else-if="searchError" class="flex items-center justify-between gap-2 rounded-lg bg-[#FBECEE] px-3 py-2.5 text-[12.5px] text-[#A33C48]">
                            <span>{{ searchError }}</span>
                            <button
                                type="button"
                                class="font-bold underline underline-offset-2"
                                @click="runGlobalSearch(trimmedSearch)"
                            >
                                Retry
                            </button>
                        </div>

                        <div v-else-if="searchGroups.length === 0" class="px-3 py-5 text-center">
                            <p class="text-[13px] font-bold text-[#292536]">
                                No matching records for &ldquo;{{ trimmedSearch }}&rdquo;
                            </p>
                            <p class="mt-1 text-[12px] text-[#625D6D]">
                                Try another session name, booking reference, or course code.
                            </p>
                        </div>

                        <div v-else class="space-y-2.5 py-1">
                            <div v-for="group in searchGroups" :key="group.key">
                                <p class="px-2.5 pb-1 text-[11px] font-bold uppercase tracking-wider text-[#625D6D]">
                                    {{ group.label }}
                                </p>
                                <div class="space-y-0.5">
                                    <Link
                                        v-for="item in group.items"
                                        :key="`${group.key}-${item.id}`"
                                        :href="item.url"
                                        class="flex items-start justify-between gap-3 rounded-[8px] px-2.5 py-2 transition-colors hover:bg-[#F1EDFB]/70"
                                        @click="closeSearchPopover"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-[13px] font-bold text-[#292536]">
                                                {{ item.title }}
                                            </p>
                                            <p class="mt-0.5 truncate text-[12px] text-[#625D6D] tabular-nums">
                                                {{ item.subtitle }} &middot; {{ item.meta }}
                                            </p>
                                        </div>
                                        <PortalStatusBadge
                                            v-if="item.statusValue && item.statusLabel"
                                            :status="item.statusValue"
                                            :label="item.statusLabel"
                                            class="shrink-0"
                                        />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="flex-1 px-4 pt-2 pb-10 sm:px-6 lg:px-8">
                <div v-if="flash.status || flash.error" class="mx-auto mb-4 max-w-[1440px] space-y-2">
                    <p
                        v-if="flash.status"
                        role="status"
                        class="rounded-[10px] border border-[#B8DFC9] bg-[#E8F4ED] px-4 py-3 text-[13.5px] font-semibold text-[#28734F]"
                    >
                        {{ flash.status }}
                    </p>
                    <p
                        v-if="flash.error"
                        role="alert"
                        class="rounded-[10px] border border-[#ECC4CA] bg-[#FBECEE] px-4 py-3 text-[13.5px] font-semibold text-[#A33C48]"
                    >
                        {{ flash.error }}
                    </p>
                </div>

                <slot />
            </main>

            <footer class="px-4 pb-6 text-center text-[12px] text-[#625D6D] sm:px-6 lg:px-8">
                {{ app.brandName }} &middot; Trainee Portal &middot; Times shown in MYT
            </footer>
        </div>
    </div>
</template>
