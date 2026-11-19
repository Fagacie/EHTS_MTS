<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministratorMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->isAdministrator()) {
            if (! $user->hasConfirmedTwoFactor()) {
                return redirect()->route('two-factor.setup');
            }

            if (! $request->session()->get('auth.two_factor_verified', false)) {
                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }
}
