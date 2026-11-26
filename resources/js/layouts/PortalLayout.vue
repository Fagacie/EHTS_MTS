<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuPortal,
    DropdownMenuRoot,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from 'reka-ui';
import {
    AlertCircle,
    ArrowUpRight,
    Award,
    Bell,
    BookOpen,
    CalendarCheck,
    CheckCircle2,
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
    Video,
    X,
} from 'lucide-vue-next';
import UserAvatar from '@/components/ds/UserAvatar.vue';
import { useAuth } from '@/composables/useAuth';
import { useRoutes } from '@/composables/useRoutes';
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

const STORAGE_KEY = 'etms_learner_sidebar_collapsed';

const collapsed = ref(false);
const mobileMenuOpen = ref(false);
const notifMenuOpen = ref(false);
const busyNotificationId = ref<string | null>(null);
const busyMarkAll = ref(false);

const localSearch = ref(props.searchQuery);
const searchOpen = ref(false);
const searchLoading = ref(false);
const searchResponse = ref<PortalSearchResponse | null>(null);
const searchWrapperRef = ref<HTMLElement | null>(null);

let searchTimer: ReturnType<typeof setTimeout> | null = null;
let searchAbortController: AbortController | null = null;

const page = usePage<EtmsSharedProps>();
const app = computed(() => page.props.app);
const flash = computed(() => page.props.flash);
const portalShell = computed(() => page.props.portal ?? null);
const { user, portals, hasMultiplePortals, logout } = useAuth();
const { routes, currentPath, isActive } = useRoutes();

const unreadCountResolved = computed(
    () => props.unreadCount ?? user.value?.unreadNotificationsCount ?? 0,
);

const recentNotifications = computed(
    () => portalShell.value?.recentNotifications ?? [],
);

const trimmedSearch = computed(() => localSearch.value.trim());

const searchGroups = computed<SearchCategoryGroup[]>(() => {
    const res = searchResponse.value?.results;
    if (!res) return [];

    return (
        [
            { key: 'bookings', label: 'Bookings', items: res.bookings },
            { key: 'modules', label: 'My Modules', items: res.modules },
            { key: 'certificates', label: 'Certificates', items: res.certificates },
            { key: 'orders', label: 'Orders', items: res.orders },
            { key: 'catalogue', label: 'Catalogue', items: res.catalogue },
        ] as SearchCategoryGroup[]
    ).filter((g) => g.items.length > 0);
});

const navItems = computed<NavEntry[]>(() => [
    {
        label: 'Dashboard',
        href: routes.value.portalDashboard,
        icon: LayoutDashboard,
        active: currentPath.value === '/portal',
    },
    {
        label: 'Live Sessions',
        href: routes.value.portalBookings,
        icon: CalendarCheck,
        active: isActive('/portal/bookings'),
    },
    {
        label: 'My Modules',
        href: routes.value.portalModules,
        icon: BookOpen,
        active: isActive('/portal/modules'),
    },
    {
        label: 'Recordings',
        href: routes.value.portalRecordings,
        icon: Video,
        active: isActive('/portal/recordings'),
    },
    {
        label: 'Certificates',
        href: routes.value.portalCertificates,
        icon: Award,
        active: isActive('/portal/certificates'),
    },
    {
        label: 'Payments',
        href: routes.value.portalOrders,
        icon: CreditCard,
        active: isActive('/portal/orders'),
    },
    {
        label: 'Notifications',
        href: routes.value.portalNotifications,
        icon: Bell,
        active: isActive('/portal/notifications'),
        badge: unreadCountResolved.value > 0 ? unreadCountResolved.value : undefined,
    },
    {
        label: 'Profile',
        href: routes.value.portalProfile,
        icon: Settings,
        active: isActive('/portal/profile'),
    },
    {
        label: 'Security',
        href: routes.value.portalSecurity,
        icon: ShieldCheck,
        active: isActive('/portal/security'),
    },
]);

function toggleSidebar(): void {
    collapsed.value = !collapsed.value;
    try {
        localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0');
    } catch {
        // Ignore storage restrictions
    }
}

function isFilamentUrl(url: string): boolean {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.pathname === '/admin' || parsed.pathname.startsWith('/admin/');
    } catch {
        return url === '/admin' || url.startsWith('/admin/');
    }
}

watch(
    () => props.searchQuery,
    (next) => {
        if (next !== localSearch.value) {
            localSearch.value = next;
        }
    },
);

