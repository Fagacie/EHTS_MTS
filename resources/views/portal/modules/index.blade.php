@php
    $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');
@endphp

<x-layouts.portal title="My Learning" breadcrumb="My Learning">
    <div class="mx-auto max-w-7xl space-y-8">
        <x-portal.page-header
            title="My Learning"
            description="Access your purchased self-paced modules, downloadable technical documents, video lessons, and session recordings."
        >
            <x-slot:actions>
                <x-portal.button href="{{ route('portal.recordings.index') }}" variant="secondary" size="sm">
                    Session Recordings
                </x-portal.button>
                <x-portal.button href="{{ route('catalogue.index') }}" variant="primary" size="sm">
                    Browse Training
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        @if ($activeEntitlements->isEmpty())
            <x-portal.empty-state
                title="No active training modules yet"
                description="Purchase any self-paced module from the training catalogue to unlock online learning materials and video lessons."
            >
                <x-slot:action>
                    <x-portal.button href="{{ route('catalogue.index') }}" variant="primary">
                        Browse Training Catalogue
                    </x-portal.button>
                    <x-portal.button href="{{ route('portal.recordings.index') }}" variant="secondary">
                        Check Session Recordings
                    </x-portal.button>
                </x-slot:action>
            </x-portal.empty-state>
        @else
            <section aria-label="Active Module Workspaces">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($activeEntitlements as $entitlement)
                        @php
                            $module = $entitlement->module;
                        @endphp
                        @if ($module)
                            <article class="flex h-full flex-col justify-between rounded-xl bg-portal-surface p-5 shadow-xs ring-1 ring-gray-950/5 transition hover:ring-gray-300 sm:p-6">
                                <div>
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="rounded-md bg-portal-bg px-2.5 py-1 text-[13px] font-medium text-portal-text ring-1 ring-inset ring-portal-border tabular-nums-mono">
                                            {{ $module->code }}
                                        </span>
                                        <x-portal.status-badge status="active" label="Lifetime Access" />
                                    </div>

                                    @if ($module->category)
                                        <p class="mt-3.5 text-sm font-medium text-portal-primary">
                                            {{ $module->category->name }}
                                        </p>
                                    @endif

                                    <h2 class="mt-1.5 text-lg font-semibold leading-snug text-portal-text">
                                        <a href="{{ route('portal.modules.show', $module) }}" class="hover:text-portal-primary transition-colors">
                                            {{ $module->title }}
                                        </a>
                                    </h2>

                                    <p class="mt-2 line-clamp-2 text-[15px] leading-relaxed text-portal-muted">
                                        {{ $module->summary ?: \Illuminate\Support\Str::limit(strip_tags((string) $module->description), 140) }}
                                    </p>

                                    <dl class="mt-5 grid grid-cols-2 gap-3 rounded-lg bg-portal-bg p-3.5 ring-1 ring-inset ring-portal-border">
                                        <div>
                                            <dt class="text-[13px] font-medium text-portal-muted">Materials</dt>
                                            <dd class="mt-0.5 text-base font-semibold text-portal-text tabular-nums-mono">
                                                {{ $module->publishedMaterials->count() }} {{ \Illuminate\Support\Str::plural('file', $module->publishedMaterials->count()) }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-[13px] font-medium text-portal-muted">Video Lessons</dt>
                                            <dd class="mt-0.5 text-base font-semibold text-portal-text tabular-nums-mono">
                                                {{ $module->publishedVideos->count() }} {{ \Illuminate\Support\Str::plural('lesson', $module->publishedVideos->count()) }}
                                            </dd>
                                        </div>
                                    </dl>

                                    <p class="mt-3.5 text-[13px] text-portal-muted">
                                        Granted {{ $entitlement->granted_at?->timezone($tz)->format('d M Y') }} MYT
                                        &middot; {{ $entitlement->grant_source === 'admin_grant' ? 'Administrator Grant' : 'Purchased' }}
                                    </p>
                                </div>

                                <div class="mt-5 border-t border-portal-border pt-4">
                                    <x-portal.button href="{{ route('portal.modules.show', $module) }}" variant="primary" size="md" class="w-full">
                                        Open Learning Workspace
                                    </x-portal.button>
                                </div>
                            </article>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @if ($revokedEntitlements->isNotEmpty())
            <x-portal.card title="Inactive &amp; Revoked Modules" subtitle="Modules whose access was ended following a refund or administrative update">
                <div class="divide-y divide-portal-border">
                    @foreach ($revokedEntitlements as $revoked)
                        <div class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="text-sm font-medium text-portal-text tabular-nums-mono">{{ $revoked->module?->code }}</span>
                                    <x-portal.status-badge status="revoked" label="Revoked" />
                                </div>
                                <p class="mt-1 text-[15px] font-semibold text-portal-text">{{ $revoked->module?->title }}</p>
                                @if ($revoked->revocation_reason)
                                    <p class="mt-0.5 text-sm text-rose-700">Reason: {{ $revoked->revocation_reason }}</p>
                                @endif
                            </div>
                            @if ($revoked->module)
                                <x-portal.button href="{{ route('catalogue.modules.show', $revoked->module) }}" variant="secondary" size="sm">
                                    View Programme
                                </x-portal.button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-portal.card>
        @endif
    </div>
</x-layouts.portal>
