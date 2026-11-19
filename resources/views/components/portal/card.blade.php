@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
])

<section {{ $attributes->merge(['class' => 'rounded-xl bg-portal-surface shadow-xs ring-1 ring-gray-950/5']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-portal-border px-5 py-4 sm:px-6">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="truncate text-base font-semibold text-portal-text sm:text-lg">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-portal-muted">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2.5">
                    {{ $actions }}
                </div>
            @endisset
        </header>
    @endif

    <div class="{{ $padding ? 'p-5 sm:p-6' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="rounded-b-xl border-t border-portal-border bg-portal-bg px-5 py-3.5 sm:px-6">
            {{ $footer }}
        </footer>
    @endisset
</section>
