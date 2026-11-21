<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Menu } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
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
import { getPublicNavItems } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import type { EtmsSharedProps } from '@/types/etms';

const mobileMenuOpen = ref(false);

const page = usePage<EtmsSharedProps>();
const app = computed(() => page.props.app);
const {
    user,
    isAuthenticated,
    portals,
    hasMultiplePortals,
    defaultDashboardUrl,
    loggingOut,
    logout,
} = useAuth();
const { routes, isActive } = useRoutes();

const navItems = computed(() => getPublicNavItems(routes.value));

function closeMobileMenu(): void {
    mobileMenuOpen.value = false;
}
</script>

<template>
    <header
        class="sticky top-0 z-40 h-[72px] border-b border-[#252238]/10 bg-white/95 text-[#252238] backdrop-blur-md"
    >
        <div
            class="mx-auto flex h-full max-w-6xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8"
        >
            <Link
                :href="routes.home"
                class="inline-flex shrink-0 items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30 focus-visible:ring-offset-2"
            >
                <img
                    :src="app.logoUrl"
                    alt="Ecohydrotech Solution"
                    width="28"
                    height="28"
                    class="h-7 w-7 shrink-0"
                >
                <span class="text-[18px] font-extrabold tracking-tight text-[#252238]">
                    Ecohydrotech
                </span>
            </Link>

            <nav
                class="hidden items-center gap-4 md:flex"
                aria-label="Primary Navigation"
            >
                <template v-for="item in navItems" :key="item.label">
                    <Link
                        v-if="item.isInertia"
                        :href="item.href"
                        :class="
                            cn(
                                'rounded-md px-3 py-2 text-[14px] font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/30',
                                isActive(item.matchPrefix)
                                    ? 'bg-[#F0ECFC] text-[#5942B5]'
                                    : 'text-[#625D6D] hover:bg-[#F7F8FC] hover:text-[#252238]',
                            )
                        "
                    >
                        {{ item.label }}
                    </Link>
                    <a
                        v-else
                        :href="item.href"
                        :class="
                            cn(
                                'rounded-lg px-3 py-1.5 text-[15px] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30 focus-visible:ring-offset-2',
                                isActive(item.matchPrefix)
                                    ? 'bg-[#272d38]/[0.06] font-bold text-[#272d38]'
                                    : 'font-normal text-[#272d38]/70 hover:bg-[#272d38]/[0.03] hover:text-[#272d38]',
                            )
                        "
                    >
                        {{ item.label }}
                    </a>
                </template>
            </nav>

            <div class="hidden shrink-0 items-center gap-4 md:flex">
                <template v-if="isAuthenticated && user">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="gap-1.5 text-[15px]"
                            >
                                <span class="max-w-36 truncate">{{ user.name }}</span>
                                <ChevronDown class="h-4 w-4 text-[#272d38]/60" aria-hidden="true" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-60">
                            <DropdownMenuLabel>
                                <p class="truncate text-[15px] font-bold text-[#272d38]">
                                    {{ user.name }}
                                </p>
                                <p class="truncate text-sm font-normal text-[#272d38]/65">
                                    {{ user.email }}
                                </p>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <a :href="defaultDashboardUrl" class="text-[15px] font-bold text-[#272d38]">
                                    My Portal
                                </a>
                            </DropdownMenuItem>
                            <template v-if="hasMultiplePortals">
                                <DropdownMenuSeparator />
                                <DropdownMenuLabel class="text-[13px] font-bold text-[#272d38]/65">
                                    Switch Portal
                                </DropdownMenuLabel>
                                <DropdownMenuItem
                                    v-for="portal in portals"
                                    :key="portal.slug"
                                    as-child
                                >
                                    <a :href="portal.url" class="flex flex-col items-start gap-0.5">
                                        <span class="text-[15px] font-semibold text-[#272d38]">
                                            {{ portal.label }}
                                        </span>
                                        <span class="text-sm text-[#272d38]/65">
                                            {{ portal.description }}
                                        </span>
                                    </a>
                                </DropdownMenuItem>
                            </template>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                :disabled="loggingOut"
                                class="text-[15px] text-[#272d38]"
                                @select.prevent="logout"
                            >
                                {{ loggingOut ? 'Signing out...' : 'Sign out' }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <template v-else>
                    <Link
                        :href="routes.login"
                        class="rounded-md px-3 py-2 text-[14px] font-semibold text-[#625D6D] transition-colors hover:text-[#252238]"
                    >
                        Sign in
                    </Link>
                    <Button
                        as-child
                        size="sm"
                        class="rounded-md bg-[#7057D9] px-4 text-[14px] font-bold shadow-none hover:bg-[#5942B5]"
                    >
                        <Link :href="routes.register">
                            Register
                        </Link>
                    </Button>
                </template>
            </div>

            <div class="flex items-center md:hidden">
                <Sheet v-model:open="mobileMenuOpen">
                    <SheetTrigger as-child>
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-10 w-10"
                            aria-label="Open navigation menu"
                        >
                            <Menu class="h-4 w-4" aria-hidden="true" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="right"
                        class="overflow-y-auto"
                    >
                        <SheetHeader class="border-b border-[#272d38]/10 pb-4 text-left">
                            <div class="inline-flex items-center gap-2.5">
                                <img
                                    :src="app.logoUrl"
                                    alt="Ecohydrotech Solution"
                                    width="24"
                                    height="24"
                                    class="h-6 w-6 shrink-0"
                                >
                                <SheetTitle class="text-[17px] font-bold text-[#272d38]">Ecohydrotech</SheetTitle>
                            </div>
                            <SheetDescription class="sr-only">
                                Site navigation and account links.
                            </SheetDescription>
                        </SheetHeader>

                        <nav class="space-y-2 py-2" aria-label="Mobile Navigation">
                            <template v-for="item in navItems" :key="item.label">
                                <Link
                                    v-if="item.isInertia"
                                    :href="item.href"
                                    :class="
                                        cn(
                                            'block rounded-lg px-3 py-2 text-[16px] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30',
                                            isActive(item.matchPrefix)
                                                ? 'bg-[#272d38]/[0.07] font-bold text-[#272d38]'
                                                : 'font-normal text-[#272d38]/75 hover:bg-[#272d38]/[0.04] hover:text-[#272d38]',
                                        )
                                    "
                                    @click="closeMobileMenu"
                                >
                                    {{ item.label }}
                                </Link>
                                <a
                                    v-else
                                    :href="item.href"
                                    :class="
                                        cn(
                                            'block rounded-lg px-3 py-2 text-[16px] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#272d38]/30',
                                            isActive(item.matchPrefix)
                                                ? 'bg-[#272d38]/[0.07] font-bold text-[#272d38]'
                                                : 'font-normal text-[#272d38]/75 hover:bg-[#272d38]/[0.04] hover:text-[#272d38]',
                                        )
                                    "
                                    @click="closeMobileMenu"
                                >
                                    {{ item.label }}
                                </a>
                            </template>
                        </nav>

                        <Separator />

                        <div v-if="isAuthenticated && user" class="space-y-4">
                            <div class="space-y-0.5">
                                <p class="truncate text-[16px] font-bold text-[#272d38]">
                                    {{ user.name }}
                                </p>
                                <p class="truncate text-sm text-[#272d38]/65">
                                    {{ user.email }}
                                </p>
                            </div>

                            <div v-if="hasMultiplePortals" class="space-y-2.5">
                                <p class="text-[13px] font-bold text-[#272d38]/60">
                                    Portals
                                </p>
                                <a
                                    v-for="portal in portals"
                                    :key="portal.slug"
                                    :href="portal.url"
                                    class="block text-[15px] font-semibold text-[#272d38] hover:underline"
                                    @click="closeMobileMenu"
                                >
                                    {{ portal.label }}
                                </a>
                            </div>

                            <Button
                                as="a"
                                :href="defaultDashboardUrl"
                                class="w-full"
                            >
                                My Portal
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                class="w-full"
                                :disabled="loggingOut"
                                @click="logout"
                            >
                                {{ loggingOut ? 'Signing out...' : 'Sign out' }}
                            </Button>
                        </div>

                        <div v-else class="space-y-3">
                            <Button
                                as-child
                                variant="outline"
                                class="w-full"
                                @click="closeMobileMenu"
                            >
                                <Link :href="routes.login">
                                    Sign in
                                </Link>
                            </Button>
                            <Button
                                as-child
                                class="w-full"
                                @click="closeMobileMenu"
                            >
                                <Link :href="routes.register">
                                    Register
                                </Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    </header>
</template>
