@props([
    'href' => null,
    'type' => 'button',
    'label' => 'Action',
    'variant' => 'ghost',
    'badge' => null,
])

@php
    $base = 'relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition duration-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary focus-visible:ring-offset-1';

    $variants = [
        'ghost' => 'text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text',
        'default' => 'bg-portal-surface text-portal-muted ring-1 ring-inset ring-gray-300 hover:bg-portal-bg hover:text-portal-text',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['ghost']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes, 'aria-label' => $label, 'title' => $label]) }}>
        {{ $slot }}
        @if ($badge && (int) $badge > 0)
            <span class="absolute top-0.5 right-0.5 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-portal-primary px-1 text-xs font-semibold leading-none text-white tabular-nums">
                {{ (int) $badge > 99 ? '99+' : $badge }}
            </span>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes, 'aria-label' => $label, 'title' => $label]) }}>
        {{ $slot }}
        @if ($badge && (int) $badge > 0)
            <span class="absolute top-0.5 right-0.5 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-portal-primary px-1 text-xs font-semibold leading-none text-white tabular-nums">
                {{ (int) $badge > 99 ? '99+' : $badge }}
            </span>
        @endif
    </button>
@endif
