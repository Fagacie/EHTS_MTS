<x-layouts.instructor :title="'Recordings — '.$session->session_code" :breadcrumb="$session->session_code.' / Recordings'">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-eht-teal-700">Session Recording Upload (INS-06, FR-08, D-017)</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $session->session_code }} — {{ $session->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Recording Access Policy: <strong>{{ $session->recording_access_policy->label() }}</strong>
                </p>
            </div>
            <x-eht.button href="{{ route('instructor.sessions.show', $session) }}" variant="outline" size="sm">
                &larr; Back to Session
            </x-eht.button>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-7 space-y-6">
                <x-eht.card title="Submitted Session Recordings ({{ $session->recordings->count() }})" subtitle="Recordings are stored privately on the company server, transcoded via FFmpeg HLS, and held in pending_review until published">
                    @if ($session->recordings->isEmpty())
                        <p class="text-xs text-slate-500">No recordings have been submitted for this session yet.</p>
                    @else
                        <div class="divide-y divide-slate-200">
                            @foreach ($session->recordings as $recording)
                                <div class="py-3.5 first:pt-0 last:pb-0">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-sm font-bold text-slate-900">{{ $recording->title }}</span>
                                        <div class="flex items-center gap-2">
                                            <x-eht.status-badge status="info" :label="$recording->encoding_status->label()" />
                                            <x-eht.status-badge :status="$recording->status->value" :label="$recording->status->label()" />
                                        </div>
                                    </div>
                                    @if ($recording->description)
                                        <p class="mt-1 text-xs text-slate-600">{{ $recording->description }}</p>
                                    @endif
                                    <p class="mt-1.5 text-[11px] text-slate-500 tabular-nums-mono">
                                        Stream ID: {{ $recording->provider_video_id }} · Disk: {{ $recording->storage_disk ?: 'recordings' }}
                                        @if ($recording->original_filename)
                                            · File: {{ $recording->original_filename }}
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-eht.card>
            </div>

            <div class="lg:col-span-5">
                <x-eht.card title="Upload Session Recording" subtitle="Upload an MP4/MOV/MKV/WebM video file for automated FFmpeg HLS processing and admin review">
                    <form method="POST" action="{{ route('instructor.sessions.recordings.store', $session) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label for="title" class="block text-xs font-semibold text-slate-700">Recording Title</label>
                            <input
                                id="title"
                                type="text"
                                name="title"
                                value="{{ old('title') }}"
                                required
                                placeholder="Part 1: Morning Hydrology Lecture & Calibration"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                            >
                            @error('title')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="video_file" class="block text-xs font-semibold text-slate-700">Video File (MP4 / MOV / MKV / WebM)</label>
                            <input
                                id="video_file"
                                type="file"
                                name="video_file"
                                accept="video/mp4,video/quicktime,video/x-matroska,video/webm"
                                class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700"
                            >
                            @error('video_file')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="provider_video_id" class="block text-xs font-semibold text-slate-700">Custom Stream Reference ID (Optional if uploading file)</label>
                            <input
                                id="provider_video_id"
                                type="text"
                                name="provider_video_id"
                                value="{{ old('provider_video_id') }}"
                                placeholder="rec-ses-2026-001-p1"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                            >
                            @error('provider_video_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="duration_seconds" class="block text-xs font-semibold text-slate-700">Duration in Seconds (Auto-detected on FFmpeg transcode)</label>
                            <input
                                id="duration_seconds"
                                type="number"
                                name="duration_seconds"
                                value="{{ old('duration_seconds', 3600) }}"
                                min="1"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-semibold text-slate-700">Recording Notes (Optional)</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                            >{{ old('description') }}</textarea>
                        </div>

                        <x-eht.button type="submit" variant="primary" class="w-full justify-center">
                            Upload &amp; Submit for Approval
                        </x-eht.button>
                    </form>
                </x-eht.card>
            </div>
        </div>
    </div>
</x-layouts.instructor>