function handleSearchInput(event: Event): void {
    const val = (event.target as HTMLInputElement).value;
    localSearch.value = val;
    emit('update:searchQuery', val);

    if (searchTimer) clearTimeout(searchTimer);
    if (val.trim().length < 2) {
        searchOpen.value = false;
        searchResponse.value = null;
        return;
    }

    searchOpen.value = true;
    searchTimer = setTimeout(() => {
        void runPortalSearch(val.trim());
    }, 220);
}

async function runPortalSearch(query: string): Promise<void> {
    if (searchAbortController) searchAbortController.abort();
    searchAbortController = new AbortController();
    searchLoading.value = true;

    try {
        const url = new URL(routes.value.portalSearch, window.location.origin);
        url.searchParams.set('q', query);

        const res = await fetch(url.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: searchAbortController.signal,
        });

        if (res.ok) {
            searchResponse.value = (await res.json()) as PortalSearchResponse;
        }
    } catch {
        // Ignore aborted or failed search requests
    } finally {
        searchLoading.value = false;
    }
}

function clearSearch(): void {
    localSearch.value = '';
    emit('update:searchQuery', '');
    searchOpen.value = false;
    searchResponse.value = null;
}

function markNotificationRead(item: PortalNotificationItem): void {
    if (!item.isUnread || busyNotificationId.value) return;
    busyNotificationId.value = item.id;

    router.post(
        item.markReadUrl,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                busyNotificationId.value = null;
            },
        },
    );
}

function markAllNotificationsRead(): void {
    if (!portalShell.value?.markAllReadUrl || busyMarkAll.value) return;
    busyMarkAll.value = true;

    router.post(
        portalShell.value.markAllReadUrl,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                busyMarkAll.value = false;
            },
        },
    );
}

function handleOutsideClick(event: MouseEvent): void {
    if (searchWrapperRef.value && !searchWrapperRef.value.contains(event.target as Node)) {
        searchOpen.value = false;
    }
}

onMounted(() => {
    try {
        collapsed.value = localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        collapsed.value = false;
    }
    document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
    if (searchTimer) clearTimeout(searchTimer);
    if (searchAbortController) searchAbortController.abort();
});
</script>

