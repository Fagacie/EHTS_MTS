@props([
    'current' => null,
])

@php
    $user = auth()->user();
    $portals = $user ? $user->availablePortals() : [];
@endphp

@if (count($portals) > 1)
    <div {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-0.5 rounded-lg bg-portal-surface-muted p-1 ring-1 ring-inset ring-portal-border']) }} role="navigation" aria-label="Portal Switcher">
        @foreach ($portals as $portal)
            @php
                $isActive = $current === $portal['slug'] || request()->routeIs($portal['route_pattern']);
            @endphp
            <a
                href="{{ $portal['url'] }}"
                class="rounded-md px-3 py-1.5 text-[13px] leading-none transition duration-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary {{ $isActive ? 'bg-portal-surface font-semibold text-portal-primary shadow-xs' : 'font-medium text-portal-muted hover:text-portal-text' }}"
            >
                {{ $portal['label'] }}
            </a>
        @endforeach
    </div>
@endif
