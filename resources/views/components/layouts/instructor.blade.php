@props([
    'title' => 'Instructor Portal',
    'breadcrumb' => 'Dashboard',
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Instructor Portal | {{ config('etms.brand_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-eht-navy-900 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Skip to main content
    </a>

    <div class="min-h-screen lg:flex">
        <aside id="instructor-sidebar" class="fixed inset-y-0 left-0 z-40 hidden w-60 shrink-0 flex-col bg-eht-navy-950 text-slate-200 lg:static lg:flex">
            <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
                <a href="{{ route('instructor.dashboard') }}">
                    <x-eht.logo :dark="true" subtitle="Instructor Portal" />
                </a>
                <button
                    type="button"
                    onclick="document.getElementById('instructor-sidebar').classList.add('hidden')"
                    class="rounded-lg p-1.5 text-slate-300 hover:bg-white/10 lg:hidden"
                    aria-label="Close Sidebar"
                >
                    &times;
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5 text-sm" aria-label="Instructor Portal Sidebar">
                <div>
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Operations</p>
                    @php
                        $instructorUnreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
                    @endphp
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('instructor.dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('instructor.dashboard') ? 'bg-eht-teal-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('instructor.notifications.index') }}" class="flex items-center justify-between gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('instructor.notifications.*') ? 'bg-eht-teal-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                            <span>Notifications</span>
                            @if ($instructorUnreadCount > 0)
                                <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-400 px-1.5 py-0.5 text-[10px] font-bold text-slate-950">
                                    {{ $instructorUnreadCount }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('instructor.sessions.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('instructor.sessions.*') ? 'bg-eht-teal-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                            Assigned Sessions
                        </a>
                        <a href="{{ route('instructor.profile.edit') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 font-medium transition {{ request()->routeIs('instructor.profile.*') ? 'bg-eht-teal-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                            My Instructor Profile
                        </a>
                    </div>
                </div>
            </nav>

            <div class="border-t border-white/10 p-4 text-xs">
                <a href="{{ route('home') }}" class="block rounded-lg border border-white/15 px-3 py-2 text-center font-medium text-slate-200 hover:bg-white/10 transition">
                    Browse Public Site
                </a>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        onclick="document.getElementById('instructor-sidebar').classList.toggle('hidden'); document.getElementById('instructor-sidebar').classList.toggle('flex');"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 lg:hidden"
                        aria-label="Open Navigation"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <nav class="text-xs sm:text-sm text-slate-500" aria-label="Breadcrumb">
                        <span>Instructor</span>
                        <span class="mx-1.5 text-slate-300">/</span>
                        <span class="font-semibold text-slate-900">{{ $breadcrumb }}</span>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden md:block">
                        <x-eht.portal-switcher current="instructor" />
                    </div>
                    <div class="hidden sm:block text-right">
                        <p class="text-xs font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-eht.button type="submit" size="sm" variant="outline">
                            Sign Out
                        </x-eht.button>
                    </form>
                </div>
            </header>

            <main id="main-content" class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @if (count(auth()->user()->availablePortals()) > 1)
                    <div class="mb-4 md:hidden">
                        <x-eht.portal-switcher current="instructor" />
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-status-success-border bg-status-success-bg px-4 py-3 text-sm font-medium text-status-success-text" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
