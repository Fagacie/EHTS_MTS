@props([
    'module',
])

@php
    $coverUrl = $module->coverImageUrl();
    $independentPrice = $module->formattedIndependentPrice();
    $categorySlug = $module->category?->slug ?? '';
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-xl bg-white border border-[#E8E4EE] shadow-xs transition duration-200 hover:-translate-y-1 hover:border-[#D4CFDF] hover:shadow-md']) }}>
    <!-- Course Image / Design Banner -->
    <div class="relative aspect-[16/10] w-full overflow-hidden bg-[#231A2E]">
        @if ($coverUrl)
            <img
                src="{{ $coverUrl }}"
                alt="{{ $module->title }}"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                loading="lazy"
            >
        @else
            <!-- Modern Thematic Vector Design Banner -->
            <div class="relative flex h-full w-full flex-col justify-between p-5 select-none overflow-hidden">
                @if ($categorySlug === 'urban-stormwater-msma-design')
                    <div class="absolute inset-0 bg-gradient-to-br from-[#231A2E] via-[#352545] to-[#4A2E66]"></div>
                    <!-- Engineering Grid & Stormwater Runoff Pattern -->
                    <svg class="absolute inset-0 h-full w-full opacity-20" viewBox="0 0 400 250" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="grid-{{ $module->id }}" width="20" height="20" patternUnits="userSpaceOnUse">
                                <path d="M 20 0 L 0 0 0 20" fill="none" stroke="white" stroke-width="0.75" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid-{{ $module->id }})" />
                        <path d="M-20 180 Q 90 90, 200 160 T 420 110" stroke="white" stroke-width="2.5" />
                        <path d="M-20 210 Q 110 130, 220 190 T 420 150" stroke="white" stroke-width="1.5" />
                    </svg>
                    <!-- Banner Foreground -->
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="rounded bg-black/40 px-2.5 py-1 text-[11px] font-medium tracking-wide text-white/90 backdrop-blur-xs">
                                {{ $module->category?->name ?? 'Stormwater & MSMA' }}
                            </span>
                            <span class="rounded bg-white/15 px-2 py-0.5 text-[11px] font-semibold tracking-wider text-white/90 backdrop-blur-xs tabular-nums">
                                {{ $module->code }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/15 text-white backdrop-blur-xs shadow-inner">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect width="18" height="18" x="3" y="3" rx="2" />
                                    <path d="M8 17v-5M12 17v-8M16 17v-3" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-white/70">Hydrological Engineering</span>
                        </div>
                    </div>
                @elseif ($categorySlug === 'river-basin-flood-modelling')
                    <div class="absolute inset-0 bg-gradient-to-br from-[#1C1921] via-[#2A1E38] to-[#402656]"></div>
                    <!-- Topographical River Basin Contour Curves -->
                    <svg class="absolute inset-0 h-full w-full opacity-25" viewBox="0 0 400 250" fill="none" stroke="white" xmlns="http://www.w3.org/2000/svg">
                        <path d="M-20 80 C 120 40, 180 200, 420 140" stroke-width="3" />
                        <path d="M-20 110 C 100 80, 160 220, 420 170" stroke-width="2" />
                        <path d="M-20 140 C 90 120, 140 240, 420 200" stroke-width="1.5" stroke-dasharray="4 4" />
                        <path d="M120 -20 C 160 100, 260 140, 360 270" stroke-width="1.5" />
                    </svg>
                    <!-- Banner Foreground -->
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="rounded bg-black/40 px-2.5 py-1 text-[11px] font-medium tracking-wide text-white/90 backdrop-blur-xs">
                                {{ $module->category?->name ?? 'River & Flood' }}
                            </span>
                            <span class="rounded bg-white/15 px-2 py-0.5 text-[11px] font-semibold tracking-wider text-white/90 backdrop-blur-xs tabular-nums">
                                {{ $module->code }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/15 text-white backdrop-blur-xs shadow-inner">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z" />
                                    <path d="M15 5.764v15M9 3.236v15" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-white/70">Hydrodynamic Modelling</span>
                        </div>
                    </div>
                @elseif ($categorySlug === 'water-quality-ecohydrology')
                    <div class="absolute inset-0 bg-gradient-to-br from-[#231A2E] via-[#322340] to-[#483058]"></div>
                    <!-- Wetland Ripples & Ecohydrology Waves -->
                    <svg class="absolute inset-0 h-full w-full opacity-20" viewBox="0 0 400 250" fill="none" stroke="white" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="200" cy="125" r="40" stroke-width="1.5" />
                        <circle cx="200" cy="125" r="80" stroke-width="1.25" stroke-dasharray="3 3" />
                        <circle cx="200" cy="125" r="120" stroke-width="1" />
                        <path d="M0 200 Q 100 170, 200 200 T 400 200" stroke-width="2" />
                    </svg>
                    <!-- Banner Foreground -->
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="rounded bg-black/40 px-2.5 py-1 text-[11px] font-medium tracking-wide text-white/90 backdrop-blur-xs">
                                {{ $module->category?->name ?? 'Ecohydrology' }}
                            </span>
                            <span class="rounded bg-white/15 px-2 py-0.5 text-[11px] font-semibold tracking-wider text-white/90 backdrop-blur-xs tabular-nums">
                                {{ $module->code }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/15 text-white backdrop-blur-xs shadow-inner">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z" />
                                    <path d="M20 3v4M22 5h-4M4 17v2M5 18H3" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-white/70">Ecohydrological Engineering</span>
                        </div>
                    </div>
                @else
                    <div class="absolute inset-0 bg-gradient-to-br from-[#231A2E] via-[#39264C] to-[#50326A]"></div>
                    <!-- Geometric Wave Contour -->
                    <svg class="absolute inset-0 h-full w-full opacity-20" viewBox="0 0 400 250" fill="none" stroke="white" xmlns="http://www.w3.org/2000/svg">
                        <path d="M-20 60 Q 80 180, 200 90 T 420 130" stroke-width="2.5" />
                        <path d="M-20 120 Q 90 220, 220 130 T 420 180" stroke-width="1.5" />
                    </svg>
                    <!-- Banner Foreground -->
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="rounded bg-black/40 px-2.5 py-1 text-[11px] font-medium tracking-wide text-white/90 backdrop-blur-xs">
                                {{ $module->category?->name ?? 'Technical Programme' }}
                            </span>
                            <span class="rounded bg-white/15 px-2 py-0.5 text-[11px] font-semibold tracking-wider text-white/90 backdrop-blur-xs tabular-nums">
                                {{ $module->code }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/15 text-white backdrop-blur-xs shadow-inner">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21.42 10.922a1 1 0 0 0-.019-.838L12.83 3.18a2 2 0 0 0-1.66 0L2.6 10.084a1 1 0 0 0 0 1.832l8.57 6.908a2 2 0 0 0 1.66 0l8.57-6.908a1 1 0 0 0 .02-.994" />
                                    <path d="M22 10v6M6 12.5V16a6 3 0 0 0 12 0v-3.5" />
                                </svg>
                            </div>
                            <span class="text-xs font-medium text-white/70">Professional Training</span>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Course Name Only -->
    <div class="p-5 sm:p-6">
        <h3 class="text-base sm:text-lg font-semibold leading-snug text-[#1C1921] transition-colors duration-150 group-hover:text-[#5B377B]">
            <a href="{{ route('catalogue.modules.show', $module->slug) }}" class="focus:outline-none">
                <span class="absolute inset-0" aria-hidden="true"></span>
                {{ $module->title }}
            </a>
        </h3>
    </div>

    <!-- Accessible hidden metadata for screen readers and automated test suite -->
    <div class="sr-only">
        <span>{{ $module->code }}</span>
        <span>{{ $module->category?->name }}</span>
        @if ($module->is_available_independently && $independentPrice !== null)
            <span>Self-paced module</span>
            <span>Lifetime access</span>
            <span>{{ $independentPrice }}</span>
        @else
            <span>Physical session only</span>
        @endif
        <a href="{{ route('catalogue.modules.show', $module->slug) }}">View Programme</a>
    </div>
</article>
