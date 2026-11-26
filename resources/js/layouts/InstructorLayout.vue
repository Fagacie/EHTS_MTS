<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Bell,
    BookOpen,
    CalendarCheck,
    CheckCircle2,
    Compass,
    LayoutDashboard,
    LogOut,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    UserCheck,
    X,
} from 'lucide-vue-next';
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
import UserAvatar from '@/components/ds/UserAvatar.vue';
import { useAuth } from '@/composables/useAuth';
import { useRoutes } from '@/composables/useRoutes';
import type { EtmsSharedProps, PortalNotificationItem } from '@/types/etms';

interface NavEntry {
    label: string;
    href: string;
    icon: typeof LayoutDashboard;
    active: boolean;
    isInertia: boolean;
    badge?: number;
}

const props = withDefaults(
    defineProps<{
        breadcrumb?: string;
        title?: string;
        subtitle?: string;
        unreadCount?: number;
    }>(),
    {
        breadcrumb: 'Dashboard',
    },
);

const STORAGE_KEY = 'etms_instructor_sidebar_collapsed';

const page = usePage<EtmsSharedProps>();
const flash = computed(() => page.props.flash);
const instructorShell = computed(() => page.props.instructorShell);
const shortName = computed(() => page.props.app?.shortName || 'ETMS');

const { user, portals, hasMultiplePortals, loggingOut, logout } = useAuth();
const { routes, isActive } = useRoutes();

const collapsed = ref(false);
const mobileOpen = ref(false);
const notifMenuOpen = ref(false);
const busyMarkAll = ref(false);
const busyNotificationId = ref<string | null>(null);

const unreadCountResolved = computed(() => {
    if (typeof props.unreadCount === 'number') {
        return props.unreadCount;
    }
    return user.value?.unreadNotificationsCount ?? 0;
});

const recentNotifications = computed<PortalNotificationItem[]>(() => {
    return instructorShell.value?.recentNotifications ?? [];
});

const instructorNav = computed<NavEntry[]>(() => [
    {
        label: 'Dashboard',
        href: routes.value.instructorDashboard,
        icon: LayoutDashboard,
        active:
            isActive('/instructor') &&
            !isActive('/instructor/sessions') &&
            !isActive('/instructor/profile') &&
            !isActive('/instructor/notifications'),
        isInertia: true,
    },
    {
        label: 'Assigned Sessions',
        href: routes.value.instructorSessions,
        icon: CalendarCheck,
        active: isActive('/instructor/sessions'),
        isInertia: true,
    },
    {
        label: 'My Profile',
        href: routes.value.instructorProfile,
        icon: UserCheck,
        active: isActive('/instructor/profile'),
        isInertia: true,
    },
    {
        label: 'Notifications',
        href: routes.value.instructorNotifications,
        icon: Bell,
        active: isActive('/instructor/notifications'),
        isInertia: true,
        badge: unreadCountResolved.value > 0 ? unreadCountResolved.value : undefined,
    },
]);

const utilityNav = computed<NavEntry[]>(() => {
    const items: NavEntry[] = [
        {
            label: 'Public Directory',
            href: routes.value.instructors,
            icon: Compass,
            active: false,
            isInertia: true,
        },
    ];

    if (user.value?.isCustomer) {
        items.push({
            label: 'Trainee Portal',
            href: routes.value.portalDashboard,
            icon: BookOpen,
            active: false,
            isInertia: true,
        });
    }

    return items;
});

onMounted(() => {
    try {
        collapsed.value = window.localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        collapsed.value = false;
    }
});

function toggleSidebarCollapse(): void {
    collapsed.value = !collapsed.value;
    try {
        window.localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0');
    } catch {
        // Ignore storage restriction.
    }
}

