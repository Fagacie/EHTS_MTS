<x-layouts.public :title="$module->title">
    <!-- Top Breadcrumb & Header Banner -->
    <div class="border-b border-slate-200 bg-eht-navy-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-300" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                <span>/</span>
                <a href="{{ route('catalogue.index') }}" class="hover:text-white">Training Catalogue</a>
                @if ($module->category)
                    <span>/</span>
                    <a href="{{ route('catalogue.index', ['category' => $module->category->slug]) }}" class="hover:text-white">
                        {{ $module->category->name }}
                    </a>
                @endif
                <span>/</span>
                <span class="font-semibold text-eht-teal-200 tabular-nums-mono">{{ $module->code }}</span>
            </nav>

            <div class="mt-4 flex flex-wrap items-center gap-2.5">
                <span class="rounded-md bg-white/10 px-2.5 py-1 text-xs font-semibold text-eht-teal-200 tabular-nums-mono">
                    {{ $module->code }}
                </span>
                @if ($module->category)
                    <span class="rounded-full bg-eht-teal-600/25 px-3 py-1 text-xs font-semibold text-eht-teal-100">
                        {{ $module->category->name }}
                    </span>
                @endif
                <x-eht.status-badge status="published" label="Published Module" />
            </div>

            <h1 class="mt-3 text-2xl font-bold tracking-tight text-white sm:text-4xl">
                {{ $module->title }}
            </h1>

            @if ($module->summary)
                <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-300">
                    {{ $module->summary }}
                </p>
            @endif
        </div>
    </div>

    <!-- Main 8 / 4 Split -->
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-12">
            <!-- Left 8-Col Content -->
            <div class="lg:col-span-8 space-y-8">
                @if ($module->coverImageUrl())
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                        <img src="{{ $module->coverImageUrl() }}" alt="{{ $module->title }}" class="max-h-80 w-full object-cover">
                    </div>
                @endif

                @if (! empty($module->learning_objectives) && is_array($module->learning_objectives))
                    <x-eht.card title="Learning Objectives" subtitle="Key competencies and technical outcomes covered in this module">
                        <ul class="grid gap-3 sm:grid-cols-2 text-sm text-slate-700">
                            @foreach ($module->learning_objectives as $objective)
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-eht-emerald-100 text-eht-emerald-700 text-xs font-bold">
                                        &check;
                                    </span>
                                    <span>{{ $objective }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-eht.card>
                @endif

                <x-eht.card title="Module Syllabus &amp; Technical Description">
                    <div class="prose prose-slate max-w-none text-sm leading-relaxed text-slate-700 whitespace-pre-line">
                        {{ $module->description }}
                    </div>
                </x-eht.card>

                <x-eht.card title="Included Learning Materials ({{ $module->publishedMaterials->count() }})" subtitle="Private Cloudflare R2 documents unlocked by an active module entitlement or administrator grant (FR-07, D-018)">
                    @if ($module->publishedMaterials->isEmpty())
                        <p class="text-xs text-slate-500">Learning materials for this module will be published prior to delivery.</p>
                    @else
                        <div class="divide-y divide-slate-200">
                            @foreach ($module->publishedMaterials as $material)
                                <div class="flex flex-wrap items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-slate-900">{{ $material->title }}</span>
                                            <x-eht.status-badge status="info" :label="$material->material_type->label()" />
                                        </div>
                                        @if ($material->description)
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $material->description }}</p>
                                        @endif
                                        <p class="mt-1 text-[11px] text-slate-400 tabular-nums-mono">
                                            Size: {{ $material->formattedFileSize() }}
                                        </p>
                                    </div>

                                    <div>
                                        @can('download', $material)
                                            <x-eht.button href="{{ route('materials.download', $material) }}" size="sm" variant="outline">
                                                Download ({{ $material->formattedFileSize() }})
                                            </x-eht.button>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500">
                                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                </svg>
                                                Requires Module Entitlement
                                            </span>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-eht.card>
            </div>

            <!-- Right 4-Col Sticky Conversion Stack (D-005 & D-018 Separation) -->
            <aside class="lg:col-span-4 space-y-6">
                <!-- Card 1: Physical Training Sessions -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="rounded-lg bg-eht-navy-50 px-2.5 py-1 text-xs font-semibold text-eht-navy-700">Option 1 · In-Person</span>
                        <x-eht.status-badge status="published" label="Certification Eligible" />
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-slate-900">Physical Training Sessions</h2>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-600">
                        Register for a scheduled instructor-led session at an Ecohydrotech venue. Physical attendance and administrator approval qualify you for an official QR-verifiable certificate (D-007).
                    </p>

                    @if ($module->upcomingSessions->isEmpty())
                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center">
                            <p class="text-xs font-semibold text-slate-700">No Upcoming Physical Cohorts Scheduled</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Check the <a href="{{ route('sessions.index') }}" class="font-semibold text-eht-teal-700 underline">Physical Sessions Directory</a> for other upcoming dates.
                            </p>
                        </div>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($module->upcomingSessions as $session)
                                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-semibold text-slate-800 tabular-nums-mono">{{ $session->session_code }}</span>
                                        <x-eht.status-badge :status="$session->status->value" :label="$session->status->label()" />
                                    </div>
                                    <a href="{{ route('sessions.show', $session) }}" class="mt-1 block text-sm font-bold text-slate-900 hover:text-eht-navy-700">
                                        {{ $session->title }}
                                    </a>
                                    <p class="mt-1 text-xs text-slate-600">{{ $session->formattedSchedule() }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $session->venue_name }} ({{ $session->venue_city }})</p>
                                    <div class="mt-2.5 flex items-center justify-between border-t border-slate-200/70 pt-2.5">
                                        <div>
                                            <span class="text-sm font-bold text-slate-900 tabular-nums-mono">{{ $session->formattedPrice() }}</span>
                                            <span class="ml-1.5 text-[11px] font-medium text-eht-emerald-700 tabular-nums-mono">({{ $session->remainingSeats() }}/{{ $session->capacity }} left)</span>
                                        </div>
                                        @if ($session->isOpenForBooking())
                                            <x-eht.button href="{{ route('checkout.session.show', $session) }}" variant="primary" size="sm">
                                                Book Seat
                                            </x-eht.button>
                                        @else
                                            <x-eht.button href="{{ route('sessions.show', $session) }}" variant="outline" size="sm">
                                                Details
                                            </x-eht.button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Card 2: Independent Self-Paced Module Purchase -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="rounded-lg bg-eht-teal-50 px-2.5 py-1 text-xs font-semibold text-eht-teal-700">Option 2 · Self-Paced Online</span>
                        @if ($module->is_available_independently)
                            <x-eht.status-badge status="issued" label="Lifetime Access" />
                        @else
                            <x-eht.status-badge status="neutral" label="Session Only" />
                        @endif
                    </div>

                    <h2 class="mt-3 text-lg font-bold text-slate-900">Independent Module Purchase</h2>

                    @if ($module->is_available_independently && $module->independent_price !== null)
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-slate-900 tabular-nums-mono">{{ $module->formattedIndependentPrice() }}</span>
                            <span class="text-xs text-slate-500">one-time payment · lifetime access</span>
                        </div>

                        <ul class="mt-4 space-y-2 text-xs text-slate-600">
                            <li class="flex items-center gap-2">
                                <span class="text-eht-emerald-600 font-bold">&check;</span>
                                <span>{{ $module->publishedMaterials->count() }} downloadable technical {{ \Illuminate\Support\Str::plural('document', $module->publishedMaterials->count()) }} (R2)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-eht-emerald-600 font-bold">&check;</span>
                                <span>Lifetime online entitlement upon verified Billplz payment (D-006)</span>
                            </li>
                        </ul>

                        <div class="mt-5">
                            @auth
                                @if (auth()->user()->hasActiveModuleEntitlement($module->id))
                                    <div class="space-y-2">
                                        <div class="rounded-xl border border-eht-emerald-200 bg-eht-emerald-50/70 p-3 text-center text-xs font-bold text-eht-emerald-800">
                                            &check; Active Lifetime Module Entitlement Owned
                                        </div>
                                        <x-eht.button href="{{ route('portal.modules.show', $module) }}" variant="primary" class="w-full">
                                            Open Learning Workspace
                                        </x-eht.button>
                                    </div>
                                @elseif (auth()->user()->isCustomer())
                                    <x-eht.button href="{{ route('checkout.module.show', $module) }}" variant="teal" class="w-full">
                                        Purchase Module ({{ $module->formattedIndependentPrice() }})
                                    </x-eht.button>
                                @else
                                    <p class="text-xs text-slate-600">
                                        Your account does not currently hold the Customer/Trainee role required to purchase modules.
                                    </p>
                                @endif
                            @else
                                <x-eht.button href="{{ route('login') }}" variant="teal" class="w-full">
                                    Sign In to Purchase ({{ $module->formattedIndependentPrice() }})
                                </x-eht.button>
                            @endauth
                        </div>
                    @else
                        <p class="mt-2 text-xs text-slate-600">
                            This module is offered exclusively through scheduled physical training sessions and is not sold as a standalone self-paced module.
                        </p>
                    @endif

                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50/70 px-3.5 py-2.5 text-[11px] leading-relaxed text-amber-900">
                        <strong>Separation Policy (D-018):</strong> Independent module purchases grant lifetime access to module materials and videos only. Physical session registration and attendance certificates are separate.
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.public>
