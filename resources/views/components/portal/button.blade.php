@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition duration-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-55';

    $variants = [
        'primary' => 'bg-portal-primary text-white shadow-xs hover:bg-portal-primary-hover',
        'secondary' => 'bg-portal-surface text-portal-text shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-portal-bg',
        'ghost' => 'text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text',
        'danger' => 'bg-portal-surface text-rose-700 shadow-xs ring-1 ring-inset ring-rose-300 hover:bg-rose-50',
    ];

    $sizes = [
        'sm' => 'min-h-9 px-3.5 py-2 text-sm',
        'md' => 'min-h-[42px] px-4 py-2.5 text-[15px]',
        'lg' => 'min-h-11 px-5 py-2.5 text-[15px]',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
