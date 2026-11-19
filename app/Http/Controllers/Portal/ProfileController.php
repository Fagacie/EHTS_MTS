<?php

namespace App\Http\Controllers\Portal;

use App\Enums\CustomerType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user()->load(['roles', 'customerProfile']);

        $profile = $user->customerProfile ?? new CustomerProfile([
            'user_id' => $user->id,
            'customer_type' => CustomerType::Individual,
            'billing_country' => 'MY',
        ]);

        return view('portal.profile', [
            'user' => $user,
            'profile' => $profile,
        ]);
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
            'customer_type' => ['required', 'string', Rule::in(['individual', 'company'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'required_if:customer_type,company', 'string', 'max:200'],
            'company_registration_no' => ['nullable', 'string', 'max:50'],
            'billing_contact_name' => ['nullable', 'string', 'max:150'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_phone' => ['nullable', 'string', 'max:30'],
            'billing_address_line_1' => ['nullable', 'string', 'max:255'],
            'billing_address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_state' => ['nullable', 'string', 'max:100'],
            'billing_postcode' => ['nullable', 'string', 'max:20'],
            'billing_country' => ['nullable', 'string', 'size:2'],
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $emailChanged = $user->email !== $validated['email'];

            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }

            $profileData = [
                'customer_type' => $validated['customer_type'],
                'phone' => $validated['phone'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'company_registration_no' => $validated['company_registration_no'] ?? null,
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
                    'customer_type' => $profileData['customer_type'],
                    'company_name' => $profileData['company_name'],
                ],
                actor: $user,
            );
        });

        return redirect()
            ->route('portal.profile.edit')
            ->with('status', 'Your profile and billing information have been updated.');
    }
}
