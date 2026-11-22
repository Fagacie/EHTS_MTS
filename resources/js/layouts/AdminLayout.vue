<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    CreditCard,
    FileCheck2,
    KeyRound,
    LayoutDashboard,
    LogOut,
    Menu,
    Users,
    Megaphone,
    X,
    Settings,
    Bell,
    PanelLeftClose,
    PanelLeftOpen,
    Search,
} from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { getInitials, cn } from '@/lib/utils';
import type { EtmsSharedProps } from '@/types/etms';

const page = usePage<EtmsSharedProps>();
const collapsed = ref(false);
const mobileOpen = ref(false);
const busy = ref(false);
const STORAGE_KEY = 'etms_admin_sidebar_collapsed';
const searchQuery = ref('');

const user = computed(() => page.props.auth.user);
const userInitials = computed(() => getInitials(user.value?.name ?? 'Administrator'));
const unreadCount = computed(() => user.value?.unreadNotificationsCount ?? 0);

const navItems = [
    { label: 'Dashboard', href: '/admin-vue', icon: LayoutDashboard, isInertia: true },
    { label: 'Physical Sessions', href: '/admin-vue/sessions', icon: CalendarDays, isInertia: true },
    { label: 'Standalone Modules', href: '/admin-vue/modules', icon: BookOpen, isInertia: true },
    { label: 'Module Access', href: '/admin-vue/module-entitlements', icon: KeyRound, isInertia: true },
    { label: 'Instructors', href: '/admin-vue/instructor-profiles', icon: Users, isInertia: true },
    { label: 'Payments & Orders', href: '/admin-vue/payments', icon: CreditCard, isInertia: true },
    { label: 'Refunds', href: '/admin-vue/refunds', icon: CreditCard, isInertia: true },
    { label: 'Users & Roles', href: '/admin-vue/users', icon: Users, isInertia: true },
    { label: 'Certificates', href: '/admin-vue/certificates', icon: FileCheck2, isInertia: true },
    { label: 'Announcements', href: '/admin-vue/announcements', icon: Megaphone, isInertia: true },
    { label: 'Reports', href: '/admin-vue/reports', icon: BarChart3, isInertia: true },
    { label: 'Settings', href: '/admin-vue/certificate-settings', icon: Settings, isInertia: true },
];

function isActive(href: string): boolean {
    return href === '/admin-vue'
        ? page.props.currentPath === href
        : page.props.currentPath.startsWith(href);
}

function logout(): void {
    busy.value = true;
    router.post(page.props.routes.logout, {}, {
        onFinish: () => {
            busy.value = false;
        },
    });
}

