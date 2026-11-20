<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\PortalPresenter;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse|InertiaResponse|SymfonyResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return PortalPresenter::inertiaSafeRedirect(
                $request,
                redirect()->intended($user->defaultDashboardUrl()),
            );
        }

        return Inertia::render('Auth/VerifyEmail', [
            'email' => $user->email,
            'resendUrl' => route('verification.send'),
            'logoutUrl' => route('logout'),
        ]);
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

    public function resend(Request $request): RedirectResponse|SymfonyResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return PortalPresenter::inertiaSafeRedirect(
                $request,
                redirect()->intended($user->defaultDashboardUrl()),
            );
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }
}
