<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\PortalPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticatedSessionController extends Controller
{
    public function create(): InertiaResponse
    {
        return Inertia::render('Auth/Login', [
            'submitUrl' => route('login.store'),
            'forgotPasswordUrl' => route('password.request'),
            'registerUrl' => route('register'),
        ]);
    }

    public function store(Request $request): RedirectResponse|SymfonyResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            $statusLabel = $user->status->label();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => "Your account is currently {$statusLabel}. Please contact Ecohydrotech administration.",
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('auth.two_factor_verified');

        $user->forceFill(['last_login_at' => now()])->save();

        AuditLog::record(
            event: 'user.logged_in',
            auditableType: 'users',
            auditableId: $user->id,
            actor: $user,
        );

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->isAdministrator()) {
            return redirect()->route(
                $user->hasConfirmedTwoFactor() ? 'two-factor.challenge' : 'two-factor.setup',
            );
        }

        return PortalPresenter::inertiaSafeRedirect(
            $request,
            redirect()->intended($user->defaultDashboardUrl()),
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            AuditLog::record(
                event: 'user.logged_out',
                auditableType: 'users',
                auditableId: $user->id,
                actor: $user,
            );
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
