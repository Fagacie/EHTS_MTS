@php
    $totalAvailableModules = $categories->sum('published_modules_count');
    $activeCategories = $categories->filter(fn ($cat): bool => $cat->published_modules_count > 0);
    $selectedCategoryModel = $category !== '' ? $categories->firstWhere('slug', $category) : null;
    $hasActiveFilters = $search !== '' || $category !== '' || $availability !== 'all' || $sort !== 'newest';

    $availabilityLabels = [
        'independent' => 'Self-Paced Available',
        'session_only' => 'Physical Session Only',
    ];

    $sortLabels = [
        'title_asc' => 'Title (A–Z)',
        'price_asc' => 'Lowest Price',
    ];
@endphp

<div>
    {{-- Editorial Page Header --}}
    <section class="border-b border-[#E8E4EE] bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold tracking-wide text-[#5B377B]">
                        Training Catalogue
                    </p>
                    <h1 class="mt-1.5 text-2xl font-bold tracking-tight text-[#1C1921] sm:text-3xl">
                        Explore Our Training Programmes
                    </h1>
                    <p class="mt-2 text-sm leading-relaxed text-[#55505E]">
                        Structured technical training across hydrology, environmental science, and civil engineering, offered through scheduled physical sessions and self-paced study modules.
                    </p>
                </div>

                <div class="shrink-0 sm:text-right">
                    <p class="text-2xl font-bold tracking-tight text-[#1C1921] tabular-nums">
                        {{ $totalAvailableModules }}
                    </p>
                    <p class="text-xs font-medium text-[#736D7D]">
                        Available {{ \Illuminate\Support\Str::plural('programme', $totalAvailableModules) }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Catalogue Content --}}
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Filter Toolbar --}}
        <div class="space-y-3.5 rounded-xl border border-[#E8E4EE] bg-white p-4 shadow-xs">
            <div class="grid gap-3 md:grid-cols-12 md:items-center">
                {{-- Search Field --}}
                <div class="relative md:col-span-6">
                    <label for="catalogue-search" class="sr-only">Search training programmes</label>
                    <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#736D7D]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </div>
                    <input
                        id="catalogue-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by module title, code, or keyword..."
                        class="block w-full rounded-lg border border-[#DCD7E4] bg-white py-2 pr-8 pl-9 text-sm text-[#1C1921] placeholder:text-[#736D7D] transition focus:border-[#5B377B] focus:outline-none focus:ring-2 focus:ring-[#5B377B]/20"
                    >
                    @if ($search !== '')
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            aria-label="Clear search"
                            class="absolute inset-y-0 right-2 flex items-center px-1 text-[#736D7D] hover:text-[#1C1921]"
                        >
                            &times;
                        </button>
                    @endif
                </div>

                {{-- Delivery Availability --}}
                <div class="md:col-span-3">
                    <label for="catalogue-availability" class="sr-only">Delivery availability</label>
                    <select
                        id="catalogue-availability"
                        wire:model.live="availability"
                        class="block w-full rounded-lg border border-[#DCD7E4] bg-white px-3 py-2 text-sm text-[#1C1921] transition focus:border-[#5B377B] focus:outline-none focus:ring-2 focus:ring-[#5B377B]/20"
                    >
                        <option value="all">All Delivery Formats</option>
                        <option value="independent">Self-Paced Available</option>
                        <option value="session_only">Physical Session Only</option>
                    </select>
                </div>

                {{-- Sort Order --}}
                <div class="md:col-span-3">
                    <label for="catalogue-sort" class="sr-only">Sort programmes</label>
                    <select
                        id="catalogue-sort"
                        wire:model.live="sort"
                        class="block w-full rounded-lg border border-[#DCD7E4] bg-white px-3 py-2 text-sm text-[#1C1921] transition focus:border-[#5B377B] focus:outline-none focus:ring-2 focus:ring-[#5B377B]/20"
                    >
                        <option value="newest">Newly Published</option>
                        <option value="title_asc">Title (A–Z)</option>
                        <option value="price_asc">Lowest Self-Paced Price</option>
                    </select>
                </div>
            </div>

            {{-- Category Filter Tabs --}}
            <div class="flex flex-wrap items-center gap-1.5 border-t border-[#F0EDF5] pt-3" role="radiogroup" aria-label="Filter by category">
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs transition {{ $category === '' ? 'bg-[#5B377B] font-semibold text-white' : 'bg-[#F4F1F8] font-medium text-[#34303B] hover:bg-[#EAE5F0]' }}">
                    <input type="radio" wire:model.live="category" value="" class="sr-only">
                    <span>All Categories</span>
                    <span class="tabular-nums {{ $category === '' ? 'text-white/80' : 'text-[#736D7D]' }}">{{ $totalAvailableModules }}</span>
                </label>

                @foreach ($activeCategories as $cat)
                    @php
                        $isSelectedCat = $category === $cat->slug;
                    @endphp
                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs transition {{ $isSelectedCat ? 'bg-[#5B377B] font-semibold text-white' : 'bg-[#F4F1F8] font-medium text-[#34303B] hover:bg-[#EAE5F0]' }}">
                        <input type="radio" wire:model.live="category" value="{{ $cat->slug }}" class="sr-only">
                        <span>{{ $cat->name }}</span>
                        <span class="tabular-nums {{ $isSelectedCat ? 'text-white/80' : 'text-[#736D7D]' }}">{{ $cat->published_modules_count }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Results Status, Active Filter Chips & Clear Action --}}
        <div class="mt-5 flex flex-wrap items-center justify-between gap-3" aria-live="polite">
            <div class="flex flex-wrap items-center gap-2 text-xs text-[#55505E]">
                <span>
                    Showing <strong class="font-semibold text-[#1C1921] tabular-nums">{{ $modules->total() }}</strong> {{ \Illuminate\Support\Str::plural('programme', $modules->total()) }}
                </span>

                @if ($search !== '')
                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-0.5 font-medium text-[#1C1921] border border-[#DCD7E4] hover:bg-[#F4F1F8]"
                        title="Remove search filter"
                    >
                        <span>Search: "{{ $search }}"</span>
                        <span aria-hidden="true" class="text-[#736D7D]">&times;</span>
                    </button>
                @endif

                @if ($selectedCategoryModel)
                    <button
                        type="button"
                        wire:click="$set('category', '')"
                        class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-0.5 font-medium text-[#1C1921] border border-[#DCD7E4] hover:bg-[#F4F1F8]"
                        title="Remove category filter"
                    >
                        <span>{{ $selectedCategoryModel->name }}</span>
                        <span aria-hidden="true" class="text-[#736D7D]">&times;</span>
                    </button>
                @elseif ($category !== '')
                    <button
                        type="button"
                        wire:click="$set('category', '')"
                        class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-0.5 font-medium text-[#1C1921] border border-[#DCD7E4] hover:bg-[#F4F1F8]"
                        title="Remove category filter"
                    >
                        <span>Category: {{ $category }}</span>
                        <span aria-hidden="true" class="text-[#736D7D]">&times;</span>
                    </button>
                @endif

                @if ($availability !== 'all' && isset($availabilityLabels[$availability]))
                    <button
                        type="button"
                        wire:click="$set('availability', 'all')"
                        class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-0.5 font-medium text-[#1C1921] border border-[#DCD7E4] hover:bg-[#F4F1F8]"
                        title="Remove delivery filter"
                    >
                        <span>{{ $availabilityLabels[$availability] }}</span>
                        <span aria-hidden="true" class="text-[#736D7D]">&times;</span>
                    </button>
                @endif

                @if ($sort !== 'newest' && isset($sortLabels[$sort]))
                    <button
                        type="button"
                        wire:click="$set('sort', 'newest')"
                        class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-0.5 font-medium text-[#1C1921] border border-[#DCD7E4] hover:bg-[#F4F1F8]"
                        title="Reset sort order"
                    >
                        <span>Sort: {{ $sortLabels[$sort] }}</span>
                        <span aria-hidden="true" class="text-[#736D7D]">&times;</span>
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-3 text-xs">
                <span wire:loading class="font-medium text-[#5B377B]">Updating...</span>

                @if ($hasActiveFilters)
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="font-semibold text-[#5B377B] hover:underline"
                    >
                        Clear filters
                    </button>
                @endif
            </div>
        </div>

        {{-- Catalogue Cards Grid or Empty State --}}
        @if ($modules->isEmpty())
            <div class="mt-6 rounded-xl border border-[#E8E4EE] bg-white px-6 py-12 text-center shadow-xs">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-[#F4F1F8] text-[#5B377B]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>
                <h2 class="mt-3 text-sm font-semibold text-[#1C1921]">No matching training programmes</h2>
                <p class="mx-auto mt-1 max-w-md text-xs text-[#55505E]">
                    Adjust your search keywords or clear active filters to view all published training modules.
                </p>
                <div class="mt-4">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="inline-flex items-center justify-center rounded-md bg-[#5B377B] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#492A64]"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modules as $module)
                    <x-eht.module-card :module="$module" wire:key="module-{{ $module->id }}" />
                @endforeach
            </div>

            @if ($modules->hasPages())
                <div class="mt-8 border-t border-[#E8E4EE] pt-6">
                    {{ $modules->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
