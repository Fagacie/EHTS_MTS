<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        protected TwoFactorAuthenticationService $twoFactor,
    ) {}

    public function showSetup(Request $request): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdministrator()) {
            abort(403, 'Administrator role required for admin MFA setup.');
        }

        if ($user->hasConfirmedTwoFactor()) {
            return redirect()->route('two-factor.challenge');
        }

        $secret = $request->session()->get('two_factor.setup_secret');
        if (! is_string($secret) || $secret === '') {
            $secret = $this->twoFactor->generateSecretKey();
            $request->session()->put('two_factor.setup_secret', $secret);
        }

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'qrCodeDataUri' => $this->twoFactor->qrCodeDataUri($user, $secret),
        ]);
    }

    public function confirmSetup(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdministrator()) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = (string) $request->session()->get('two_factor.setup_secret', '');

        if ($secret === '' || ! $this->twoFactor->verifyCode($secret, $validated['code'])) {
            return back()->withErrors([
                'code' => 'The provided 6-digit authenticator code is invalid or expired.',
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor.setup_secret');
        $request->session()->put('auth.two_factor_verified', true);

        AuditLog::record(
            event: 'user.mfa_enabled',
            auditableType: 'users',
            auditableId: $user->id,
            actor: $user,
        );

        return redirect()
            ->intended(route('filament.admin.pages.dashboard'))
            ->with('recovery_codes', $recoveryCodes)
            ->with('status', 'Two-factor authentication has been enabled for your administrator account.');
    }

    public function showChallenge(Request $request): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdministrator()) {
            return redirect()->intended($user->defaultDashboardUrl());
        }

        if (! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('two-factor.setup');
        }

        if ($request->session()->get('auth.two_factor_verified', false)) {
            return redirect()->intended(route('filament.admin.pages.dashboard'));
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdministrator() || ! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('two-factor.setup');
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $code = trim((string) ($validated['code'] ?? ''));
        $recoveryCode = trim((string) ($validated['recovery_code'] ?? ''));

        if ($code === '' && $recoveryCode === '') {
            return back()->withErrors([
                'code' => 'Enter a 6-digit authenticator code or a recovery code.',
            ]);
        }

        $verified = false;
        $method = 'totp';

        if ($code !== '') {
            $verified = $this->twoFactor->verifyCode((string) $user->two_factor_secret, $code);
        } elseif ($recoveryCode !== '') {
            $method = 'recovery_code';
            $verified = $this->twoFactor->verifyAndConsumeRecoveryCode($user, $recoveryCode);
        }

        if (! $verified) {
            return back()->withErrors([
                $recoveryCode !== '' ? 'recovery_code' : 'code' => 'The authentication code could not be verified.',
            ]);
        }

        $request->session()->put('auth.two_factor_verified', true);

        AuditLog::record(
            event: 'user.mfa_verified',
            auditableType: 'users',
            auditableId: $user->id,
            newValues: ['method' => $method],
            actor: $user,
        );

        return redirect()->intended(route('filament.admin.pages.dashboard'));
    }
}
