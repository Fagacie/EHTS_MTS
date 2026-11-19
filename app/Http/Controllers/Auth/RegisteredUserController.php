<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CustomerType;
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
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'customer_type' => ['nullable', 'string', Rule::in(['individual', 'company'])],
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
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $customerType = $validated['customer_type'] ?? CustomerType::Individual->value;

        $user = DB::transaction(function () use ($validated, $customerType): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'status' => UserStatus::Active->value,
            ]);

            $user->assignRole(RoleSlug::Customer);

            $user->customerProfile()->create([
                'customer_type' => $customerType,
                'phone' => $validated['phone'] ?? null,
                'job_title' => $validated['job_title'] ?? null,
                'company_name' => $customerType === 'company' ? ($validated['company_name'] ?? null) : null,
                'company_registration_no' => $customerType === 'company' ? ($validated['company_registration_no'] ?? null) : null,
                'billing_contact_name' => $customerType === 'company' ? ($validated['billing_contact_name'] ?? null) : null,
                'billing_email' => $customerType === 'company' ? ($validated['billing_email'] ?? null) : null,
                'billing_phone' => $customerType === 'company' ? ($validated['billing_phone'] ?? null) : null,
                'billing_address_line_1' => $customerType === 'company' ? ($validated['billing_address_line_1'] ?? null) : null,
                'billing_address_line_2' => $customerType === 'company' ? ($validated['billing_address_line_2'] ?? null) : null,
                'billing_city' => $customerType === 'company' ? ($validated['billing_city'] ?? null) : null,
                'billing_state' => $customerType === 'company' ? ($validated['billing_state'] ?? null) : null,
                'billing_postcode' => $customerType === 'company' ? ($validated['billing_postcode'] ?? null) : null,
                'billing_country' => 'MY',
            ]);

            AuditLog::record(
                event: 'user.registered',
                auditableType: 'users',
                auditableId: $user->id,
                newValues: [
                    'email' => $user->email,
                    'role' => RoleSlug::Customer->value,
                    'customer_type' => $customerType,
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
