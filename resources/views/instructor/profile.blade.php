<x-layouts.instructor title="My Instructor Profile" breadcrumb="Instructor Profile">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">My Instructor Profile</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Maintain your professional biography, qualifications, and specialisations displayed on public session pages (INS-07, FR-15).
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-eht.status-badge
                    :status="$profile->is_public ? 'published' : 'draft'"
                    :label="$profile->is_public ? 'Published on Public Directory' : 'Hidden from Public Directory'"
                />
                @if ($profile->is_public)
                    <x-eht.button href="{{ route('instructors.show', $profile) }}" variant="outline" size="sm">
                        View Public Profile
                    </x-eht.button>
                @endif
            </div>
        </div>

        <x-eht.card title="Professional Credentials &amp; Biography" subtitle="Public directory visibility is governed by an administrator">
            <form method="POST" action="{{ route('instructor.profile.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="display_name" class="block text-xs font-semibold text-slate-700">Public Display Name</label>
                        <input
                            id="display_name"
                            type="text"
                            name="display_name"
                            value="{{ old('display_name', $profile->display_name) }}"
                            required
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                        >
                        @error('display_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="professional_title" class="block text-xs font-semibold text-slate-700">Professional Title</label>
                        <input
                            id="professional_title"
                            type="text"
                            name="professional_title"
                            value="{{ old('professional_title', $profile->professional_title) }}"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                        >
                    </div>
                </div>

                <div>
                    <label for="qualifications" class="block text-xs font-semibold text-slate-700">Academic &amp; Professional Qualifications</label>
                    <input
                        id="qualifications"
                        type="text"
                        name="qualifications"
                        value="{{ old('qualifications', $profile->qualifications) }}"
                        placeholder="PhD (Hydrology), BEM P.Eng, IEM"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="short_bio" class="block text-xs font-semibold text-slate-700">Short Summary Bio</label>
                    <input
                        id="short_bio"
                        type="text"
                        name="short_bio"
                        value="{{ old('short_bio', $profile->short_bio) }}"
                        placeholder="1-sentence summary shown on session cards"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="specialisations_text" class="block text-xs font-semibold text-slate-700">Domain Specialisations (Comma-Separated)</label>
                    <input
                        id="specialisations_text"
                        type="text"
                        name="specialisations_text"
                        value="{{ old('specialisations_text', is_array($profile->specialisations) ? implode(', ', $profile->specialisations) : '') }}"
                        placeholder="Flood Forecasting, HEC-RAS 2D, Urban Stormwater MSMA"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="biography" class="block text-xs font-semibold text-slate-700">Professional Biography</label>
                    <textarea
                        id="biography"
                        name="biography"
                        rows="5"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                    >{{ old('biography', $profile->biography) }}</textarea>
                </div>

                <div class="flex justify-end">
                    <x-eht.button type="submit" variant="primary">
                        Save Profile Changes
                    </x-eht.button>
                </div>
            </form>
        </x-eht.card>
    </div>
</x-layouts.instructor>
