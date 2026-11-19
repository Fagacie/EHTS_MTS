@props([
    'current' => null,
])

@php
    $user = auth()->user();
    $portals = $user ? $user->availablePortals() : [];
@endphp

@if (count($portals) > 1)
    <div {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 p-1']) }} role="navigation" aria-label="Portal Switcher">
        @foreach ($portals as $portal)
            @php
                $isActive = $current === $portal['slug'] || request()->routeIs($portal['route_pattern']);
            @endphp
            <a
                href="{{ $portal['url'] }}"
                class="rounded-md px-2.5 py-1.5 text-xs font-semibold transition {{ $isActive ? 'bg-eht-navy-700 text-white shadow-xs' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}"
            >
                {{ $portal['label'] }}
            </a>
        @endforeach
    </div>
@endif
