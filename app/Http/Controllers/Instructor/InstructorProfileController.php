<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InstructorProfile;
use App\Support\InstructorPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InstructorProfileController extends Controller
{
    public function edit(Request $request): InertiaResponse
    {
        $user = $request->user();
        $profile = InstructorProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'photo_storage_disk' => 'public',
                'is_public' => false,
            ],
        );

        return Inertia::render(
            'Instructor/Profile',
            InstructorPresenter::profileData($user, $profile),
        );
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:150'],
            'professional_title' => ['nullable', 'string', 'max:150'],
            'qualifications' => ['nullable', 'string', 'max:255'],
            'short_bio' => ['nullable', 'string', 'max:500'],
            'specialisations_text' => ['nullable', 'string', 'max:500'],
            'biography' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $specialisations = collect(explode(',', (string) ($validated['specialisations_text'] ?? '')))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();

        $profile = InstructorProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $validated['display_name'],
                'professional_title' => $validated['professional_title'] ?? null,
                'qualifications' => $validated['qualifications'] ?? null,
                'short_bio' => $validated['short_bio'] ?? null,
                'specialisations' => $specialisations,
                'biography' => $validated['biography'] ?? null,
                'updated_by' => $user->id,
            ],
        );

        if ($request->hasFile('photo')) {
            $oldKey = $profile->photo_storage_key;
            $oldUserKey = $user->photo_storage_key;
            $storedPhoto = $request->file('photo')->store('instructors/photos', 'public');
            $profile->update([
                'photo_storage_disk' => 'public',
                'photo_storage_key' => $storedPhoto,
            ]);
            $user->update([
                'photo_storage_disk' => 'public',
                'photo_storage_key' => $storedPhoto,
            ]);
            if ($oldKey && $oldKey !== $profile->photo_storage_key) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldKey);
            }
            if ($oldUserKey && $oldUserKey !== $storedPhoto) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldUserKey);
            }
        }

        AuditLog::record(
            event: 'instructor_profile.updated',
            auditableType: InstructorProfile::class,
            auditableId: $profile->id,
            oldValues: null,
            newValues: ['display_name' => $profile->display_name],
            reason: 'Instructor updated professional biography and profile details.',
            actor: $user,
        );

        return redirect()
            ->route('instructor.profile.edit')
            ->with('status', 'Instructor profile updated successfully.');
    }
}
