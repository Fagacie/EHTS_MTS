@props([
    'title' => 'Ecohydrotech Training Management System',
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('etms.brand_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#FAF9FB] font-sans text-[#1C1921] antialiased flex flex-col">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-md focus:bg-[#5B377B] focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Skip to main content
    </a>

    <header class="sticky top-0 z-40 border-b border-[#E6E2EC] bg-white">
        <div class="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-10">
                <a href="{{ route('home') }}" class="shrink-0 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#5B377B]">
                    <x-eht.logo />
                </a>

                <nav class="hidden lg:flex items-center gap-7 text-sm" aria-label="Public Navigation">
                    <a
                        href="{{ route('catalogue.index') }}"
                        class="py-1.5 transition-colors duration-150 {{ request()->routeIs('catalogue.*') ? 'font-semibold text-[#5B377B]' : 'font-medium text-[#4A4554] hover:text-[#5B377B]' }}"
                    >
                        Training Catalogue
                    </a>
                    <a
                        href="{{ route('sessions.index') }}"
                        class="py-1.5 transition-colors duration-150 {{ request()->routeIs('sessions.*') ? 'font-semibold text-[#5B377B]' : 'font-medium text-[#4A4554] hover:text-[#5B377B]' }}"
                    >
                        Physical Sessions
                    </a>
                    <a
                        href="{{ route('instructors.index') }}"
                        class="py-1.5 transition-colors duration-150 {{ request()->routeIs('instructors.*') ? 'font-semibold text-[#5B377B]' : 'font-medium text-[#4A4554] hover:text-[#5B377B]' }}"
                    >
                        Instructors
                    </a>
                    <a
                        href="{{ route('home') }}#verify-preview"
                        class="py-1.5 font-medium text-[#4A4554] transition-colors duration-150 hover:text-[#5B377B]"
                    >
                        Verify Certificate
                    </a>
                </nav>
            </div>

            <div class="hidden lg:flex shrink-0 items-center gap-3">
                @auth
                    <x-eht.portal-switcher />
                    <a
                        href="{{ auth()->user()->defaultDashboardUrl() }}"
                        class="inline-flex items-center justify-center rounded-md bg-[#5B377B] px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-[#492A64] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#5B377B] focus-visible:ring-offset-2"
                    >
                        My Portal
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-md border border-[#D4CFD9] bg-white px-3.5 py-2 text-sm font-medium text-[#34303B] transition-colors duration-150 hover:border-[#ABA3B5] hover:bg-[#FAF9FB] hover:text-[#1C1921] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#5B377B] focus-visible:ring-offset-2"
                        >
                            Sign Out
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="px-3 py-2 text-sm font-medium text-[#34303B] transition-colors duration-150 hover:text-[#5B377B]"
                    >
                        Log In
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex items-center justify-center rounded-md bg-[#5B377B] px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-[#492A64] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#5B377B] focus-visible:ring-offset-2"
                    >
                        Create Account
                    </a>
                @endauth
            </div>

            <button
                id="public-mobile-toggle"
                type="button"
                aria-expanded="false"
                aria-controls="public-mobile-nav"
                aria-label="Toggle navigation menu"
                class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-[#D4CFD9] bg-white text-[#34303B] transition-colors duration-150 hover:bg-[#F4F1F8] lg:hidden"
            >
                <svg id="public-mobile-icon-open" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <svg id="public-mobile-icon-close" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="public-mobile-nav" class="hidden border-t border-[#E6E2EC] bg-white px-4 py-4 shadow-sm lg:hidden">
            <nav class="space-y-1 text-sm" aria-label="Mobile Navigation">
                <a href="{{ route('catalogue.index') }}" class="block rounded-md px-3 py-2.5 {{ request()->routeIs('catalogue.*') ? 'bg-[#F4F1F8] font-semibold text-[#5B377B]' : 'font-medium text-[#34303B] hover:bg-[#F7F5FA]' }}">Training Catalogue</a>
                <a href="{{ route('sessions.index') }}" class="block rounded-md px-3 py-2.5 {{ request()->routeIs('sessions.*') ? 'bg-[#F4F1F8] font-semibold text-[#5B377B]' : 'font-medium text-[#34303B] hover:bg-[#F7F5FA]' }}">Physical Sessions</a>
                <a href="{{ route('instructors.index') }}" class="block rounded-md px-3 py-2.5 {{ request()->routeIs('instructors.*') ? 'bg-[#F4F1F8] font-semibold text-[#5B377B]' : 'font-medium text-[#34303B] hover:bg-[#F7F5FA]' }}">Instructors</a>
                <a href="{{ route('home') }}#verify-preview" class="block rounded-md px-3 py-2.5 font-medium text-[#34303B] hover:bg-[#F7F5FA]">Verify Certificate</a>
            </nav>
            <div class="mt-4 flex flex-col gap-2.5 border-t border-[#E6E2EC] pt-4">
                @auth
                    <x-eht.portal-switcher class="mb-1 w-full justify-center" />
                    <a href="{{ auth()->user()->defaultDashboardUrl() }}" class="inline-flex w-full items-center justify-center rounded-md bg-[#5B377B] px-4 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-[#492A64]">
                        My Portal
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md border border-[#D4CFD9] bg-white px-4 py-2.5 text-sm font-medium text-[#34303B] transition-colors duration-150 hover:bg-[#FAF9FB]">
                            Sign Out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-md border border-[#D4CFD9] bg-white px-4 py-2.5 text-sm font-medium text-[#34303B] transition-colors duration-150 hover:bg-[#FAF9FB]">
                        Log In
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-md bg-[#5B377B] px-4 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-[#492A64]">
                        Create Account
                    </a>
                @endauth
            </div>
        </div>

        <script>
            (() => {
                const btn = document.getElementById('public-mobile-toggle');
                const menu = document.getElementById('public-mobile-nav');
                const openIcon = document.getElementById('public-mobile-icon-open');
                const closeIcon = document.getElementById('public-mobile-icon-close');
                if (!btn || !menu) return;

                const setOpen = (isOpen) => {
                    menu.classList.toggle('hidden', !isOpen);
                    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    openIcon?.classList.toggle('hidden', isOpen);
                    closeIcon?.classList.toggle('hidden', !isOpen);
                };

                btn.addEventListener('click', () => {
                    setOpen(menu.classList.contains('hidden'));
                });

                menu.querySelectorAll('a').forEach((link) => {
                    link.addEventListener('click', () => setOpen(false));
                });

                window.addEventListener('resize', () => {
                    if (window.innerWidth >= 1024) setOpen(false);
                });
            })();
        </script>
    </header>

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-[#18151C] text-[#CBC7D1]">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div class="sm:col-span-2">
                <x-eht.logo :dark="true" />
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-[#A39DAE]">
                    Specialised technical training in hydrology, environmental science, and engineering for professionals and individuals in Malaysia.
                </p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white">Training</h3>
                <ul class="mt-3.5 space-y-2.5 text-sm text-[#A39DAE]">
                    <li><a href="{{ route('catalogue.index') }}" class="transition hover:text-white">Training Catalogue</a></li>
                    <li><a href="{{ route('sessions.index') }}" class="transition hover:text-white">Physical Sessions</a></li>
                    <li><a href="{{ route('instructors.index') }}" class="transition hover:text-white">Instructors</a></li>
                    <li><a href="{{ route('home') }}#verify-preview" class="transition hover:text-white">Verify Certificate</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-white">Account</h3>
                <ul class="mt-3.5 space-y-2.5 text-sm text-[#A39DAE]">
                    @auth
                        <li><a href="{{ auth()->user()->defaultDashboardUrl() }}" class="transition hover:text-white">My Portal</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="transition hover:text-white">Log In</a></li>
                        <li><a href="{{ route('register') }}" class="transition hover:text-white">Create Account</a></li>
                    @endauth
                    <li><a href="{{ route('portal.dashboard') }}" class="transition hover:text-white">Customer Portal</a></li>
                    <li><a href="{{ route('instructor.dashboard') }}" class="transition hover:text-white">Instructor Portal</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-[#918B9C] sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>&copy; {{ now()->year }} {{ config('etms.brand_name') }}. All rights reserved.</p>
                <p>Session times are in Malaysia Time (MYT). Fees are in Malaysian Ringgit (RM).</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
