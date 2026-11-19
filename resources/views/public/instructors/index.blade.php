<x-layouts.public title="Public Instructor Directory">
    <div class="border-b border-slate-200 bg-eht-navy-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <span class="inline-flex items-center rounded-full bg-eht-teal-600/20 px-3 py-1 text-xs font-semibold text-eht-teal-200">
                Ecohydrotech Faculty · Domain Specialists
            </span>
            <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-4xl">
                Instructor Profiles &amp; Technical Faculty
            </h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-300">
                Meet the certified engineers, hydrologists, and environmental specialists who deliver Ecohydrotech physical training sessions across Malaysia.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($instructors->isEmpty())
            <x-eht.empty-state
                title="No public instructor profiles published yet"
                description="Instructor profiles appear here once published by an administrator."
            />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($instructors as $instructor)
                    <article class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-xs transition hover:border-eht-teal-600/60 hover:shadow-md">
                        <div>
                            <div class="flex items-center gap-4">
                                @if ($instructor->photoUrl())
                                    <img src="{{ $instructor->photoUrl() }}" alt="{{ $instructor->resolvedDisplayName() }}" class="h-14 w-14 rounded-full object-cover border border-slate-200">
                                @else
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-eht-navy-900 text-lg font-bold text-eht-teal-200">
                                        {{ mb_substr($instructor->resolvedDisplayName(), 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <h2 class="text-base font-bold text-slate-900">
                                        <a href="{{ route('instructors.show', $instructor) }}" class="hover:text-eht-navy-700">
                                            {{ $instructor->resolvedDisplayName() }}
                                        </a>
                                    </h2>
                                    @if ($instructor->professional_title)
                                        <p class="text-xs font-medium text-eht-teal-700">{{ $instructor->professional_title }}</p>
                                    @endif
                                </div>
                            </div>

                            @if ($instructor->qualifications)
                                <p class="mt-3 text-xs font-medium text-slate-600">
                                    {{ $instructor->qualifications }}
                                </p>
                            @endif

                            @if ($instructor->biography)
                                <p class="mt-2.5 line-clamp-3 text-xs leading-relaxed text-slate-600">
                                    {{ $instructor->biography }}
                                </p>
                            @endif

                            @if (! empty($instructor->specialisations) && is_array($instructor->specialisations))
                                <div class="mt-4 flex flex-wrap gap-1.5">
                                    @foreach (array_slice($instructor->specialisations, 0, 4) as $spec)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700">
                                            {{ $spec }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <x-eht.button href="{{ route('instructors.show', $instructor) }}" variant="outline" size="sm" class="w-full">
                                View Full Profile &amp; Assigned Sessions
                            </x-eht.button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
