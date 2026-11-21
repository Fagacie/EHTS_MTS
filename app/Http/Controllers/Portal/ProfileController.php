<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Support\PortalPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProfileController extends Controller
{
    public function edit(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user()->load(['roles', 'customerProfile']);

        $profile = $user->customerProfile ?? new CustomerProfile([
            'user_id' => $user->id,
            'billing_country' => 'MY',
        ]);

        return Inertia::render('Portal/Profile', PortalPresenter::profileData($user, $profile));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'billing_contact_name' => ['nullable', 'string', 'max:150'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_phone' => ['nullable', 'string', 'max:30'],
            'billing_address_line_1' => ['nullable', 'string', 'max:255'],
            'billing_address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_state' => ['nullable', 'string', 'max:100'],
            'billing_postcode' => ['nullable', 'string', 'max:20'],
            'billing_country' => ['nullable', 'string', 'size:2'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $user, $validated): void {
            $emailChanged = $user->email !== $validated['email'];

            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($request->hasFile('photo')) {
                $oldKey = $user->photo_storage_key;
                $user->photo_storage_disk = 'public';
                $user->photo_storage_key = $request->file('photo')->store('users/avatars', 'public');
                $user->save();
                if ($oldKey && $oldKey !== $user->photo_storage_key) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldKey);
                }
            }

            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }

            $profileData = [
                'phone' => $validated['phone'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'billing_contact_name' => $validated['billing_contact_name'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'billing_phone' => $validated['billing_phone'] ?? null,
                'billing_address_line_1' => $validated['billing_address_line_1'] ?? null,
                'billing_address_line_2' => $validated['billing_address_line_2'] ?? null,
                'billing_city' => $validated['billing_city'] ?? null,
                'billing_state' => $validated['billing_state'] ?? null,
                'billing_postcode' => $validated['billing_postcode'] ?? null,
                'billing_country' => strtoupper($validated['billing_country'] ?? 'MY'),
            ];

            $profile = $user->customerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData,
            );

            AuditLog::record(
                event: 'customer_profile.updated',
                auditableType: 'customer_profiles',
                auditableId: $profile->id,
                newValues: [
                    'name' => $user->name,
                ],
                actor: $user,
            );
        });

        return redirect()
            ->route('portal.profile.edit')
            ->with('status', 'Your profile and billing information have been updated.');
    }
}
