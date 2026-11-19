<x-layouts.public :title="$instructor->resolvedDisplayName()">
    <div class="border-b border-slate-200 bg-eht-navy-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-300" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <span>/</span>
                <a href="{{ route('instructors.index') }}" class="hover:text-white">Instructors</a>
                <span>/</span>
                <span class="font-semibold text-eht-teal-200">{{ $instructor->resolvedDisplayName() }}</span>
            </nav>

            <div class="mt-6 flex flex-wrap items-center gap-5">
                @if ($instructor->photoUrl())
                    <img src="{{ $instructor->photoUrl() }}" alt="{{ $instructor->resolvedDisplayName() }}" class="h-20 w-20 rounded-full object-cover border-2 border-eht-teal-500">
                @else
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-eht-navy-800 text-2xl font-bold text-eht-teal-200 border border-white/15">
                        {{ mb_substr($instructor->resolvedDisplayName(), 0, 1) }}
                    </div>
                @endif
                <div>
                    <x-eht.status-badge status="published" label="Verified Faculty Profile" />
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-4xl">
                        {{ $instructor->resolvedDisplayName() }}
                    </h1>
                    @if ($instructor->professional_title)
                        <p class="mt-1 text-sm font-medium text-eht-teal-200">{{ $instructor->professional_title }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-12">
            <div class="lg:col-span-8 space-y-8">
                <x-eht.card title="Professional Biography &amp; Qualifications">
                    @if ($instructor->qualifications)
                        <div class="mb-4 rounded-xl bg-slate-50 p-3.5 border border-slate-100">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Credentials &amp; Qualifications</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $instructor->qualifications }}</p>
                        </div>
                    @endif

                    <div class="prose prose-slate max-w-none text-sm leading-relaxed text-slate-700 whitespace-pre-line">
                        {{ $instructor->biography ?: 'Detailed professional biography will be updated shortly.' }}
                    </div>
                </x-eht.card>

                <x-eht.card title="Upcoming Assigned Training Sessions ({{ $upcomingSessions->count() }})" subtitle="Register directly for an upcoming physical session led by {{ $instructor->resolvedDisplayName() }}">
                    @if ($upcomingSessions->isEmpty())
                        <p class="text-xs text-slate-500">No upcoming public sessions are currently scheduled for this instructor.</p>
                    @else
                        <div class="grid gap-5 md:grid-cols-2">
                            @foreach ($upcomingSessions as $session)
                                <x-eht.session-card :session="$session" />
                            @endforeach
                        </div>
                    @endif
                </x-eht.card>
            </div>

            <aside class="lg:col-span-4 space-y-6">
                <x-eht.card title="Specialisations &amp; Public Contact">
                    @if (! empty($instructor->specialisations) && is_array($instructor->specialisations))
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Technical Specialisations</p>
                            <div class="mt-2.5 flex flex-wrap gap-2">
                                @foreach ($instructor->specialisations as $spec)
                                    <span class="rounded-full bg-eht-teal-50 px-3 py-1 text-xs font-semibold text-eht-teal-800 border border-eht-teal-200">
                                        {{ $spec }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($instructor->short_bio)
                        <div class="mt-5 border-t border-slate-100 pt-4 text-xs leading-relaxed text-slate-600">
                            <p class="font-semibold text-slate-800">Profile Summary</p>
                            <p class="mt-1">{{ $instructor->short_bio }}</p>
                        </div>
                    @endif
                </x-eht.card>
            </aside>
        </div>
    </div>
</x-layouts.public>
