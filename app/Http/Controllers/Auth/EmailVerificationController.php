<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->defaultDashboardUrl());
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $request->fulfill();

            AuditLog::record(
                event: 'user.email_verified',
                auditableType: 'users',
                auditableId: $user->id,
                actor: $user,
            );
        }

        if ($user->isAdministrator()) {
            return redirect()->route(
                $user->hasConfirmedTwoFactor() ? 'two-factor.challenge' : 'two-factor.setup',
            )->with('status', 'Email address verified. Complete administrator MFA to continue.');
        }

        return redirect()
            ->intended($user->defaultDashboardUrl())
            ->with('status', 'Your email address has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->defaultDashboardUrl());
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }
}
