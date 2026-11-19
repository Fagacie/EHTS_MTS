@props([
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-portal-primary text-white">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 2.75C8.5 7.1 6 10.6 6 14.25a6 6 0 1 0 12 0C18 10.6 15.5 7.1 12 2.75Z" />
            <path d="M9.5 14.75c.6 1.55 2 2.5 3.6 2.5" />
        </svg>
    </span>
    <div class="portal-logo-text min-w-0 leading-tight">
        <span class="block text-sm font-semibold tracking-tight text-portal-text">
            Ecohydrotech
        </span>
        @if ($subtitle)
            <span class="block text-xs font-medium text-portal-muted">
                {{ $subtitle }}
            </span>
        @endif
    </div>
</div>
