<x-layouts.public title="Upcoming Physical Training Sessions">
    <div class="border-b border-slate-200 bg-eht-navy-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <span class="inline-flex items-center rounded-full bg-eht-teal-600/20 px-3 py-1 text-xs font-semibold text-eht-teal-200">
                In-Person Delivery · Certification Eligible
            </span>
            <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-4xl">
                Upcoming Physical Training Sessions
            </h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-300">
                Register for scheduled instructor-led sessions at Ecohydrotech training venues across Malaysia. Every booking reserves your seat for 15 minutes during checkout.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <!-- Search & Category Filter Bar -->
        <form method="GET" action="{{ route('sessions.index') }}" class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="grid gap-3 sm:grid-cols-12">
                <div class="sm:col-span-6">
                    <label for="session-q" class="sr-only">Search sessions</label>
                    <input
                        id="session-q"
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search by session code, module title, or venue city..."
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                    >
                </div>
                <div class="sm:col-span-4">
                    <label for="session-category" class="sr-only">Category</label>
                    <select
                        id="session-category"
                        name="category"
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                    >
                        <option value="">All Training Categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($selectedCategory === $category->slug)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 flex items-center gap-2">
                    <x-eht.button type="submit" variant="primary" class="w-full">
                        Filter
                    </x-eht.button>
                </div>
            </div>
        </form>

        @if ($sessions->isEmpty())
            <x-eht.empty-state
                title="No upcoming sessions match your filter"
                description="Try clearing your search filter or browse the self-paced training catalogue."
            >
                <x-eht.button href="{{ route('sessions.index') }}" variant="outline" size="sm">
                    Reset Filters
                </x-eht.button>
                <x-eht.button href="{{ route('catalogue.index') }}" variant="primary" size="sm">
                    Browse Training Catalogue
                </x-eht.button>
            </x-eht.empty-state>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($sessions as $session)
                    <x-eht.session-card :session="$session" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</x-layouts.public>