try {
    collapsed.value = window.localStorage.getItem(STORAGE_KEY) === '1';
} catch {
    collapsed.value = false;
}

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
    <div class="portal-violet min-h-screen bg-[#F7F6FA] text-[#292536]">
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 hidden border-r border-[#E5E1EB] bg-white transition-[width] duration-150 lg:flex lg:flex-col',
                collapsed ? 'w-[72px]' : 'w-64',
            ]"
        >
            <div class="flex h-16 items-center justify-between border-b border-[#E5E1EB] px-4">
                <Link v-if="!collapsed" href="/admin-vue" class="truncate text-[14px] font-extrabold text-[#7057D9]">
                    Ecohydrotech Admin
                </Link>
                <button
                    type="button"
                    class="grid h-9 w-9 place-items-center rounded-[8px] text-[#625D6D] transition-colors hover:bg-[#F3F1F7] hover:text-[#292536]"
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    @click="toggleSidebarCollapse"
                >
                    <PanelLeftOpen v-if="collapsed" :size="18" />
                    <PanelLeftClose v-else :size="18" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 p-3" aria-label="Administrator navigation">
                <template v-for="item in navItems" :key="item.label">
                <component
                    :is="item.isInertia ? Link : 'a'"
                    :href="item.href"
                    :title="collapsed ? item.label : undefined"
                    :class="[
                        'flex h-10 items-center gap-3 rounded-[8px] px-3 text-[13px] font-semibold transition-colors duration-150',
                        item.href && isActive(item.href)
                            ? 'bg-[#7057D9] text-white'
                            : 'text-[#625D6D] hover:bg-[#F3F1F7] hover:text-[#292536]',
                        collapsed ? 'justify-center' : '',
                    ]"
                >
                    <component :is="item.icon" :size="16" />
                    <span v-if="!collapsed">{{ item.label }}</span>
                </component>
                </template>
            </nav>

            <div class="border-t border-[#E5E1EB] p-3">
                <button
                    type="button"
                    class="flex h-10 w-full items-center gap-3 rounded-[8px] px-3 text-[13px] font-semibold text-[#625D6D] hover:bg-[#F3F1F7] hover:text-[#292536]"
                    :class="collapsed ? 'justify-center' : ''"
                    :disabled="busy"
                    @click="logout"
                >
                    <LogOut :size="16" />
                    <span v-if="!collapsed">{{ busy ? 'Signing out…' : 'Sign out' }}</span>
                </button>
            </div>
        </aside>

        <div :class="collapsed ? 'lg:pl-[72px]' : 'lg:pl-64'">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-[#E5E1EB] bg-white/95 px-4 backdrop-blur sm:px-6">
                <Sheet v-model:open="mobileOpen">
                    <SheetTrigger as-child>
                        <button type="button" class="grid h-9 w-9 place-items-center rounded-[8px] text-[#625D6D] hover:bg-[#F3F1F7] lg:hidden" aria-label="Open menu"><Menu :size="20" /></button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-72 p-0">
                        <SheetHeader class="border-b border-[#E5E1EB] px-4 text-left"><SheetTitle class="text-[#7057D9]">Ecohydrotech Admin</SheetTitle><SheetDescription>Administrator workspace</SheetDescription></SheetHeader>
                        <nav class="space-y-1 p-3" aria-label="Administrator mobile navigation">
                            <component v-for="item in navItems" :key="item.label" :is="item.isInertia ? Link : 'a'" :href="item.href" class="flex h-10 items-center gap-3 rounded-[8px] px-3 text-[13px] font-semibold text-[#625D6D] hover:bg-[#F3F1F7]" @click="mobileOpen=false"><component :is="item.icon" :size="16"/>{{item.label}}</component>
                        </nav>
                    </SheetContent>
                </Sheet>

                <form class="hidden max-w-md flex-1 md:block" @submit.prevent="submitSearch">
                    <label class="relative block">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-[#625D6D]" :size="16"/>
                        <Input v-model="searchQuery" class="h-10 pl-9" placeholder="Search training sessions"/>
                    </label>
                </form>

                <div class="ml-auto flex items-center gap-2">
                    <button type="button" class="relative grid h-9 w-9 place-items-center rounded-[8px] text-[#625D6D] hover:bg-[#F3F1F7]" aria-label="Notifications">
                        <Bell :size="18"/>
                        <span v-if="unreadCount>0" class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-[#7057D9]"/>
                    </button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button type="button" class="flex items-center gap-2 rounded-[8px] p-1.5 hover:bg-[#F3F1F7]" aria-label="Open profile menu">
                                <span class="hidden text-[13px] font-semibold text-[#403B4B] sm:inline">{{user?.name}}</span>
                                <img v-if="user?.photoUrl" :src="user.photoUrl" alt="" class="h-8 w-8 rounded-full object-cover"/>
                                <span v-else class="grid h-8 w-8 place-items-center rounded-full bg-[#7057D9] text-[12px] font-bold text-white">{{userInitials}}</span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <DropdownMenuLabel>{{user?.email}}</DropdownMenuLabel>
                            <DropdownMenuSeparator/>
                            <DropdownMenuItem v-if="user?.isCustomer" as-child><Link href="/portal">Trainee Portal</Link></DropdownMenuItem>
                            <DropdownMenuItem v-if="user?.isInstructor" as-child><Link href="/instructor">Instructor Portal</Link></DropdownMenuItem>
                            <DropdownMenuSeparator/>
                            <DropdownMenuItem @select="logout">Sign out</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <main class="mx-auto max-w-7xl p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
