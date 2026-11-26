<script setup lang="ts">
import { computed, ref } from 'vue';
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
    ArrowRight,
    ArrowUp,
    ArrowUpRight,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    Compass,
    LogOut,
    Menu,
    User,
    Users,
    X,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import type { EtmsSharedProps } from '@/types/etms';

const page = usePage<EtmsSharedProps>();
const app = computed(() => page.props.app);
const authUser = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);
const routes = computed(() => page.props.routes);
const currentPath = computed(() => page.props.currentPath);

const mobileOpen = ref(false);

const navItems = computed(() => [
    { label: 'Home', href: routes.value.home, icon: Compass, match: (p: string) => p === '/' },
    { label: 'Modules', href: routes.value.catalogue, icon: BookOpen, match: (p: string) => p.startsWith('/training') },
    { label: 'Training Sessions', href: routes.value.sessions, icon: CalendarDays, match: (p: string) => p.startsWith('/sessions') },
    { label: 'Instructors', href: routes.value.instructors, icon: Users, match: (p: string) => p.startsWith('/instructors') },
]);

const userInitials = computed(() => {
    const name = authUser.value?.name?.trim() ?? '';
    if (!name) return 'U';
    const parts = name.split(/\s+/);
    return (parts[0]?.[0] ?? '') + (parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '');
});

function isFilamentUrl(url: string): boolean {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.pathname === '/admin' || parsed.pathname.startsWith('/admin/');
    } catch {
        return url === '/admin' || url.startsWith('/admin/');
    }
}

function logout(): void {
    mobileOpen.value = false;
    router.post(routes.value.logout);
}

