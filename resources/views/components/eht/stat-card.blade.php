@props([
    'label',
    'value',
    'meta' => null,
    'accent' => 'navy',
])

@php
    $accentBar = match ($accent) {
        'teal' => 'border-t-eht-teal-600',
        'emerald' => 'border-t-eht-emerald-600',
        'amber' => 'border-t-amber-500',
        default => 'border-t-eht-navy-700',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-xl border border-slate-200 border-t-4 {$accentBar} bg-white p-5 shadow-xs"]) }}>
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 tabular-nums">{{ $value }}</p>
    @if ($meta)
        <p class="mt-1.5 text-xs text-slate-600">{{ $meta }}</p>
    @endif
</div>
