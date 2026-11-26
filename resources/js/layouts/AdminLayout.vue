<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
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
    BarChart3,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    CreditCard,
    FileCheck2,
    KeyRound,
    LayoutDashboard,
    LogOut,
    Megaphone,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    RotateCcw,
    Search,
    Settings,
    Users,
    X,
} from 'lucide-vue-next';
import UserAvatar from '@/components/ds/UserAvatar.vue';
import type { EtmsSharedProps } from '@/types/etms';

const props = withDefaults(
    defineProps<{
        breadcrumb?: string;
    }>(),
    {
        breadcrumb: '',
    },
);

const page = usePage<EtmsSharedProps>();
const collapsed = ref(false);
const mobileOpen = ref(false);
const busy = ref(false);
const STORAGE_KEY = 'etms_admin_sidebar_collapsed';
const searchQuery = ref('');

const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);
const shortName = computed(() => page.props.app?.shortName || 'ETMS');

interface NavEntry {
    label: string;
    href: string;
    icon: typeof LayoutDashboard;
    isInertia: boolean;
}

interface NavGroup {
    label: string;
    items: NavEntry[];
}

const navGroups: NavGroup[] = [
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', href: '/admin-vue', icon: LayoutDashboard, isInertia: true },
        ],
    },
    {
        label: 'Training',
        items: [
            { label: 'Training Sessions', href: '/admin-vue/sessions', icon: CalendarDays, isInertia: true },
            { label: 'Modules', href: '/admin-vue/modules', icon: BookOpen, isInertia: true },
            { label: 'Module Access', href: '/admin-vue/module-entitlements', icon: KeyRound, isInertia: true },
            { label: 'Instructors', href: '/admin-vue/instructor-profiles', icon: Users, isInertia: true },
            { label: 'Certificates', href: '/admin-vue/certificates', icon: FileCheck2, isInertia: true },
        ],
    },
    {
        label: 'Finance & Reports',
        items: [
            { label: 'Payments & Orders', href: '/admin-vue/payments', icon: CreditCard, isInertia: true },
            { label: 'Refunds', href: '/admin-vue/refunds', icon: RotateCcw, isInertia: true },
            { label: 'Reports', href: '/admin-vue/reports', icon: BarChart3, isInertia: true },
        ],
    },
    {
        label: 'System',
        items: [
            { label: 'Users & Roles', href: '/admin-vue/users', icon: Users, isInertia: true },
            { label: 'Announcements', href: '/admin-vue/announcements', icon: Megaphone, isInertia: true },
            { label: 'Settings', href: '/admin-vue/settings', icon: Settings, isInertia: true },
        ],
    },
];

const allNavItems = navGroups.flatMap((g) => g.items);

const resolvedBreadcrumb = computed(() => {
    if (props.breadcrumb) return props.breadcrumb;
    const match = allNavItems.find((item) => isActive(item.href));
    return match?.label ?? 'Administration';
});

function isActive(href: string): boolean {
    const path = page.props.currentPath ?? '';
    if (href === '/admin-vue/certificates' && path.startsWith('/admin-vue/certificate-settings')) {
        return true;
    }
    return href === '/admin-vue' ? path === href : path.startsWith(href);
}

function logout(): void {
    busy.value = true;
    router.post(
        page.props.routes.logout,
        {},
        {
            onFinish: () => {
                busy.value = false;
            },
        },
    );
}

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
        // Ignore storage write restriction.
    }
}

