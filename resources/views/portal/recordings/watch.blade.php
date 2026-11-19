<x-layouts.portal :title="$recording->title" :breadcrumb="($session?->session_code ?? 'Session').' / Recording'">
    <div class="mx-auto max-w-5xl space-y-8">
        <x-portal.page-header
            :title="$recording->title"
            :description="($session?->title ? $session->title.' · ' : '').'Duration: '.$recording->formattedDuration()"
        >
            <x-slot:badges>
                @if ($session?->session_code)
                    <span class="rounded-md bg-portal-bg px-2.5 py-1 text-[13px] font-medium text-portal-text ring-1 ring-inset ring-portal-border tabular-nums-mono">
                        {{ $session->session_code }}
                    </span>
                @endif
                <x-portal.status-badge status="verified" label="Verified Attendee Access" />
            </x-slot:badges>

            <x-slot:actions>
                <x-portal.button href="{{ route('portal.recordings.index') }}" variant="secondary" size="sm">
                    Back to Session Recordings
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        <x-portal.card title="Session Recording Player" subtitle="Authorised playback for verified session attendees">
            @if ($streamManifestUrl)
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
            @else
                <x-portal.alert variant="warning" title="Video Preparing">
                    This session recording is currently being prepared for streaming. Please check back shortly.
                </x-portal.alert>
            @endif

            @if ($recording->description)
                <p class="mt-4 text-[15px] leading-relaxed text-portal-muted">{{ $recording->description }}</p>
            @endif
        </x-portal.card>
    </div>
</x-layouts.portal>
