<x-layouts.instructor :title="'Completion Report — '.$session->session_code" :breadcrumb="$session->session_code.' / Completion'">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-eht-teal-700">Session Completion Notes (INS-05)</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $session->session_code }} — {{ $session->title }}</h1>
            </div>
            <x-eht.button href="{{ route('instructor.sessions.show', $session) }}" variant="outline" size="sm">
                &larr; Back to Session
            </x-eht.button>
        </div>

        <x-eht.card title="Delivery Summary &amp; Completion Notes" subtitle="Record syllabus coverage, practical lab outcomes, and any operational remarks for administrator review">
            <form method="POST" action="{{ route('instructor.sessions.completion.store', $session) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="completion_notes" class="block text-xs font-semibold text-slate-700">Session Completion Report</label>
                    <textarea
                        id="completion_notes"
                        name="completion_notes"
                        rows="6"
                        required
                        placeholder="Summarize technical topics covered, practical exercises completed, and cohort observations..."
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                    >{{ old('completion_notes', $session->completion_notes) }}</textarea>
                    @error('completion_notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <x-eht.button type="submit" variant="primary">
                        Save Completion Report
                    </x-eht.button>
                </div>
            </form>
        </x-eht.card>
    </div>
</x-layouts.instructor>