function submitSearch(): void {
    const query = searchQuery.value.trim();
    router.get('/admin-vue/sessions', query ? { q: query } : {}, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <div class="min-h-screen bg-[#f7f6f2] text-ink">
        <!-- Desktop Collapsible Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-line/80 bg-[#f7f6f2] transition-[width] duration-150 lg:flex print:hidden"
            :class="collapsed ? 'w-[74px]' : 'w-64'"
        >
            <!-- Brand Header -->
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-line/70 px-4">
                <Link
                    v-if="!collapsed"
                    href="/admin-vue"
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

            <!-- Grouped Navigation -->
            <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4" aria-label="Administrator navigation">
                <div
                    v-for="(group, groupIdx) in navGroups"
                    :key="group.label"
                    :class="groupIdx > 0 && collapsed ? 'border-t border-line/70 pt-3' : ''"
                >
                    <p
                        v-if="!collapsed && groupIdx > 0"
                        class="mb-1.5 px-3 text-[11px] font-semibold text-muted/80"
                    >
                        {{ group.label }}
                    </p>

                    <div class="space-y-0.5">
                        <component
                            :is="item.isInertia ? Link : 'a'"
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            :title="collapsed ? item.label : undefined"
                            class="flex h-9 items-center gap-2.5 rounded-lg px-3 text-[13px] font-medium transition-colors duration-150"
                            :class="[
                                isActive(item.href)
                                    ? 'bg-navy-950 font-semibold text-white shadow-xs'
                                    : 'text-muted hover:bg-sand-100/80 hover:text-navy-950',
                                collapsed ? 'justify-center px-0' : '',
                            ]"
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
                    class="mb-2 flex items-center gap-2.5 rounded-lg bg-sand-50/90 px-2.5 py-2"
                >
                    <UserAvatar
                        :name="user?.name ?? 'Admin'"
                        :photo-url="user?.photoUrl"
                        size="sm"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-navy-950">
                            {{ user?.name ?? 'Administrator' }}
                        </p>
                        <p class="truncate text-[11px] text-muted">Administrator</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="flex h-9 w-full items-center gap-2.5 rounded-lg px-3 text-[13px] font-medium text-muted transition-colors hover:bg-sand-100 hover:text-navy-950 disabled:opacity-50"
                    :class="collapsed ? 'justify-center px-0' : ''"
                    :title="collapsed ? 'Sign out' : undefined"
                    :disabled="busy"
                    @click="logout"
                >
                    <LogOut class="size-4 shrink-0" aria-hidden="true" />
                    <span v-if="!collapsed">{{ busy ? 'Signing out...' : 'Sign out' }}</span>
                </button>
            </div>
        </aside>

        <!-- Main Column -->
        <div
            class="min-h-screen transition-[padding] duration-150 print:pl-0"
            :class="collapsed ? 'lg:pl-[74px]' : 'lg:pl-64'"
        >
            <!-- Sticky Topbar -->
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line/70 bg-[#f7f6f2]/90 px-4 backdrop-blur-md sm:px-6 lg:px-8 print:hidden">
                <!-- Mobile Drawer Trigger -->
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

                            <nav class="flex-1 space-y-4 overflow-y-auto p-3" aria-label="Mobile admin navigation">
                                <div v-for="(group, idx) in navGroups" :key="group.label">
                                    <p v-if="idx > 0" class="mb-1.5 px-3 text-[11px] font-semibold text-muted/80">
                                        {{ group.label }}
                                    </p>
                                    <div class="space-y-0.5">
                                        <component
                                            :is="item.isInertia ? Link : 'a'"
                                            v-for="item in group.items"
                                            :key="item.href"
                                            :href="item.href"
                                            class="flex h-9 items-center gap-2.5 rounded-lg px-3 text-[13px] font-medium transition-colors"
                                            :class="
                                                isActive(item.href)
                                                    ? 'bg-navy-950 font-semibold text-white'
                                                    : 'text-muted hover:bg-sand-100 hover:text-navy-950'
                                            "
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
                                    :disabled="busy"
                                    @click="logout"
                                >
                                    <LogOut class="size-4" aria-hidden="true" />
                                    <span>Sign out</span>
                                </button>
                            </div>
                        </DialogContent>
                    </DialogPortal>
                </DialogRoot>

                <!-- Current Section Breadcrumb -->
                <div class="hidden items-center gap-2 text-sm sm:flex">
                    <span class="font-medium text-muted">Admin</span>
                    <span class="text-line-strong">/</span>
                    <span class="font-semibold text-navy-950">{{ resolvedBreadcrumb }}</span>
                </div>

                <!-- Quick Session Search -->
                <form class="ml-auto hidden max-w-xs flex-1 md:block" @submit.prevent="submitSearch">
                    <label class="relative block">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search sessions..."
                            aria-label="Search training sessions"
                            class="h-9 w-full rounded-lg border border-line bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </label>
                </form>

                <!-- Right Actions -->
                <div class="ml-auto flex items-center gap-2.5 md:ml-0">
                    <DropdownMenuRoot>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-2 py-1.5 ring-1 ring-line transition-colors hover:bg-sand-50"
                                aria-label="Open administrator menu"
                            >
                                <UserAvatar
                                    :name="user?.name ?? 'Admin'"
                                    :photo-url="user?.photoUrl"
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
                                class="z-50 w-56 rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-neutral-200"
                            >
                                <div class="px-2.5 py-2">
                                    <p class="truncate text-xs font-semibold text-navy-950">{{ user?.name }}</p>
                                    <p class="truncate text-[11px] text-muted">{{ user?.email }}</p>
                                </div>
                                <DropdownMenuSeparator class="my-1 h-px bg-line" />
                                <DropdownMenuItem
                                    v-if="user?.isCustomer"
                                    as-child
                                    class="flex cursor-pointer items-center rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                >
                                    <Link href="/portal">Trainee Portal</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="user?.isInstructor"
                                    as-child
                                    class="flex cursor-pointer items-center rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                >
                                    <Link href="/instructor">Instructor Portal</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    as-child
                                    class="flex cursor-pointer items-center rounded-lg px-2.5 py-2 text-xs font-medium text-ink outline-none hover:bg-sand-100"
                                >
                                    <Link href="/">Public Website</Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator class="my-1 h-px bg-line" />
                                <DropdownMenuItem
                                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-xs font-medium text-danger-700 outline-none hover:bg-danger-50"
                                    @select="logout"
                                >
                                    <LogOut class="size-3.5" aria-hidden="true" />
                                    <span>Sign out</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenuPortal>
                    </DropdownMenuRoot>
                </div>
            </header>

            <!-- Main Content -->
            <main class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 print:p-0">
                <!-- Flash Messages -->
                <div
                    v-if="flash?.status"
                    role="status"
                    class="mb-5 flex items-center gap-2.5 rounded-xl bg-success-50 px-4 py-3 text-sm font-medium text-success-700 ring-1 ring-success-600/20 print:hidden"
                >
                    <CheckCircle2 class="size-4 shrink-0" aria-hidden="true" />
                    <span>{{ flash.status }}</span>
                </div>

                <div
                    v-if="flash?.error"
                    role="alert"
                    class="mb-5 flex items-center gap-2.5 rounded-xl bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700 ring-1 ring-danger-600/20 print:hidden"
                >
                    <AlertCircle class="size-4 shrink-0" aria-hidden="true" />
                    <span>{{ flash.error }}</span>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