function scrollToTop(): void {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-ink">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-xl focus:bg-navy-950 focus:px-4 focus:py-2 text-sm font-semibold text-white focus:outline-none"
        >
            Skip to main content
        </a>

        <!-- Modern Sticky Translucent Top Navigation -->
        <header class="sticky top-0 z-40 border-b border-line/80 bg-white/92 backdrop-blur-md">
            <div class="mx-auto flex h-[72px] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <!-- Brand -->
                <Link
                    :href="routes.home"
                    class="flex items-center rounded-xl py-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <img
                        :src="app.logoUrl"
                        :alt="app.brandName"
                        class="h-11 w-auto object-contain sm:h-12"
                    />
                    <span class="sr-only">{{ app.brandName }}</span>
                </Link>

                <!-- Centered Pill Navigation Rail -->
                <nav
                    aria-label="Primary"
                    class="hidden items-center gap-1 rounded-full bg-sand-50 p-1 ring-1 ring-line/90 md:flex"
                >
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        :aria-current="item.match(currentPath) ? 'page' : undefined"
                        class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-[13.5px] transition-all duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                        :class="
                            item.match(currentPath)
                                ? 'bg-navy-950 font-semibold text-white shadow-xs'
                                : 'font-medium text-ink/75 hover:bg-white hover:text-navy-950'
                        "
                    >
                        <component
                            :is="item.icon"
                            class="size-3.5"
                            :class="item.match(currentPath) ? 'text-accent-100' : 'text-muted'"
                            aria-hidden="true"
                        />
                        <span>{{ item.label }}</span>
                    </Link>
                </nav>

                <!-- Right Actions -->
                <div class="hidden items-center gap-2 md:flex">
                    <template v-if="authUser">
                        <DropdownMenuRoot>
                            <DropdownMenuTrigger
                                class="inline-flex h-10 cursor-pointer items-center gap-2.5 rounded-full border border-line bg-white py-1 pr-3.5 pl-1.5 text-sm font-medium text-navy-950 shadow-xs transition-all duration-150 hover:border-navy-700/30 hover:bg-sand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                            >
                                <span class="relative shrink-0">
                                    <img
                                        v-if="authUser.photoUrl"
                                        :src="authUser.photoUrl"
                                        :alt="authUser.name"
                                        class="size-7 rounded-full object-cover ring-1 ring-line"
                                    />
                                    <span
                                        v-else
                                        class="flex size-7 items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-white uppercase"
                                    >
                                        {{ userInitials }}
                                    </span>
                                    <span
                                        class="absolute -right-0.5 -bottom-0.5 size-2.5 rounded-full bg-accent-600 ring-2 ring-white"
                                        aria-hidden="true"
                                    />
                                </span>
                                <span class="max-w-32 truncate text-[13.5px] font-semibold">{{ authUser.name }}</span>
                                <ChevronDown class="size-4 text-muted" aria-hidden="true" />
                            </DropdownMenuTrigger>

                            <DropdownMenuPortal>
                                <DropdownMenuContent
                                    align="end"
                                    :side-offset="8"
                                    class="z-50 w-64 rounded-2xl border border-line bg-white p-1.5 shadow-popover"
                                >
                                    <div class="px-3 py-2">
                                        <p class="truncate text-sm font-semibold text-navy-950">{{ authUser.name }}</p>
                                        <p class="truncate text-xs text-muted">{{ authUser.email }}</p>
                                    </div>

                                    <DropdownMenuSeparator class="my-1 h-px bg-line" />

                                    <template v-for="portal in authUser.availablePortals" :key="portal.slug">
                                        <DropdownMenuItem as-child>
                                            <a
                                                v-if="isFilamentUrl(portal.url)"
                                                :href="portal.url"
                                                class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm text-ink transition-colors hover:bg-sand-50 focus:bg-sand-50 focus:outline-none"
                                            >
                                                <span>
                                                    <span class="block font-semibold text-navy-950">{{ portal.label }}</span>
                                                    <span class="block text-xs text-muted">{{ portal.description }}</span>
                                                </span>
                                                <ArrowUpRight class="size-4 shrink-0 text-muted" aria-hidden="true" />
                                            </a>
                                            <Link
                                                v-else
                                                :href="portal.url"
                                                class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm text-ink transition-colors hover:bg-sand-50 focus:bg-sand-50 focus:outline-none"
                                            >
                                                <span>
                                                    <span class="block font-semibold text-navy-950">{{ portal.label }}</span>
                                                    <span class="block text-xs text-muted">{{ portal.description }}</span>
                                                </span>
                                                <ArrowUpRight class="size-4 shrink-0 text-muted" aria-hidden="true" />
                                            </Link>
                                        </DropdownMenuItem>
                                    </template>

                                    <DropdownMenuSeparator class="my-1 h-px bg-line" />

                                    <DropdownMenuItem
                                        class="flex w-full cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-danger-700 transition-colors hover:bg-danger-50 focus:bg-danger-50 focus:outline-none"
                                        @select="logout"
                                    >
                                        <LogOut class="size-4" aria-hidden="true" />
                                        <span>Sign out</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenuPortal>
                        </DropdownMenuRoot>
                    </template>

                    <template v-else>
                        <Link
                            :href="routes.login"
                            class="rounded-full px-3.5 py-2 text-[13.5px] font-semibold text-navy-900 transition-colors duration-150 hover:bg-sand-50 hover:text-primary-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                        >
                            Sign in
                        </Link>
                        <AppButton :href="routes.register" variant="warm" size="sm" pill>
                            <span>Register</span>
                            <ArrowRight class="size-3.5" aria-hidden="true" />
                        </AppButton>
                    </template>
                </div>

                <!-- Mobile Drawer Trigger -->
                <div class="flex items-center md:hidden">
                    <DialogRoot v-model:open="mobileOpen">
                        <DialogTrigger as-child>
                            <button
                                type="button"
                                aria-label="Open navigation menu"
                                class="inline-flex size-10 items-center justify-center rounded-full border border-line bg-sand-50 text-navy-950 transition-colors hover:bg-sand-100"
                            >
                                <Menu class="size-5" aria-hidden="true" />
                            </button>
                        </DialogTrigger>

                        <DialogPortal>
                            <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                            <DialogContent
                                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xs flex-col bg-white p-5 shadow-floating focus:outline-none"
                            >
                                <div class="flex items-center justify-between border-b border-line pb-4">
                                    <DialogTitle class="flex items-center">
                                        <img
                                            :src="app.logoUrl"
                                            :alt="app.brandName"
                                            class="h-9 w-auto object-contain"
                                        />
                                        <span class="sr-only">{{ app.brandName }}</span>
                                    </DialogTitle>
                                    <DialogClose
                                        aria-label="Close navigation menu"
                                        class="inline-flex size-9 items-center justify-center rounded-full text-muted hover:bg-sand-100 hover:text-navy-950"
                                    >
                                        <X class="size-5" aria-hidden="true" />
                                    </DialogClose>
                                </div>

                                <nav aria-label="Mobile navigation" class="mt-4 flex flex-col gap-1">
                                    <Link
                                        v-for="item in navItems"
                                        :key="item.href"
                                        :href="item.href"
                                        class="flex items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm transition-colors"
                                        :class="
                                            item.match(currentPath)
                                                ? 'bg-navy-950 font-semibold text-white'
                                                : 'font-medium text-ink hover:bg-sand-50'
                                        "
                                        @click="mobileOpen = false"
                                    >
                                        <component
                                            :is="item.icon"
                                            class="size-4"
                                            :class="item.match(currentPath) ? 'text-accent-100' : 'text-muted'"
                                            aria-hidden="true"
                                        />
                                        <span>{{ item.label }}</span>
                                    </Link>
                                </nav>

                                <div class="mt-auto border-t border-line pt-4">
                                    <template v-if="authUser">
                                        <div class="mb-3 flex items-center gap-2.5 px-1">
                                            <span class="flex size-9 items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-white uppercase">
                                                {{ userInitials }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-navy-950">{{ authUser.name }}</p>
                                                <p class="truncate text-xs text-muted">{{ authUser.email }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <AppButton
                                                v-for="portal in authUser.availablePortals"
                                                :key="portal.slug"
                                                :href="portal.url"
                                                :external="isFilamentUrl(portal.url)"
                                                variant="outline"
                                                size="sm"
                                                class="w-full justify-between"
                                                @click="mobileOpen = false"
                                            >
                                                <span class="inline-flex items-center gap-2">
                                                    <User class="size-4 text-primary-600" aria-hidden="true" />
                                                    {{ portal.label }}
                                                </span>
                                                <ArrowUpRight class="size-4 text-muted" aria-hidden="true" />
                                            </AppButton>
                                            <AppButton
                                                variant="ghost"
                                                size="sm"
                                                class="w-full text-danger-700 hover:bg-danger-50"
                                                @click="logout"
                                            >
                                                <LogOut class="size-4" aria-hidden="true" />
                                                Sign out
                                            </AppButton>
                                        </div>
                                    </template>
                                    <div v-else class="flex flex-col gap-2">
                                        <AppButton
                                            :href="routes.register"
                                            variant="warm"
                                            size="md"
                                            class="w-full"
                                            @click="mobileOpen = false"
                                        >
                                            Register
                                        </AppButton>
                                        <AppButton
                                            :href="routes.login"
                                            variant="outline"
                                            size="md"
                                            class="w-full"
                                            @click="mobileOpen = false"
                                        >
                                            Sign in
                                        </AppButton>
                                    </div>
                                </div>
                            </DialogContent>
                        </DialogPortal>
                    </DialogRoot>
                </div>
            </div>
        </header>

        <!-- Flash Notifications -->
        <div
            v-if="flash.status || flash.error"
            class="border-b border-line bg-sand-50/80 py-3"
        >
            <div class="mx-auto max-w-7xl space-y-2 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flash.status"
                    role="status"
                    class="flex items-center gap-2.5 rounded-xl bg-success-50 px-4 py-2.5 text-sm font-medium text-success-700 ring-1 ring-inset ring-success-600/20"
                >
                    <CheckCircle2 class="size-4 shrink-0" aria-hidden="true" />
                    <span>{{ flash.status }}</span>
                </div>
                <div
                    v-if="flash.error"
                    role="alert"
                    class="flex items-center gap-2.5 rounded-xl bg-danger-50 px-4 py-2.5 text-sm font-medium text-danger-700 ring-1 ring-inset ring-danger-600/20"
                >
                    <AlertCircle class="size-4 shrink-0" aria-hidden="true" />
                    <span>{{ flash.error }}</span>
                </div>
            </div>
        </div>

        <!-- Main Pure White Editorial Canvas -->
        <main id="main-content" class="flex-1 bg-white">
            <slot />
        </main>

        <!-- Modern Architectural Deep Navy Footer -->
        <footer class="mt-20 bg-navy-950 text-white sm:mt-24">
            <div class="h-1 w-full bg-accent-600" aria-hidden="true" />

            <div class="mx-auto max-w-7xl px-4 pt-12 pb-10 sm:px-6 lg:px-8">
                <!-- Top Action Strip -->
                <div class="flex flex-col justify-between gap-6 border-b border-white/10 pb-10 md:flex-row md:items-center">
                    <div class="flex items-center gap-3.5">
                        <div class="inline-flex items-center rounded-xl bg-white px-3 py-1.5 shadow-xs">
                            <img
                                :src="app.logoUrl"
                                :alt="app.brandName"
                                class="h-10 w-auto object-contain"
                            />
                        </div>
                        <p class="hidden text-xs text-white/65 sm:block">
                            Hydrology, Water Resources &amp; Environmental Training
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <AppButton :href="routes.sessions" variant="warm" size="sm" pill>
                            <span>View Live Schedule</span>
                            <ArrowRight class="size-3.5" aria-hidden="true" />
                        </AppButton>
                        <AppButton
                            :href="routes.catalogue"
                            variant="outline"
                            size="sm"
                            pill
                            class="!border-white/20 !bg-white/5 !text-white hover:!bg-white/12"
                        >
                            <span>Browse Modules</span>
                        </AppButton>
                    </div>
                </div>

                <!-- 4-Column Sitemap -->
                <div class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <p class="max-w-sm text-sm leading-relaxed text-white/70">
                            Practical field workshops, technical seminars, and self-paced engineering modules with verified attendance and digital certification.
                        </p>
                    </div>

                    <div class="lg:col-span-2">
                        <h2 class="text-xs font-semibold tracking-[0.08em] text-white/45 uppercase">
                            Training
                        </h2>
                        <ul class="mt-3.5 space-y-2.5 text-sm">
                            <li>
                                <Link :href="routes.sessions" class="text-white/80 transition-colors hover:text-white">
                                    Training Sessions
                                </Link>
                            </li>
                            <li>
                                <Link :href="routes.catalogue" class="text-white/80 transition-colors hover:text-white">
                                    Self-Paced Modules
                                </Link>
                            </li>
                            <li>
                                <Link :href="routes.instructors" class="text-white/80 transition-colors hover:text-white">
                                    Instructors
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div class="lg:col-span-3">
                        <h2 class="text-xs font-semibold tracking-[0.08em] text-white/45 uppercase">
                            Account
                        </h2>
                        <ul class="mt-3.5 space-y-2.5 text-sm">
                            <template v-if="authUser">
                                <li v-for="portal in authUser.availablePortals" :key="portal.slug">
                                    <a
                                        v-if="isFilamentUrl(portal.url)"
                                        :href="portal.url"
                                        class="inline-flex items-center gap-1 text-white/80 transition-colors hover:text-white"
                                    >
                                        <span>{{ portal.label }}</span>
                                        <ArrowUpRight class="size-3.5 text-accent-100" aria-hidden="true" />
                                    </a>
                                    <Link
                                        v-else
                                        :href="portal.url"
                                        class="inline-flex items-center gap-1 text-white/80 transition-colors hover:text-white"
                                    >
                                        <span>{{ portal.label }}</span>
                                        <ArrowUpRight class="size-3.5 text-accent-100" aria-hidden="true" />
                                    </Link>
                                </li>
                            </template>
                            <template v-else>
                                <li>
                                    <Link :href="routes.login" class="text-white/80 transition-colors hover:text-white">
                                        Sign in to Portal
                                    </Link>
                                </li>
                                <li>
                                    <Link :href="routes.register" class="text-white/80 transition-colors hover:text-white">
                                        Create Learner Account
                                    </Link>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="lg:col-span-3">
                        <h2 class="text-xs font-semibold tracking-[0.08em] text-white/45 uppercase">
                            Included in Portal
                        </h2>
                        <ul class="mt-3.5 space-y-2 text-xs leading-relaxed text-white/65">
                            <li>Real-time seat inventory &amp; checkout holds</li>
                            <li>Downloadable manuals &amp; protected recordings</li>
                            <li>Attendance verification &amp; serialized PDF certificates</li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="mt-12 flex flex-col items-start justify-between gap-4 border-t border-white/10 pt-6 text-xs text-white/50 sm:flex-row sm:items-center">
                    <p>&copy; {{ app.year }} {{ app.brandName }}. All rights reserved.</p>
                    <div class="flex items-center gap-4">
                        <span>{{ app.shortName }}</span>
                        <button
                            type="button"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-full bg-white/8 px-3 py-1.5 text-xs font-medium text-white/80 transition-colors hover:bg-white/15 hover:text-white"
                            @click="scrollToTop"
                        >
                            <span>Top</span>
                            <ArrowUp class="size-3.5" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
