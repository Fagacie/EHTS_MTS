@props([
    'dark' => false,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md {{ $dark ? 'bg-white/10 text-zinc-100 ring-1 ring-white/20' : 'bg-[#5B377B] text-white' }}">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.75C8.5 7.1 6 10.6 6 14.25a6 6 0 1 0 12 0C18 10.6 15.5 7.1 12 2.75Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 14.75c.6 1.55 2 2.5 3.6 2.5" />
        </svg>
    </span>
    <div class="leading-tight">
        <span class="block text-sm font-semibold tracking-tight {{ $dark ? 'text-white' : 'text-zinc-900' }}">
            Ecohydrotech
        </span>
        <span class="block text-xs font-medium {{ $dark ? 'text-zinc-400' : 'text-zinc-500' }}">
            {{ $subtitle ?? 'Training Management' }}
        </span>
    </div>
</div>
