<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RegisteredUserController extends Controller
{
    public function create(): InertiaResponse
    {
        return Inertia::render('Auth/Register', [
            'submitUrl' => route('register.store'),
            'loginUrl' => route('login'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
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
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'status' => UserStatus::Active->value,
            ]);

            $user->assignRole(RoleSlug::Customer);

            $user->customerProfile()->create([
                'customer_type' => 'individual',
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
                'billing_country' => 'MY',
            ]);

            AuditLog::record(
                event: 'user.registered',
                auditableType: 'users',
                auditableId: $user->id,
                newValues: [
                    'email' => $user->email,
                    'role' => RoleSlug::Customer->value,
                ],
                actor: $user,
            );

            return $user;
        });

        event(new Registered($user));

        app(EtmsNotificationService::class)->notifyUser(
            user: $user,
            eventType: 'user.registered',
            title: 'Welcome to Ecohydrotech Training Portal',
            message: 'Your customer account has been created. Please verify your email address to access bookings, downloads, and certificates.',
            actionUrl: route('portal.dashboard', [], false),
            actionLabel: 'Open Customer Portal',
            details: [
                'Account Name' => $user->name,
                'Email' => $user->email,
            ],
            sendEmail: false,
            sendInApp: true,
        );

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
