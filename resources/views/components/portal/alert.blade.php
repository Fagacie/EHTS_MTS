@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $styles = match ($variant) {
        'success' => 'bg-emerald-50 text-emerald-900 ring-emerald-600/20',
        'warning' => 'bg-amber-50 text-amber-950 ring-amber-600/25',
        'danger' => 'bg-rose-50 text-rose-900 ring-rose-600/20',
        'neutral' => 'bg-gray-100 text-gray-800 ring-gray-300',
        default => 'bg-portal-primary-soft text-portal-primary ring-portal-primary-border',
    };

    $iconColor = match ($variant) {
        'success' => 'text-emerald-600',
        'warning' => 'text-amber-600',
        'danger' => 'text-rose-600',
        'neutral' => 'text-gray-500',
        default => 'text-portal-primary',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex flex-col gap-3 rounded-xl px-4 py-3.5 text-[14px] leading-relaxed ring-1 ring-inset sm:flex-row sm:items-start sm:justify-between {$styles}"]) }}>
    <div class="flex min-w-0 items-start gap-3">
        <span class="mt-0.5 shrink-0 {{ $iconColor }}">
            @switch($variant)
                @case('success')
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    @break
                @case('warning')
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    @break
                @case('danger')
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>
                    @break
                @default
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
            @endswitch
        </span>

        <div class="min-w-0 space-y-1">
            @if ($title)
                <p class="text-[15px] font-semibold leading-snug">{{ $title }}</p>
            @endif
            <div class="text-[14px] leading-relaxed">{{ $slot }}</div>
        </div>
    </div>

    @isset($action)
        <div class="shrink-0 sm:self-center">
            {{ $action }}
        </div>
    @endisset
</div>
