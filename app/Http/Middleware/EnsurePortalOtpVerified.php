<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalOtpVerified
{
    private const SESSION_VERIFIED_USER_ID = 'portal_otp_verified_user_id';

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAlumni()) {
            return $next($request);
        }

        if ($user->isApproved() || $user->hasCompletedPortalOtp()) {
            return $next($request);
        }

        $verifiedUserId = (int) $request->session()->get(self::SESSION_VERIFIED_USER_ID, 0);
        if ($verifiedUserId !== (int) $user->id) {
            return redirect()->route('portal.otp.create');
        }

        return $next($request);
    }
}
