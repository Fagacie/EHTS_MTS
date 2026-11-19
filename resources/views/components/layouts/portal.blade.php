@props([
    'title' => 'Trainee Portal',
    'breadcrumb' => 'Dashboard',
])

@php
    $user = auth()->user();
    $portalUnreadCount = $user?->unreadNotifications()->count() ?? 0;
    $userPortals = $user ? $user->availablePortals() : [];

    $initials = collect(preg_split('/\s+/', trim((string) ($user?->name ?? 'U'))) ?: [])
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    if ($initials === '') {
        $initials = 'U';
    }

    $navItems = [
        [
            'label' => 'Dashboard',
            'href' => route('portal.dashboard'),
            'active' => request()->routeIs('portal.dashboard'),
            'icon' => 'dashboard',
        ],
        [
            'label' => 'Browse Training',
            'href' => route('catalogue.index'),
            'active' => request()->routeIs('catalogue.*') || request()->routeIs('sessions.*'),
            'icon' => 'browse',
        ],
        [
            'label' => 'My Learning',
            'href' => route('portal.modules.index'),
            'active' => request()->routeIs('portal.modules.*') || request()->routeIs('portal.recordings.*'),
            'icon' => 'learning',
        ],
        [
            'label' => 'My Bookings',
            'href' => route('portal.bookings.index'),
            'active' => request()->routeIs('portal.bookings.*'),
            'icon' => 'bookings',
        ],
        [
            'label' => 'Certificates',
            'href' => route('portal.certificates.index'),
            'active' => request()->routeIs('portal.certificates.*'),
            'icon' => 'certificates',
        ],
        [
            'label' => 'Orders & Payments',
            'href' => route('portal.orders.index'),
            'active' => request()->routeIs('portal.orders.*'),
            'icon' => 'orders',
        ],
        [
            'label' => 'Notifications',
            'href' => route('portal.notifications.index'),
            'active' => request()->routeIs('portal.notifications.*'),
            'icon' => 'notifications',
            'badge' => $portalUnreadCount > 0 ? $portalUnreadCount : null,
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Trainee Portal | {{ config('etms.brand_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="portal-shell min-h-screen bg-portal-bg font-sans text-portal-text antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-portal-primary focus:px-4 focus:py-2.5 focus:text-sm focus:font-medium focus:text-white">
        Skip to main content
    </a>

    <!-- Mobile Drawer Backdrop -->
    <div
        id="portal-sidebar-backdrop"
        class="fixed inset-0 z-40 hidden bg-gray-950/40 lg:hidden"
        aria-hidden="true"
        onclick="window.etmsClosePortalDrawer()"
    ></div>

    <div class="min-h-screen lg:flex">
        <!-- Sidebar (Light admin-aligned rail + Mobile slide-over drawer) -->
        <aside
            id="portal-sidebar"
            data-collapsed="false"
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col overflow-hidden border-r border-portal-border bg-portal-surface text-gray-700 transition-[width,transform] duration-200 ease-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shrink-0"
            aria-label="Trainee Portal Navigation"
        >
            <!-- Brand Header -->
            <div class="portal-sidebar-header flex h-16 shrink-0 items-center justify-between px-5">
                <a href="{{ route('portal.dashboard') }}" title="Dashboard" class="rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary">
                    <x-portal.logo />
                </a>

                <x-portal.icon-button
                    id="portal-drawer-close"
                    label="Close Navigation"
                    class="lg:hidden"
                    onclick="window.etmsClosePortalDrawer(true)"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </x-portal.icon-button>
            </div>

            <!-- Main Navigation -->
            <nav class="portal-sidebar-nav flex-1 overflow-y-auto px-3 py-3 text-[15px]" aria-label="Main Navigation">
                <div class="space-y-1">
                    @foreach ($navItems as $item)
                        <a
                            href="{{ $item['href'] }}"
                            title="{{ $item['label'] }}"
                            @if ($item['active']) aria-current="page" @endif
                            class="portal-nav-item group flex items-center justify-between gap-2.5 rounded-lg px-3 py-2.5 transition duration-75 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary {{ $item['active'] ? 'bg-gray-100 font-semibold text-portal-primary' : 'font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                @switch($item['icon'])
                                    @case('dashboard')
                                        <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-portal-primary' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect width="7" height="9" x="3" y="3" rx="1" />
                                            <rect width="7" height="5" x="14" y="3" rx="1" />
                                            <rect width="7" height="9" x="14" y="12" rx="1" />
                                            <rect width="7" height="5" x="3" y="16" rx="1" />
                                        </svg>
                                        @break
                                    @case('browse')
                                        <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-portal-primary' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10" />
                                            <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" />
                                        </svg>
                                        @break
                                    @case('learning')
                                        <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-portal-primary' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                                        </svg>
                                        @break
                                    @case('bookings')
                                        <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-portal-primary' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M8 2v4" />
                                            <path d="M16 2v4" />
                                            <rect width="18" height="18" x="3" y="4" rx="2" />
                                            <path d="M3 10h18" />
                                            <path d="m9 16 2 2 4-4" />
                                        </svg>
                                        @break
                                    @case('certificates')
                                        <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-portal-primary' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="8" r="6" />
                                            <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                        </svg>
                                        @break
                                    @case('orders')
                                        <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-portal-primary' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect width="20" height="14" x="2" y="5" rx="2" />
                                            <line x1="2" x2="22" y1="10" y2="10" />
                                        </svg>
                                        @break
                                    @case('notifications')
                                        <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-portal-primary' : 'text-gray-400 group-hover:text-gray-600' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                                        </svg>
                                        @break
                                @endswitch

                                <span class="portal-sidebar-text truncate">{{ $item['label'] }}</span>
                            </span>

                            @if (! empty($item['badge']))
                                <span class="portal-sidebar-badge inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-portal-primary px-1.5 text-xs font-semibold text-white tabular-nums">
                                    {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </nav>

            <!-- Sign Out Footer -->
            <div class="portal-sidebar-footer mt-auto border-t border-portal-border p-3 text-[15px]">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        title="Sign Out"
                        class="portal-nav-item group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left font-medium text-gray-600 transition duration-75 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary"
                    >
                        <svg class="h-5 w-5 shrink-0 text-gray-400 group-hover:text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" x2="9" y1="12" y2="12" />
                        </svg>
                        <span class="portal-sidebar-text">Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Workspace Column -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Minimal Top Header -->
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-portal-border bg-portal-surface px-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-2.5">
                    <!-- Mobile Drawer Trigger -->
                    <x-portal.icon-button
                        id="portal-drawer-open"
                        label="Open Navigation"
                        aria-expanded="false"
                        aria-controls="portal-sidebar"
                        class="lg:hidden"
                        onclick="window.etmsOpenPortalDrawer()"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="4" x2="20" y1="12" y2="12" />
                            <line x1="4" x2="20" y1="6" y2="6" />
                            <line x1="4" x2="20" y1="18" y2="18" />
                        </svg>
                    </x-portal.icon-button>

                    <!-- Desktop Sidebar Collapse / Expand Toggle -->
                    <x-portal.icon-button
                        id="portal-sidebar-collapse"
                        label="Collapse Sidebar"
                        aria-expanded="true"
                        aria-controls="portal-sidebar"
                        class="hidden lg:inline-flex"
                        onclick="window.etmsToggleSidebarCollapse()"
                    >
                        <svg id="portal-icon-collapse" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M9 3v18" />
                            <path d="m16 15-3-3 3-3" />
                        </svg>
                        <svg id="portal-icon-expand" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M9 3v18" />
                            <path d="m14 9 3 3-3 3" />
                        </svg>
                    </x-portal.icon-button>

                    <nav class="flex min-w-0 items-center gap-1.5 text-sm" aria-label="Breadcrumb">
                        <a href="{{ route('portal.dashboard') }}" class="text-portal-muted transition-colors hover:text-portal-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary rounded">
                            Portal
                        </a>
                        <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                        <span class="truncate font-medium text-portal-text">{{ $breadcrumb }}</span>
                    </nav>
                </div>

                <div class="flex shrink-0 items-center gap-2.5">
                    @if (count($userPortals) > 1)
                        <div class="hidden md:block">
                            <x-portal.portal-switcher current="customer" />
                        </div>
                    @endif

                    <x-portal.icon-button
                        id="portal-header-notifications"
                        href="{{ route('portal.notifications.index') }}"
                        label="Notifications"
                        :badge="$portalUnreadCount"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                        </svg>
                    </x-portal.icon-button>

                    <!-- Account Menu (Profile & Security) -->
                    <div class="relative" id="portal-profile-container">
                        <button
                            id="portal-profile-trigger"
                            type="button"
                            aria-expanded="false"
                            aria-haspopup="menu"
                            aria-controls="portal-profile-menu"
                            onclick="window.etmsToggleProfileMenu()"
                            class="inline-flex h-10 items-center gap-2.5 rounded-lg px-2.5 text-left text-sm transition duration-100 hover:bg-portal-surface-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-portal-primary-soft text-xs font-semibold text-portal-primary">
                                {{ $initials }}
                            </span>
                            <span class="hidden max-w-36 truncate text-[14px] font-medium text-portal-text sm:inline">
                                {{ $user?->name }}
                            </span>
                            <svg class="h-4 w-4 text-portal-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div
                            id="portal-profile-menu"
                            role="menu"
                            aria-labelledby="portal-profile-trigger"
                            class="hidden absolute right-0 mt-1.5 w-64 rounded-xl bg-portal-surface py-1.5 shadow-lg ring-1 ring-gray-950/5"
                        >
                            <div class="border-b border-portal-border px-4 py-2.5">
                                <p class="truncate text-sm font-semibold text-portal-text">{{ $user?->name }}</p>
                                <p class="truncate text-[13px] text-portal-muted">{{ $user?->email }}</p>
                            </div>

                            @if (count($userPortals) > 1)
                                <div class="border-b border-portal-border py-1.5 md:hidden">
                                    <p class="px-4 py-1 text-xs font-medium text-portal-muted">Switch Portal</p>
                                    @foreach ($userPortals as $portal)
                                        <a href="{{ $portal['url'] }}" role="menuitem" class="block px-4 py-2 text-sm font-medium text-portal-text hover:bg-portal-bg focus-visible:bg-portal-bg focus-visible:outline-none">
                                            {{ $portal['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <div class="py-1.5">
                                <a href="{{ route('portal.profile.edit') }}" role="menuitem" class="block px-4 py-2 text-sm font-medium text-portal-text hover:bg-portal-bg focus-visible:bg-portal-bg focus-visible:outline-none {{ request()->routeIs('portal.profile.*') ? 'text-portal-primary font-semibold' : '' }}">
                                    Profile &amp; Billing
                                </a>
                                <a href="{{ route('portal.security.edit') }}" role="menuitem" class="block px-4 py-2 text-sm font-medium text-portal-text hover:bg-portal-bg focus-visible:bg-portal-bg focus-visible:outline-none {{ request()->routeIs('portal.security.*') ? 'text-portal-primary font-semibold' : '' }}">
                                    Password &amp; Security
                                </a>
                            </div>

                            <div class="border-t border-portal-border pt-1.5">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" role="menuitem" class="block w-full px-4 py-2 text-left text-sm font-medium text-rose-700 hover:bg-rose-50 focus-visible:bg-rose-50 focus-visible:outline-none">
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Workspace -->
            <main id="main-content" class="flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                @if (count($userPortals) > 1)
                    <div class="mb-5 md:hidden">
                        <x-portal.portal-switcher current="customer" />
                    </div>
                @endif

                @if (session('status'))
                    <x-portal.alert variant="success" role="status" class="mb-6">
                        {{ session('status') }}
                    </x-portal.alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts

    <script>
        (function () {
            const STORAGE_KEY = 'etms_portal_sidebar_collapsed';
            const sidebar = document.getElementById('portal-sidebar');
            const backdrop = document.getElementById('portal-sidebar-backdrop');
            const drawerOpenBtn = document.getElementById('portal-drawer-open');
            const drawerCloseBtn = document.getElementById('portal-drawer-close');
            const collapseBtn = document.getElementById('portal-sidebar-collapse');
            const iconCollapse = document.getElementById('portal-icon-collapse');
            const iconExpand = document.getElementById('portal-icon-expand');
            const profileContainer = document.getElementById('portal-profile-container');
            const profileTrigger = document.getElementById('portal-profile-trigger');
            const profileMenu = document.getElementById('portal-profile-menu');

            function applySidebarCollapse(collapsed) {
                if (!sidebar) return;
                sidebar.setAttribute('data-collapsed', collapsed ? 'true' : 'false');
                if (collapseBtn) {
                    collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    collapseBtn.setAttribute('aria-label', collapsed ? 'Expand Sidebar' : 'Collapse Sidebar');
                    collapseBtn.setAttribute('title', collapsed ? 'Expand Sidebar' : 'Collapse Sidebar');
                }
                if (iconCollapse && iconExpand) {
                    iconCollapse.classList.toggle('hidden', collapsed);
                    iconExpand.classList.toggle('hidden', !collapsed);
                }
            }

            try {
                applySidebarCollapse(window.localStorage.getItem(STORAGE_KEY) === '1');
            } catch (_) {
                applySidebarCollapse(false);
            }

            window.etmsToggleSidebarCollapse = function () {
                if (!sidebar) return;
                const next = sidebar.getAttribute('data-collapsed') !== 'true';
                applySidebarCollapse(next);
                try {
                    window.localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
                } catch (_) {}
            };

            window.etmsOpenPortalDrawer = function () {
                if (!sidebar || !backdrop) return;
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                if (drawerOpenBtn) drawerOpenBtn.setAttribute('aria-expanded', 'true');
                if (drawerCloseBtn) drawerCloseBtn.focus();
            };

            window.etmsClosePortalDrawer = function (restoreFocus) {
                if (!sidebar || !backdrop) return;
                const wasOpen = !sidebar.classList.contains('-translate-x-full');
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                if (drawerOpenBtn) {
                    drawerOpenBtn.setAttribute('aria-expanded', 'false');
                    if (restoreFocus && wasOpen) {
                        drawerOpenBtn.focus();
                    }
                }
            };

            window.etmsToggleProfileMenu = function () {
                if (!profileMenu || !profileTrigger) return;
                const willOpen = profileMenu.classList.contains('hidden');
                profileMenu.classList.toggle('hidden', !willOpen);
                profileTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            };

            if (sidebar) {
                sidebar.querySelectorAll('a[href]').forEach(function (link) {
                    link.addEventListener('click', function () {
                        if (window.innerWidth < 1024) {
                            window.etmsClosePortalDrawer(false);
                        }
                    });
                });
            }

            document.addEventListener('click', function (event) {
                if (profileContainer && profileMenu && !profileContainer.contains(event.target)) {
                    profileMenu.classList.add('hidden');
                    if (profileTrigger) profileTrigger.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    window.etmsClosePortalDrawer(true);
                    if (profileMenu && !profileMenu.classList.contains('hidden')) {
                        profileMenu.classList.add('hidden');
                        if (profileTrigger) {
                            profileTrigger.setAttribute('aria-expanded', 'false');
                            profileTrigger.focus();
                        }
                    }
                }
            });
        })();
    </script>
</body>
</html>