function markAllNotificationsRead(): void {
    if (busyMarkAll.value || !instructorShell.value?.markAllReadUrl) {
        return;
    }

    busyMarkAll.value = true;
    router.post(
        instructorShell.value.markAllReadUrl,
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
    <div class="min-h-screen bg-[#f7f6f2] text-ink">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-navy-950 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:outline-none"
        >
            Skip to main content
        </a>

        <!-- Desktop Collapsible Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-line/80 bg-[#f7f6f2] transition-[width] duration-150 lg:flex"
            :class="collapsed ? 'w-[74px]' : 'w-64'"
            aria-label="Instructor Portal Navigation"
        >
            <!-- Brand Header -->
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-line/70 px-4">
                <Link
                    v-if="!collapsed"
                    :href="routes.instructorDashboard"
                    class="flex min-w-0 items-center gap-2 focus-visible:outline-none"
                >
                    <img
                        src="/images/ecohydrotech-logo.png"
                        alt="Ecohydrotech"
                        class="h-8 w-auto shrink-0 object-contain"
                    />
                    <span class="h-4 w-px shrink-0 bg-line-strong/80" aria-hidden="true" />
                    <span class="truncate text-xs font-bold tracking-wider text-navy-950 uppercase">
                        {{ shortName }}
                    </span>
                </Link>

                <button
                    type="button"
                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                    :class="collapsed ? 'mx-auto' : ''"
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    @click="toggleSidebarCollapse"
                >
                    <PanelLeftOpen v-if="collapsed" class="size-4" aria-hidden="true" />
                    <PanelLeftClose v-else class="size-4" aria-hidden="true" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4" aria-label="Instructor Sidebar">
                <div class="space-y-0.5">
                    <component
                        :is="item.isInertia ? Link : 'a'"
                        v-for="item in instructorNav"
                        :key="item.label"
                        :href="item.href"
                        :title="collapsed ? item.label : undefined"
                        :aria-current="item.active ? 'page' : undefined"
                        class="flex h-9 items-center justify-between gap-2.5 rounded-lg px-3 text-[13px] font-medium transition-colors duration-150"
                        :class="[
                            item.active
                                ? 'bg-navy-950 font-semibold text-white shadow-xs'
                                : 'text-muted hover:bg-sand-100/80 hover:text-navy-950',
                            collapsed ? 'justify-center px-0' : '',
                        ]"
                    >
                        <span class="flex min-w-0 items-center gap-2.5">
                            <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                            <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                        </span>
                        <span
                            v-if="!collapsed && item.badge"
                            class="tabular inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                            :class="item.active ? 'bg-white/20 text-white' : 'bg-navy-950 text-white'"
                        >
                            {{ item.badge }}
                        </span>
                    </component>
                </div>

                <div class="border-t border-line/70 pt-3">
                    <p
                        v-if="!collapsed"
                        class="mb-1.5 px-3 text-[11px] font-semibold text-muted/80"
                    >
                        Links
                    </p>
                    <div class="space-y-0.5">
                        <component
                            :is="item.isInertia ? Link : 'a'"
                            v-for="item in utilityNav"
                            :key="item.label"
                            :href="item.href"
                            :title="collapsed ? item.label : undefined"
                            class="flex h-9 items-center gap-2.5 rounded-lg px-3 text-[13px] font-medium text-muted transition-colors hover:bg-sand-100/80 hover:text-navy-950"
                            :class="collapsed ? 'justify-center px-0' : ''"
                        >
                            <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                            <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                        </component>
                    </div>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="shrink-0 border-t border-line/70 p-3">
                <div
                    v-if="!collapsed"
                    class="mb-2 flex items-center gap-2.5 rounded-lg bg-white/70 px-2.5 py-2"
                >
                    <UserAvatar
                        :name="user?.name ?? 'Instructor'"
                        :src="user?.photoUrl"
                        size="sm"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-navy-950">
                            {{ user?.name ?? 'Instructor' }}
                        </p>
                        <p class="truncate text-[11px] text-muted">Instructor</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="flex h-9 w-full items-center gap-2.5 rounded-lg px-3 text-[13px] font-medium text-muted transition-colors hover:bg-sand-100 hover:text-navy-950 disabled:opacity-50"
                    :class="collapsed ? 'justify-center px-0' : ''"
                    :title="collapsed ? 'Sign out' : undefined"
                    :disabled="loggingOut"
                    @click="logout"
                >
                    <LogOut class="size-4 shrink-0" aria-hidden="true" />
                    <span v-if="!collapsed">{{ loggingOut ? 'Signing out...' : 'Sign out' }}</span>
                </button>
            </div>
        </aside>

        <!-- Main Column -->
        <div
            class="min-h-screen transition-[padding] duration-150"
            :class="collapsed ? 'lg:pl-[74px]' : 'lg:pl-64'"
        >
            <!-- Sticky Topbar -->
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-line/70 bg-[#f7f6f2]/90 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <!-- Mobile Drawer -->
                    <DialogRoot v-model:open="mobileOpen">
                        <DialogTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex size-9 items-center justify-center rounded-lg text-muted transition-colors hover:bg-white hover:text-navy-950 lg:hidden"
                                aria-label="Open menu"
                            >
                                <Menu class="size-5" aria-hidden="true" />
                            </button>
                        </DialogTrigger>
                        <DialogPortal>
                            <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/50 backdrop-blur-[1px] lg:hidden" />
                            <DialogContent
                                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-[#f7f6f2] shadow-2xl focus:outline-none lg:hidden"
                            >
                                <div class="flex h-16 items-center justify-between border-b border-line px-4">
                                    <DialogTitle class="flex items-center gap-2">
                                        <img
                                            src="/images/ecohydrotech-logo.png"
                                            alt="Ecohydrotech"
                                            class="h-7 w-auto object-contain"
                                        />
                                        <span class="h-4 w-px bg-line-strong/80" aria-hidden="true" />
                                        <span class="text-xs font-bold tracking-wider text-navy-950 uppercase">
                                            {{ shortName }}
                                        </span>
                                    </DialogTitle>
                                    <DialogClose
                                        class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-sand-100 hover:text-navy-950"
                                        aria-label="Close menu"
                                    >
                                        <X class="size-4" aria-hidden="true" />
                                    </DialogClose>
                                </div>

                                <nav class="flex-1 space-y-4 overflow-y-auto p-3" aria-label="Mobile instructor navigation">
                                    <div class="space-y-0.5">
                                        <component
                                            :is="item.isInertia ? Link : 'a'"
                                            v-for="item in instructorNav"
                                            :key="item.label"
                                            :href="item.href"
                                            class="flex h-9 items-center justify-between rounded-lg px-3 text-[13px] font-medium transition-colors"
                                            :class="
                                                item.active
                                                    ? 'bg-navy-950 font-semibold text-white'
                                                    : 'text-muted hover:bg-sand-100 hover:text-navy-950'
                                            "
                                            @click="mobileOpen = false"
                                        >
                                            <span class="flex items-center gap-2.5">
                                                <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                                                <span>{{ item.label }}</span>
                                            </span>
                                            <span
                                                v-if="item.badge"
                                                class="tabular rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] font-bold"
                                            >
                                                {{ item.badge }}
                                            </span>
                                        </component>
                                    </div>

                                    <div class="border-t border-line pt-3">
                                        <div class="space-y-0.5">
                                            <component
                                                :is="item.isInertia ? Link : 'a'"
                                                v-for="item in utilityNav"
                                                :key="item.label"
                                                :href="item.href"
                                                class="flex h-9 items-center gap-2.5 rounded-lg px-3 text-[13px] font-medium text-muted hover:bg-sand-100 hover:text-navy-950"
                                                @click="mobileOpen = false"
                                            >
                                                <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                                                <span>{{ item.label }}</span>
                                            </component>
                                        </div>
                                    </div>
                                </nav>

                                <div class="border-t border-line p-3">
                                    <button
                                        type="button"
                                        class="flex h-9 w-full items-center gap-2.5 rounded-lg px-3 text-[13px] font-medium text-muted hover:bg-sand-100 hover:text-navy-950"
                                        :disabled="loggingOut"
                                        @click="logout"
                                    >
                                        <LogOut class="size-4" aria-hidden="true" />
                                        <span>Sign out</span>
                                    </button>
                                </div>
                            </DialogContent>
                        </DialogPortal>
                    </DialogRoot>

                    <!-- Breadcrumb -->
                    <nav class="hidden items-center gap-2 text-sm sm:flex" aria-label="Breadcrumb">
                        <Link
                            :href="routes.instructorDashboard"
                            class="font-medium text-muted transition-colors hover:text-navy-950"
                        >
                            Instructor
                        </Link>
                        <span class="text-line-strong">/</span>
                        <span class="truncate font-semibold text-navy-950">{{ breadcrumb }}</span>
                    </nav>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2.5">
                    <!-- Notifications Dropdown -->
                    <DropdownMenuRoot v-model:open="notifMenuOpen">
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="relative inline-flex size-9 items-center justify-center rounded-lg bg-white text-muted ring-1 ring-line transition-colors hover:bg-sand-50 hover:text-navy-950"
                                aria-label="Notifications"
                            >
                                <Bell class="size-4" aria-hidden="true" />
                                <span
                                    v-if="unreadCountResolved > 0"
                                    class="tabular absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-navy-950 px-1 text-[9.5px] font-bold text-white ring-2 ring-[#f7f6f2]"
                                >
                                    {{ unreadCountResolved > 9 ? '9+' : unreadCountResolved }}
                                </span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuPortal>
                            <DropdownMenuContent
                                align="end"
                                :side-offset="6"
                                class="z-50 w-[320px] rounded-xl border border-line bg-white p-0 shadow-elevated sm:w-[360px]"
                            >
                                <div class="flex items-center justify-between border-b border-line/80 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-ink">Notifications</p>
                                        <p class="text-xs text-muted">{{ unreadCountResolved }} unread</p>
                                    </div>
                                    <button
                                        v-if="unreadCountResolved > 0"
                                        type="button"
                                        :disabled="busyMarkAll"
                                        class="text-xs font-medium text-primary-600 hover:underline disabled:opacity-50"
                                        @click="markAllNotificationsRead"
                                    >
                                        {{ busyMarkAll ? 'Updating…' : 'Mark all read' }}
                                    </button>
                                </div>

                                <div v-if="recentNotifications.length === 0" class="px-4 py-8 text-center text-xs text-muted">
                                    No new notifications
                                </div>

                                <ul v-else class="max-h-[300px] divide-y divide-line/70 overflow-y-auto">
                                    <li
                                        v-for="item in recentNotifications"
                                        :key="item.id"
                                        class="cursor-pointer px-4 py-3 transition-colors hover:bg-sand-50"
                                        :class="item.isUnread ? 'bg-primary-50/35' : ''"
                                        @click="openNotificationItem(item)"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="line-clamp-1 text-xs font-semibold text-ink">
                                                {{ item.title }}
                                            </p>
                                            <span
                                                v-if="item.isUnread"
                                                class="mt-1 size-2 shrink-0 rounded-full bg-primary-600"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <p class="mt-0.5 line-clamp-2 text-xs text-muted">
                                            {{ item.message }}
                                        </p>
                                        <p class="tabular mt-1 text-[10.5px] text-muted/80">
                                            {{ item.createdAtLabel }}
                                        </p>
                                    </li>
                                </ul>

                                <div class="border-t border-line/80 p-2 text-center">
                                    <Link
                                        :href="routes.instructorNotifications"
                                        class="block rounded-lg py-1.5 text-xs font-medium text-primary-600 hover:bg-sand-50"
                                        @click="notifMenuOpen = false"
                                    >
                                        View all notifications
                                    </Link>
                                </div>
                            </DropdownMenuContent>
                        </DropdownMenuPortal>
                    </DropdownMenuRoot>

                    <!-- Account Menu -->
                    <DropdownMenuRoot>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-2 py-1.5 ring-1 ring-line transition-colors hover:bg-sand-50"
                                aria-label="Open instructor menu"
                            >
                                <UserAvatar
                                    :name="user?.name ?? 'Instructor'"
                                    :src="user?.photoUrl"
                                    size="sm"
                                />
                                <span class="hidden max-w-32 truncate text-xs font-semibold text-navy-950 sm:inline">
                                    {{ user?.name }}
                                </span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuPortal>
                            <DropdownMenuContent
                                align="end"
                                :side-offset="6"
                                class="z-50 w-56 rounded-xl bg-white p-1.5 shadow-elevated ring-1 ring-line"
                            >
                                <div class="px-2.5 py-2">
                                    <p class="truncate text-xs font-semibold text-navy-950">{{ user?.name }}</p>
                                    <p class="truncate text-[11px] text-muted">{{ user?.email }}</p>
                                </div>
                                <DropdownMenuSeparator class="my-1 h-px bg-line" />
                                <DropdownMenuItem
                                    as-child
                                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                >
                                    <Link :href="routes.instructorProfile">
                                        <UserCheck class="size-3.5 text-muted" />
                                        <span>My Profile</span>
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    as-child
                                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                >
                                    <Link :href="routes.instructorNotifications">
                                        <Bell class="size-3.5 text-muted" />
                                        <span>Notifications</span>
                                    </Link>
                                </DropdownMenuItem>
                                <template v-if="hasMultiplePortals">
                                    <DropdownMenuSeparator class="my-1 h-px bg-line" />
                                    <DropdownMenuItem
                                        v-for="portal in portals"
                                        :key="portal.slug"
                                        as-child
                                        class="flex cursor-pointer items-center rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                    >
                                        <a :href="portal.url">{{ portal.label }}</a>
                                    </DropdownMenuItem>
                                </template>
                                <DropdownMenuSeparator class="my-1 h-px bg-line" />
                                <DropdownMenuItem
                                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-danger-600 outline-none hover:bg-danger-50"
                                    @select="logout"
                                >
                                    <LogOut class="size-3.5" />
                                    <span>{{ loggingOut ? 'Signing out...' : 'Sign out' }}</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenuPortal>
                    </DropdownMenuRoot>
                </div>
            </header>

            <!-- Flash Banners -->
            <div v-if="flash?.status || flash?.error" class="px-4 pt-4 sm:px-6 lg:px-8">
                <div
                    v-if="flash?.status"
                    role="status"
                    class="flex items-center gap-2.5 rounded-xl border border-success-600/25 bg-success-50 px-4 py-3 text-sm font-medium text-success-700"
                >
                    <CheckCircle2 class="size-4 shrink-0" aria-hidden="true" />
                    <span>{{ flash.status }}</span>
                </div>
                <div
                    v-if="flash?.error"
                    role="alert"
                    class="mt-2 flex items-center gap-2.5 rounded-xl border border-danger-600/25 bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700"
                >
                    <AlertCircle class="size-4 shrink-0" aria-hidden="true" />
                    <span>{{ flash.error }}</span>
                </div>
            </div>

            <!-- Page Body -->
            <main id="main-content" class="mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
