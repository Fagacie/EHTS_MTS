@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-eht-teal-600 focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-eht-navy-700 text-white hover:bg-eht-navy-800 shadow-xs',
        'teal' => 'bg-eht-teal-600 text-white hover:bg-eht-teal-700 shadow-xs',
        'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-eht-navy-700',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 shadow-xs',
    ];

    $sizes = [
        'sm' => 'px-3 py-2 text-xs min-h-9',
        'md' => 'px-4 py-2.5 text-sm min-h-10',
        'lg' => 'px-5 py-3 text-sm min-h-11',
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
