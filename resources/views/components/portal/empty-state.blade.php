@props([
    'title',
    'description' => null,
    'contained' => true,
])

<div {{ $attributes->merge([
    'class' => ($contained ? 'rounded-xl bg-portal-surface shadow-xs ring-1 ring-gray-950/5 ' : '').'px-6 py-10 text-center',
]) }}>
    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-portal-surface-muted text-portal-muted">
        @isset($icon)
            {{ $icon }}
        @else
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
                <polyline points="14 2 14 8 20 8" />
            </svg>
        @endisset
    </div>

    <h3 class="mt-3.5 text-base font-semibold text-portal-text sm:text-lg">{{ $title }}</h3>

    @if ($description)
        <p class="mx-auto mt-1.5 max-w-md text-[15px] leading-relaxed text-portal-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5 flex flex-wrap justify-center gap-3">{{ $action }}</div>
    @endisset
</div>