<template>
    <div class="flex min-h-screen bg-sand-50 text-ink">
        <!-- Seamless Collapsible Light Sidebar (Desktop) -->
        <aside
            class="sticky top-0 hidden h-screen shrink-0 flex-col border-r border-neutral-200/80 bg-sand-50 transition-[width] duration-200 lg:flex"
            :class="collapsed ? 'w-[74px]' : 'w-60'"
        >
            <!-- Brand + Collapse Toggle Door -->
            <div
                class="flex h-16 shrink-0 items-center border-b border-neutral-200/70 px-3.5"
                :class="collapsed ? 'justify-center' : 'justify-between gap-2'"
            >
                <Link
                    v-if="!collapsed"
                    :href="routes.portalDashboard"
                    class="flex min-w-0 items-center rounded-xl py-1 focus-visible:outline-2 focus-visible:outline-primary-600"
                >
                    <img
                        :src="app.logoUrl"
                        :alt="app.brandName"
                        class="h-9 w-auto object-contain"
                    />
                    <span class="sr-only">{{ app.brandName }}</span>
                </Link>

                <button
                    type="button"
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    class="inline-flex size-8.5 shrink-0 cursor-pointer items-center justify-center rounded-xl text-muted transition-colors hover:bg-sand-100 hover:text-navy-950 focus-visible:outline-2 focus-visible:outline-primary-600"
                    @click="toggleSidebar"
                >
                    <PanelLeftOpen v-if="collapsed" class="size-4.5" aria-hidden="true" />
                    <PanelLeftClose v-else class="size-4.5" aria-hidden="true" />
                </button>
            </div>

            <!-- Compact Navigation (all primary items visible without scrolling) -->
            <nav aria-label="Learner navigation" class="flex-1 space-y-0.5 px-2.5 py-3">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    :title="collapsed ? item.label : undefined"
                    :aria-current="item.active ? 'page' : undefined"
                    class="group relative flex items-center rounded-xl py-2 text-[13.5px] transition-all duration-150 focus-visible:outline-2 focus-visible:outline-primary-600"
                    :class="[
                        collapsed ? 'justify-center px-0' : 'gap-3 px-3',
                        item.active
                            ? 'bg-white font-semibold text-navy-950 shadow-card ring-1 ring-neutral-200/75'
                            : 'font-medium text-muted hover:bg-sand-100/80 hover:text-navy-950',
                    ]"
                >
                    <component
                        :is="item.icon"
                        class="size-4 shrink-0 transition-colors"
                        :class="item.active ? 'text-primary-600' : 'text-muted group-hover:text-navy-900'"
                        aria-hidden="true"
                    />
                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                    <span
                        v-if="item.badge && !collapsed"
                        class="tabular ml-auto rounded-full bg-coral-500 px-2 py-0.5 text-[11px] font-bold text-white"
                    >
                        {{ item.badge > 99 ? '99+' : item.badge }}
                    </span>
                    <span
                        v-else-if="item.badge && collapsed"
                        class="absolute top-1.5 right-2 size-2 rounded-full bg-coral-500"
                        aria-hidden="true"
                    />
                </Link>
            </nav>

            <!-- Sidebar User Footer -->
            <div class="shrink-0 border-t border-neutral-200/70 p-2.5">
                <DropdownMenuRoot>
                    <DropdownMenuTrigger
                        class="flex w-full cursor-pointer items-center rounded-xl p-2 text-left transition-colors hover:bg-sand-100 focus-visible:outline-2 focus-visible:outline-primary-600"
                        :class="collapsed ? 'justify-center' : 'gap-2.5'"
                    >
                        <UserAvatar
                            :src="user?.photoUrl"
                            :name="user?.name"
                            size="sm"
                        />
                        <div v-if="!collapsed" class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-navy-950">
                                {{ user?.name ?? 'Learner' }}
                            </p>
                            <p class="truncate text-[11px] text-muted">
                                {{ user?.email }}
                            </p>
                        </div>
                    </DropdownMenuTrigger>

                    <DropdownMenuPortal>
                        <DropdownMenuContent
                            side="right"
                            align="end"
                            :side-offset="10"
                            class="z-50 w-60 rounded-2xl border border-line bg-white p-1.5 shadow-popover"
                        >
                            <div class="px-3 py-2">
                                <p class="truncate text-sm font-semibold text-navy-950">{{ user?.name }}</p>
                                <p class="truncate text-xs text-muted">{{ user?.email }}</p>
                            </div>

                            <template v-if="hasMultiplePortals">
                                <DropdownMenuSeparator class="my-1 h-px bg-line" />
                                <template v-for="portal in portals" :key="portal.slug">
                                    <DropdownMenuItem as-child>
                                        <a
                                            v-if="isFilamentUrl(portal.url)"
                                            :href="portal.url"
                                            class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-xs font-semibold text-navy-950 hover:bg-sand-50 focus:bg-sand-50 focus:outline-none"
                                        >
                                            <span>{{ portal.label }}</span>
                                            <ArrowUpRight class="size-3.5 text-muted" aria-hidden="true" />
                                        </a>
                                        <Link
                                            v-else
                                            :href="portal.url"
                                            class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-xs font-semibold text-navy-950 hover:bg-sand-50 focus:bg-sand-50 focus:outline-none"
                                        >
                                            <span>{{ portal.label }}</span>
                                            <ArrowUpRight class="size-3.5 text-muted" aria-hidden="true" />
                                        </Link>
                                    </DropdownMenuItem>
                                </template>
                            </template>

                            <DropdownMenuSeparator class="my-1 h-px bg-line" />

                            <DropdownMenuItem
                                class="flex w-full cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-danger-700 hover:bg-danger-50 focus:bg-danger-50 focus:outline-none"
                                @select="logout"
                            >
                                <LogOut class="size-3.5" aria-hidden="true" />
                                <span>Sign out</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenuPortal>
                </DropdownMenuRoot>
            </div>
        </aside>

        <!-- Main Column -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Sticky Top Utility Bar -->
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-neutral-200/70 bg-sand-50/92 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <!-- Mobile Drawer Trigger -->
                    <DialogRoot v-model:open="mobileMenuOpen">
                        <DialogTrigger as-child>
                            <button
                                type="button"
                                aria-label="Open learner menu"
                                class="inline-flex size-9 items-center justify-center rounded-xl border border-line bg-white text-navy-950 lg:hidden"
                            >
                                <Menu class="size-4.5" aria-hidden="true" />
                            </button>
                        </DialogTrigger>

                        <DialogPortal>
                            <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/40 backdrop-blur-[2px]" />
                            <DialogContent
                                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-sand-50 p-4 shadow-floating focus:outline-none"
                            >
                                <div class="flex items-center justify-between border-b border-line pb-3.5">
                                    <DialogTitle class="flex items-center">
                                        <img
                                            :src="app.logoUrl"
                                            :alt="app.brandName"
                                            class="h-8 w-auto object-contain"
                                        />
                                        <span class="sr-only">{{ app.brandName }}</span>
                                    </DialogTitle>
                                    <DialogClose
                                        aria-label="Close learner menu"
                                        class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-sand-100 hover:text-navy-950"
                                    >
                                        <X class="size-4.5" aria-hidden="true" />
                                    </DialogClose>
                                </div>

                                <nav aria-label="Mobile learner navigation" class="mt-3 flex-1 space-y-0.5 overflow-y-auto">
                                    <Link
                                        v-for="item in navItems"
                                        :key="item.href"
                                        :href="item.href"
                                        class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition-colors"
                                        :class="
                                            item.active
                                                ? 'bg-white font-semibold text-navy-950 shadow-card ring-1 ring-neutral-200/75'
                                                : 'font-medium text-muted hover:bg-sand-100 hover:text-navy-950'
                                        "
                                        @click="mobileMenuOpen = false"
                                    >
                                        <component
                                            :is="item.icon"
                                            class="size-4 shrink-0"
                                            :class="item.active ? 'text-primary-600' : 'text-muted'"
                                            aria-hidden="true"
                                        />
                                        <span>{{ item.label }}</span>
                                        <span
                                            v-if="item.badge"
                                            class="tabular ml-auto rounded-full bg-coral-500 px-2 py-0.5 text-[11px] font-bold text-white"
                                        >
                                            {{ item.badge }}
                                        </span>
                                    </Link>
                                </nav>

                                <div class="mt-3 border-t border-line pt-3">
                                    <div class="mb-2.5 flex items-center gap-2.5 px-1">
                                        <UserAvatar :src="user?.photoUrl" :name="user?.name" size="sm" />
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-semibold text-navy-950">{{ user?.name }}</p>
                                            <p class="truncate text-[11px] text-muted">{{ user?.email }}</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-line bg-white py-2 text-xs font-semibold text-danger-700 hover:bg-danger-50"
                                        @click="logout"
                                    >
                                        <LogOut class="size-3.5" aria-hidden="true" />
                                        <span>Sign out</span>
                                    </button>
                                </div>
                            </DialogContent>
                        </DialogPortal>
                    </DialogRoot>

                    <span class="truncate text-sm font-semibold text-navy-950">
                        {{ breadcrumb }}
                    </span>
                </div>

                <!-- Right Utility Controls: Search, Notifications, Public Site Link -->
                <div class="flex items-center gap-2.5">
                    <!-- Quick Portal Search -->
                    <div ref="searchWrapperRef" class="relative hidden sm:block">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            :value="localSearch"
                            type="text"
                            placeholder="Search portal..."
                            aria-label="Search portal"
                            class="h-9 w-52 rounded-full border border-line bg-white pr-8 pl-8.5 text-xs text-ink placeholder:text-muted/75 transition-all focus:w-64 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            @input="handleSearchInput"
                            @focus="trimmedSearch.length >= 2 && (searchOpen = true)"
                        />
                        <button
                            v-if="localSearch"
                            type="button"
                            aria-label="Clear search"
                            class="absolute top-1/2 right-2.5 -translate-y-1/2 cursor-pointer text-muted hover:text-navy-950"
                            @click="clearSearch"
                        >
                            <X class="size-3.5" aria-hidden="true" />
                        </button>

                        <!-- Search Popover -->
                        <div
                            v-if="searchOpen"
                            class="absolute right-0 mt-2 w-80 overflow-hidden rounded-2xl border border-line bg-white shadow-popover"
                        >
                            <div v-if="searchLoading" class="px-4 py-6 text-center text-xs text-muted">
                                Searching...
                            </div>
                            <div v-else-if="searchGroups.length > 0" class="max-h-80 overflow-y-auto p-2">
                                <div v-for="group in searchGroups" :key="group.key" class="mb-2 last:mb-0">
                                    <p class="px-2.5 py-1 text-[10px] font-semibold tracking-wider text-muted uppercase">
                                        {{ group.label }}
                                    </p>
                                    <Link
                                        v-for="item in group.items"
                                        :key="`${group.key}-${item.id}`"
                                        :href="item.url"
                                        class="block rounded-xl px-2.5 py-2 transition-colors hover:bg-sand-50"
                                        @click="searchOpen = false"
                                    >
                                        <p class="truncate text-xs font-semibold text-navy-950">{{ item.title }}</p>
                                        <p class="truncate text-[11px] text-muted">{{ item.subtitle }}</p>
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="px-4 py-6 text-center text-xs text-muted">
                                No matches for "{{ trimmedSearch }}"
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Bell Dropdown -->
                    <DropdownMenuRoot v-model:open="notifMenuOpen">
                        <DropdownMenuTrigger
                            aria-label="Notifications"
                            class="relative inline-flex size-9 cursor-pointer items-center justify-center rounded-full border border-line bg-white text-navy-900 transition-colors hover:bg-sand-100 focus-visible:outline-2 focus-visible:outline-primary-600"
                        >
                            <Bell class="size-4" aria-hidden="true" />
                            <span
                                v-if="unreadCountResolved > 0"
                                class="absolute top-1 right-1 size-2 rounded-full bg-coral-500 ring-2 ring-white"
                                aria-hidden="true"
                            />
                        </DropdownMenuTrigger>

                        <DropdownMenuPortal>
                            <DropdownMenuContent
                                align="end"
                                :side-offset="8"
                                class="z-50 w-80 rounded-2xl border border-line bg-white p-2 shadow-popover"
                            >
                                <div class="flex items-center justify-between px-2.5 py-1.5">
                                    <span class="text-xs font-bold text-navy-950">Notifications</span>
                                    <button
                                        v-if="unreadCountResolved > 0"
                                        type="button"
                                        class="cursor-pointer text-[11px] font-semibold text-primary-600 hover:text-primary-700"
                                        @click="markAllNotificationsRead"
                                    >
                                        Mark all read
                                    </button>
                                </div>

                                <DropdownMenuSeparator class="my-1 h-px bg-line" />

                                <div v-if="recentNotifications.length > 0" class="max-h-72 space-y-1 overflow-y-auto">
                                    <button
                                        v-for="item in recentNotifications"
                                        :key="item.id"
                                        type="button"
                                        class="w-full cursor-pointer rounded-xl px-2.5 py-2 text-left transition-colors hover:bg-sand-50"
                                        :class="item.isUnread ? 'bg-primary-50/40' : ''"
                                        @click="markNotificationRead(item)"
                                    >
                                        <p class="text-xs font-semibold text-navy-950">{{ item.title }}</p>
                                        <p class="mt-0.5 line-clamp-2 text-[11px] text-muted">{{ item.message }}</p>
                                    </button>
                                </div>
                                <p v-else class="px-3 py-6 text-center text-xs text-muted">
                                    You're all caught up.
                                </p>

                                <DropdownMenuSeparator class="my-1 h-px bg-line" />

                                <Link
                                    :href="routes.portalNotifications"
                                    class="block rounded-xl py-1.5 text-center text-xs font-semibold text-primary-600 hover:bg-sand-50"
                                    @click="notifMenuOpen = false"
                                >
                                    View all notifications
                                </Link>
                            </DropdownMenuContent>
                        </DropdownMenuPortal>
                    </DropdownMenuRoot>

                    <!-- Explore Website Pill -->
                    <Link
                        :href="routes.home"
                        class="hidden items-center gap-1.5 rounded-full border border-line bg-white px-3.5 py-1.5 text-xs font-semibold text-navy-950 transition-colors hover:bg-sand-100 sm:inline-flex"
                    >
                        <Compass class="size-3.5 text-accent-600" aria-hidden="true" />
                        <span>Browse Courses</span>
                    </Link>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <!-- Flash Banners (including instant registration welcome) -->
                <div v-if="flash.status || flash.error" class="mx-auto mb-5 max-w-[1440px] space-y-2">
                    <div
                        v-if="flash.status"
                        role="status"
                        class="flex items-center gap-2.5 rounded-2xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700 ring-1 ring-inset ring-success-600/20"
                    >
                        <CheckCircle2 class="size-4 shrink-0" aria-hidden="true" />
                        <span>{{ flash.status }}</span>
                    </div>
                    <div
                        v-if="flash.error"
                        role="alert"
                        class="flex items-center gap-2.5 rounded-2xl bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700 ring-1 ring-inset ring-danger-600/20"
                    >
                        <AlertCircle class="size-4 shrink-0" aria-hidden="true" />
                        <span>{{ flash.error }}</span>
                    </div>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
