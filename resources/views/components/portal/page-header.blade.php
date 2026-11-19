@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        @isset($badges)
            <div class="mb-2.5 flex flex-wrap items-center gap-2">
                {{ $badges }}
            </div>
        @endisset

        <h1 class="text-[1.625rem] font-semibold leading-tight tracking-tight text-portal-text sm:text-[1.875rem] sm:leading-tight">
            {{ $title }}
        </h1>

        @if ($description)
            <p class="mt-1.5 text-[15px] leading-relaxed text-portal-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2.5 sm:pt-1">
            {{ $actions }}
        </div>
    @endisset
</div>
