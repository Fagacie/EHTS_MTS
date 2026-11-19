@php
    $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');
@endphp

<x-layouts.portal :title="$module->title.' — Learning Workspace'" :breadcrumb="$module->code">
    <div class="mx-auto max-w-7xl space-y-8">
        <x-portal.page-header
            :title="$module->title"
            :description="$entitlement ? 'Access granted on '.$entitlement->granted_at?->timezone($tz)->format('d M Y, h:i A').' MYT' : null"
        >
            <x-slot:badges>
                <span class="rounded-md bg-portal-bg px-2.5 py-1 text-[13px] font-medium text-portal-text ring-1 ring-inset ring-portal-border tabular-nums-mono">
                    {{ $module->code }}
                </span>
                <x-portal.status-badge status="active" label="Lifetime Access" />
            </x-slot:badges>

            <x-slot:actions>
                <x-portal.button href="{{ route('portal.modules.index') }}" variant="secondary" size="sm">
                    Back to My Learning
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        <div class="grid gap-8 lg:grid-cols-12">
            <!-- Left 8-Col Video Stage, Downloads & Syllabus -->
            <div class="space-y-6 lg:col-span-8">
                @if ($activeVideo && $streamManifestUrl)
                    <x-portal.card :title="$activeVideo->title" :subtitle="'Duration: '.$activeVideo->formattedDuration()">
                        <div class="aspect-video w-full overflow-hidden rounded-lg bg-gray-950 ring-1 ring-gray-950/10">
                            <video
                                controls
                                playsinline
                                controlsList="nodownload"
                                preload="metadata"
                                data-etms-hls-player
                                data-src="{{ $streamManifestUrl }}"
                                class="h-full w-full"
                            >
                                Your browser does not support HTML5 video playback.
                            </video>
                        </div>
                        @if ($activeVideo->description)
                            <p class="mt-4 text-[15px] leading-relaxed text-portal-muted">{{ $activeVideo->description }}</p>
                        @endif
                    </x-portal.card>
                @elseif ($module->publishedVideos->isNotEmpty())
                    <x-portal.alert variant="info" title="Video Curriculum Preparing">
                        Video lessons for this module are currently being prepared for playback. Please check back shortly.
                    </x-portal.alert>
                @endif

                <!-- Downloadable Learning Materials -->
                <x-portal.card
                    :title="'Learning Materials ('.$module->publishedMaterials->count().')'"
                    subtitle="Time-limited secure downloads for your enrolled module"
                >
                    @if ($module->publishedMaterials->isEmpty())
                        <p class="text-[15px] text-portal-muted">No downloadable materials have been published for this module yet.</p>
                    @else
                        <div class="divide-y divide-portal-border">
                            @foreach ($module->publishedMaterials as $material)
                                <div class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2.5">
                                            <span class="text-[15px] font-semibold text-portal-text">{{ $material->title }}</span>
                                            <x-portal.status-badge status="info" :label="$material->material_type->label()" />
                                        </div>
                                        @if ($material->description)
                                            <p class="text-sm leading-relaxed text-portal-muted">{{ $material->description }}</p>
                                        @endif
                                        <p class="text-[13px] text-portal-muted tabular-nums-mono">
                                            {{ $material->original_filename }} &middot; {{ $material->formattedFileSize() }}
                                        </p>
                                    </div>

                                    <div class="shrink-0">
                                        @if ($material->is_downloadable)
                                            <x-portal.button href="{{ $material->temporaryDownloadUrl(15) }}" variant="primary" size="sm">
                                                Download ({{ $material->formattedFileSize() }})
                                            </x-portal.button>
                                        @else
                                            <span class="inline-flex items-center rounded-lg bg-portal-surface-muted px-3 py-2 text-sm font-medium text-portal-muted">
                                                Download Unavailable
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-portal.card>

                <x-portal.card title="Syllabus &amp; Learning Objectives">
                    @if (! empty($module->learning_objectives) && is_array($module->learning_objectives))
                        <ul class="mb-5 space-y-2.5 text-[15px] leading-relaxed text-portal-text">
                            @foreach ($module->learning_objectives as $obj)
                                <li class="flex items-start gap-2.5">
                                    <svg class="mt-1 h-4 w-4 shrink-0 text-portal-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    <span>{{ $obj }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="border-t border-portal-border pt-4 text-[15px] leading-relaxed text-portal-text whitespace-pre-line">
                        {{ $module->description }}
                    </div>
                </x-portal.card>
            </div>

            <!-- Right 4-Col Video Curriculum Playlist -->
            <aside class="lg:col-span-4">
                <x-portal.card :title="'Video Lessons ('.$module->publishedVideos->count().')'">
                    @if ($module->publishedVideos->isEmpty())
                        <p class="text-[15px] leading-relaxed text-portal-muted">
                            This programme is delivered through the downloadable workbooks and technical documents on this page.
                        </p>
                    @else
                        <div class="space-y-3">
                            @foreach ($module->publishedVideos as $index => $video)
                                @php
                                    $isCurrent = $activeVideo && $activeVideo->id === $video->id;
                                    $isPlayable = $video->isPlayable();
                                @endphp
                                <div class="rounded-lg p-4 ring-1 ring-inset transition {{ $isCurrent ? 'bg-portal-primary-soft ring-portal-primary/30' : 'bg-portal-bg ring-portal-border' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-[15px] font-semibold leading-snug {{ $isCurrent ? 'text-portal-primary' : 'text-portal-text' }}">
                                            <span class="mr-1 text-sm font-medium text-portal-muted tabular-nums">{{ $index + 1 }}.</span>
                                            {{ $video->title }}
                                        </p>
                                        <span class="shrink-0 text-[13px] font-medium text-portal-muted tabular-nums-mono">
                                            {{ $video->formattedDuration() }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <span class="text-[13px] text-portal-muted">
                                            {{ $video->encoding_status->label() }}
                                        </span>

                                        @if ($isPlayable)
                                            <x-portal.button
                                                href="{{ $video->temporaryWatchUrl(30) }}"
                                                :variant="$isCurrent ? 'primary' : 'secondary'"
                                                size="sm"
                                            >
                                                {{ $isCurrent ? 'Playing' : 'Watch Lesson' }}
                                            </x-portal.button>
                                        @else
                                            <x-portal.status-badge status="processing" label="Processing" />
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-portal.card>
            </aside>
        </div>
    </div>
</x-layouts.portal>
