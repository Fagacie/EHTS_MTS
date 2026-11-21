<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    BookOpen,
    CalendarCheck,
    Compass,
    LayoutDashboard,
    LogOut,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    ShieldCheck,
    UserCheck,
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
} from '@/types/etms';

interface NavEntry {
    label: string;
    href: string;
    icon: unknown;
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
const app = computed(() => page.props.app);
const flash = computed(() => page.props.flash);
const instructorShell = computed(() => page.props.instructorShell);

const { user, portals, hasMultiplePortals, loggingOut, logout } = useAuth();
const { routes, isActive } = useRoutes();

const collapsed = ref(false);
const mobileMenuOpen = ref(false);
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
        active: isActive('/instructor') && !isActive('/instructor/sessions') && !isActive('/instructor/profile') && !isActive('/instructor/notifications'),
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
        // Ignore storage write restriction
    }
}

function closeMobileMenu(): void {
    mobileMenuOpen.value = false;
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
    <div class="portal-violet min-h-screen bg-[#F7F6FA] font-sans text-[#292536] lg:flex">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-[#7057D9] focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-white focus:outline-none"
        >
            Skip to main content
        </a>

        <!-- Desktop Sidebar -->
        <aside
            :class="
                cn(
                    'hidden lg:block lg:shrink-0 lg:border-r lg:border-[#E5E1EB] lg:bg-[#F7F6FA] lg:text-[#292536] lg:transition-[width] lg:duration-150',
                    collapsed ? 'lg:w-[74px]' : 'lg:w-[238px]',
                )
            "
            aria-label="Instructor Portal Navigation"
        >
            <div class="sticky top-0 flex h-screen flex-col">
                <!-- Brand / Logo -->
                <div :class="cn('px-4 pt-5 pb-5', collapsed && 'flex justify-center px-2')">
                    <Link
                        :href="routes.instructorDashboard"
                        title="Ecohydrotech Instructor Portal"
                        aria-label="Ecohydrotech Instructor Portal"
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

                <!-- Instructor Navigation -->
                <nav class="flex flex-1 flex-col overflow-y-auto px-3 pb-4" aria-label="Instructor Sidebar">
                    <ul class="space-y-1">
                        <li v-for="item in instructorNav" :key="item.label">
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
                                    class="inline-flex min-w-5 items-center justify-center rounded-full bg-[#7057D9] px-1.5 py-0.5 text-[10px] font-bold text-white tabular-nums"
                                >
                                    {{ item.badge }}
                                </span>
                            </component>
                        </li>
                    </ul>

                    <div class="my-4 h-px bg-[#E5E1EB]" aria-hidden="true" />

                    <!-- Utility Nav -->
                    <ul class="space-y-1">
                        <li v-for="item in utilityNav" :key="item.label">
                            <component
                                :is="item.isInertia ? Link : 'a'"
                                :href="item.href"
                                :title="collapsed ? item.label : undefined"
                                :class="
                                    cn(
                                        'group flex items-center justify-between gap-2.5 rounded-[8px] px-3 py-2.5 text-[13.5px] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                        'font-medium text-[#403B4B] hover:bg-[#EAE5F8]/60 hover:text-[#282338]',
                                        collapsed && 'justify-center px-2',
                                    )
                                "
                            >
                                <span :class="cn('flex min-w-0 items-center gap-3', collapsed && 'gap-0')">
                                    <component
                                        :is="item.icon"
                                        class="h-[17px] w-[17px] shrink-0 text-[#625D6D] transition-colors group-hover:text-[#5942B5]"
                                        aria-hidden="true"
                                    />
                                    <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                                </span>
                            </component>
                        </li>
                    </ul>

                    <!-- Collapse / Expand trigger -->
                    <div class="mt-auto pt-3">
                        <button
                            type="button"
                            :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                            :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                            class="flex w-full items-center justify-center gap-2 rounded-[8px] py-2 text-[12px] font-semibold text-[#625D6D] transition-colors hover:bg-[#EAE5F8]/60 hover:text-[#282338] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40"
                            @click="toggleSidebarCollapse"
                        >
                            <component
                                :is="collapsed ? PanelLeftOpen : PanelLeftClose"
                                class="h-4 w-4"
                                aria-hidden="true"
                            />
                            <span v-if="!collapsed">Collapse Menu</span>
                        </button>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- Main Body Wrapper -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top Header Bar -->
            <header
                class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-4 border-b border-[#E5E1EB] bg-[#F7F6FA]/95 px-4 backdrop-blur sm:px-6 lg:px-8"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <!-- Mobile Hamburger -->
                    <Sheet v-model:open="mobileMenuOpen">
                        <SheetTrigger as-child>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-9 w-9 p-0 text-[#403B4B] hover:bg-[#EAE5F8] lg:hidden"
                                aria-label="Open navigation menu"
                            >
                                <Menu class="h-5 w-5" aria-hidden="true" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            class="w-[280px] border-r border-[#E5E1EB] bg-[#F7F6FA] p-0 text-[#292536]"
                        >
                            <SheetHeader class="border-b border-[#E5E1EB] p-4 text-left">
                                <SheetTitle class="flex items-center gap-2.5 text-[16px] font-extrabold text-[#282338]">
                                    <img
                                        :src="app.logoUrl"
                                        alt=""
                                        width="28"
                                        height="28"
                                        class="h-7 w-7 rounded-[8px] object-contain"
                                        aria-hidden="true"
                                    >
                                    <span>Ecohydrotech</span>
                                </SheetTitle>
                                <SheetDescription class="text-[12px] text-[#625D6D]">
                                    Instructor Portal Workspace
                                </SheetDescription>
                            </SheetHeader>

                            <div class="flex flex-1 flex-col overflow-y-auto p-3">
                                <ul class="space-y-1">
                                    <li v-for="item in instructorNav" :key="item.label">
                                        <component
                                            :is="item.isInertia ? Link : 'a'"
                                            :href="item.href"
                                            :class="
                                                cn(
                                                    'flex items-center justify-between rounded-[8px] px-3 py-2 text-[13.5px] transition-colors',
                                                    item.active
                                                        ? 'bg-[#EAE5F8] font-bold text-[#5942B5]'
                                                        : 'font-medium text-[#403B4B] hover:bg-[#EAE5F8]/60 hover:text-[#282338]',
                                                )
                                            "
                                            @click="closeMobileMenu"
                                        >
                                            <span class="flex items-center gap-2.5">
                                                <component :is="item.icon" class="h-4 w-4" />
                                                <span>{{ item.label }}</span>
                                            </span>
                                            <span
                                                v-if="item.badge"
                                                class="rounded-full bg-[#7057D9] px-2 py-0.5 text-[10px] font-bold text-white tabular-nums"
                                            >
                                                {{ item.badge }}
                                            </span>
                                        </component>
                                    </li>
                                </ul>

                                <div class="my-4 h-px bg-[#E5E1EB]" />

                                <ul class="space-y-1">
                                    <li v-for="item in utilityNav" :key="item.label">
                                        <component
                                            :is="item.isInertia ? Link : 'a'"
                                            :href="item.href"
                                            class="flex items-center gap-2.5 rounded-[8px] px-3 py-2 text-[13.5px] font-medium text-[#403B4B] hover:bg-[#EAE5F8]/60 hover:text-[#282338]"
                                            @click="closeMobileMenu"
                                        >
                                            <component :is="item.icon" class="h-4 w-4 text-[#625D6D]" />
                                            <span>{{ item.label }}</span>
                                        </component>
                                    </li>
                                </ul>
                            </div>
                        </SheetContent>
                    </Sheet>

                    <!-- Breadcrumbs -->
                    <nav class="hidden min-w-0 items-center text-[13.5px] font-medium sm:flex" aria-label="Breadcrumb">
                        <Link
                            :href="routes.instructorDashboard"
                            class="text-[#625D6D] transition-colors hover:text-[#282338]"
                        >
                            Instructor
                        </Link>
                        <span class="mx-2 text-[#C0BBC9]">/</span>
                        <span class="truncate font-bold text-[#282338]">{{ breadcrumb }}</span>
                    </nav>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Notifications Dropdown -->
                    <DropdownMenu v-model:open="notifMenuOpen">
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="relative h-9 w-9 border-[#E5E1EB] bg-white p-0 text-[#403B4B] shadow-none hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338] focus-visible:ring-[#7057D9]/40"
                                aria-label="Notifications"
                            >
                                <Bell class="h-4 w-4" aria-hidden="true" />
                                <span
                                    v-if="unreadCountResolved > 0"
                                    class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[#7057D9] px-1 text-[9.5px] font-bold text-white tabular-nums ring-2 ring-[#F7F6FA]"
                                >
                                    {{ unreadCountResolved > 9 ? '9+' : unreadCountResolved }}
                                </span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-[320px] rounded-[10px] border-[#E5E1EB] bg-white p-0 text-[#292536] shadow-lg sm:w-[360px]"
                        >
                            <div class="flex items-center justify-between border-b border-[#EEEAF2] px-4 py-3">
                                <div>
                                    <p class="text-[13.5px] font-bold text-[#282338]">Notifications</p>
                                    <p class="text-[11.5px] text-[#625D6D]">
                                        {{ unreadCountResolved }} unread updates
                                    </p>
                                </div>
                                <button
                                    v-if="unreadCountResolved > 0"
                                    type="button"
                                    :disabled="busyMarkAll"
                                    class="text-[11.5px] font-bold text-[#5942B5] transition-colors hover:text-[#7057D9] disabled:opacity-50"
                                    @click="markAllNotificationsRead"
                                >
                                    {{ busyMarkAll ? 'Updating...' : 'Mark all read' }}
                                </button>
                            </div>

                            <div v-if="recentNotifications.length === 0" class="px-4 py-8 text-center">
                                <p class="text-[13px] font-medium text-[#625D6D]">No new notifications</p>
                            </div>

                            <ul v-else class="max-h-[300px] divide-y divide-[#EEEAF2] overflow-y-auto">
                                <li
                                    v-for="item in recentNotifications"
                                    :key="item.id"
                                    :class="
                                        cn(
                                            'cursor-pointer px-4 py-3 transition-colors hover:bg-[#FAF9FE]',
                                            item.isUnread && 'bg-[#F9F7FD]',
                                        )
                                    "
                                    @click="openNotificationItem(item)"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="line-clamp-1 text-[13px] font-bold text-[#282338]">
                                            {{ item.title }}
                                        </p>
                                        <span
                                            v-if="item.isUnread"
                                            class="mt-1 h-2 w-2 shrink-0 rounded-full bg-[#7057D9]"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    <p class="mt-0.5 line-clamp-2 text-[12px] leading-relaxed text-[#625D6D]">
                                        {{ item.message }}
                                    </p>
                                    <p class="mt-1 text-[10.5px] text-[#868092] tabular-nums">
                                        {{ item.createdAtLabel }}
                                    </p>
                                </li>
                            </ul>

                            <div class="border-t border-[#EEEAF2] p-2 text-center">
                                <Link
                                    :href="routes.instructorNotifications"
                                    class="block rounded-[6px] py-1.5 text-[12px] font-bold text-[#5942B5] hover:bg-[#FAF9FE] hover:text-[#7057D9]"
                                    @click="notifMenuOpen = false"
                                >
                                    View all notifications
                                </Link>
                            </div>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <!-- Multi-role Portal Switcher Pill -->
                    <div v-if="hasMultiplePortals" class="hidden sm:block">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-9 gap-1.5 border-[#E5E1EB] bg-white px-2.5 text-[12px] font-bold text-[#403B4B] shadow-none hover:border-[#B9ADE9] hover:bg-[#FAF9FE]"
                                >
                                    <span class="h-2 w-2 rounded-full bg-[#7057D9]" />
                                    <span>Instructor</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                class="w-56 rounded-[10px] border-[#E5E1EB] bg-white text-[#292536] shadow-lg"
                            >
                                <DropdownMenuLabel class="text-[11px] font-bold uppercase tracking-wider text-[#625D6D]">
                                    Switch Portal
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator class="bg-[#EEEAF2]" />
                                <DropdownMenuItem
                                    v-for="portal in portals"
                                    :key="portal.slug"
                                    as-child
                                    class="text-[13px] font-medium hover:bg-[#FAF9FE]"
                                >
                                    <a :href="portal.url" class="flex flex-col items-start gap-0.5">
                                        <span class="font-bold text-[#282338]">{{ portal.label }}</span>
                                        <span class="text-[11px] text-[#625D6D]">{{ portal.description }}</span>
                                    </a>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- User Account Dropdown -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="flex h-9 items-center gap-2 rounded-full p-1 hover:bg-[#EAE5F8] focus-visible:ring-[#7057D9]/40"
                                aria-label="Open user menu"
                            >
                                <img v-if="user?.photoUrl" :src="user.photoUrl" alt="" class="h-7 w-7 rounded-full object-cover"/>
                                <span v-else class="flex h-7 w-7 items-center justify-center rounded-full bg-[#7057D9] text-[11px] font-extrabold text-white">
                                    {{ getInitials(user?.name ?? '') }}
                                </span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-60 rounded-[10px] border-[#E5E1EB] bg-white p-1 text-[#292536] shadow-lg"
                        >
                            <div class="px-3 py-2.5">
                                <p class="truncate text-[13.5px] font-bold text-[#282338]">
                                    {{ user?.name }}
                                </p>
                                <p class="truncate text-[11.5px] text-[#625D6D]">
                                    {{ user?.email }}
                                </p>
                            </div>
                            <DropdownMenuSeparator class="bg-[#EEEAF2]" />
                            <DropdownMenuItem as-child class="text-[13px] font-medium hover:bg-[#FAF9FE]">
                                <Link :href="routes.instructorProfile" class="flex items-center gap-2.5">
                                    <UserCheck class="h-4 w-4 text-[#625D6D]" />
                                    <span>My Profile</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child class="text-[13px] font-medium hover:bg-[#FAF9FE]">
                                <Link :href="routes.instructorNotifications" class="flex items-center gap-2.5">
                                    <Bell class="h-4 w-4 text-[#625D6D]" />
                                    <span>Notifications</span>
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator class="bg-[#EEEAF2]" />
                            <DropdownMenuItem
                                :disabled="loggingOut"
                                class="text-[13px] font-bold text-[#A33C48] hover:bg-[#FBECEE]"
                                @click="logout"
                            >
                                <div class="flex items-center gap-2.5">
                                    <LogOut class="h-4 w-4" />
                                    <span>{{ loggingOut ? 'Signing out...' : 'Sign out' }}</span>
                                </div>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <!-- Flash alerts -->
            <div
                v-if="flash.status || flash.error"
                class="border-b border-[#E5E1EB] bg-white px-4 py-2.5 sm:px-6 lg:px-8"
            >
                <div class="mx-auto max-w-[1440px] space-y-2">
                    <p
                        v-if="flash.status"
                        role="status"
                        class="rounded-[8px] border border-[#C5E1D4] bg-[#EDF7F2] px-3.5 py-2 text-[13.5px] font-medium text-[#1E5638]"
                    >
                        {{ flash.status }}
                    </p>
                    <p
                        v-if="flash.error"
                        role="alert"
                        class="rounded-[8px] border border-[#ECC4CA] bg-[#FBECEE] px-3.5 py-2 text-[13.5px] font-bold text-[#A33C48]"
                    >
                        {{ flash.error }}
                    </p>
                </div>
            </div>

            <!-- Page Body Content -->
            <main id="main-content" class="flex-1 overflow-x-hidden px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
