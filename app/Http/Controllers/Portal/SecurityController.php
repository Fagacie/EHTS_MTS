<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Notification\EtmsNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.security', [
            'user' => $request->user()->load('roles'),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        AuditLog::record(
            event: 'user.password_updated',
            auditableType: 'users',
            auditableId: $user->id,
            actor: $user,
        );

        app(EtmsNotificationService::class)->notifyUser(
            user: $user,
            eventType: 'user.password_updated',
            title: 'Account Password Updated',
            message: 'Your password was updated from your security settings. Contact our team immediately if you did not authorise this change.',
            actionUrl: route('portal.security.edit', [], false),
            actionLabel: 'Review Security Settings',
            details: [
                'Account Email' => $user->email,
                'Updated At' => now('Asia/Kuala_Lumpur')->format('d M Y, h:i A').' MYT',
            ],
        );

        return back()->with('status', 'Your password has been updated.');
    }
}
