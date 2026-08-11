<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Illuminate\View\View;

class PortalOtpController extends Controller
{
    private const SESSION_VERIFIED_USER_ID = 'portal_otp_verified_user_id';
    private const SESSION_CODE_HASH = 'portal_otp_code_hash';
    private const SESSION_CODE_EXPIRES_AT = 'portal_otp_code_expires_at';
    private const SESSION_ATTEMPTS = 'portal_otp_attempts';
    private const SESSION_LAST_SENT_AT = 'portal_otp_last_sent_at';
    private const OTP_TTL_MINUTES = 10;
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_RESEND_COOLDOWN_SECONDS = 30;

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAlumni()) {
            abort(403);
        }

        if ($this->isVerifiedForCurrentUser($request)) {
            return redirect()->route('portal.dashboard');
        }

        $deliveryError = false;
        if (! $this->hasActiveCode($request)) {
            $deliveryError = ! $this->issueCode($request);
        }

        return view('portal.auth.verify-otp', [
            'email' => $this->maskEmail((string) $user->email),
            'resendCooldownSeconds' => $this->resendCooldownSeconds($request),
            'deliveryError' => $deliveryError,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAlumni()) {
            abort(403);
        }

        if ($this->isVerifiedForCurrentUser($request)) {
            return redirect()->route('portal.dashboard');
        }

        $validated = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        if (! $this->hasActiveCode($request)) {
            if (! $this->issueCode($request)) {
                return back()->withErrors([
                    'otp' => 'Unable to send OTP email right now. Please try again shortly.',
                ]);
            }

            return back()->withErrors([
                'otp' => 'Your OTP has expired. A new code was sent to your email.',
            ]);
        }

        $attempts = (int) $request->session()->get(self::SESSION_ATTEMPTS, 0);
        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            if (! $this->issueCode($request)) {
                return back()->withErrors([
                    'otp' => 'Unable to send OTP email right now. Please try again shortly.',
                ]);
            }

            return back()->withErrors([
                'otp' => 'Too many incorrect attempts. We sent a new OTP to your email.',
            ]);
        }

        $hash = (string) $request->session()->get(self::SESSION_CODE_HASH, '');
        $otp = (string) $validated['otp'];

        if ($hash === '' || ! Hash::check($otp, $hash)) {
            $request->session()->put(self::SESSION_ATTEMPTS, $attempts + 1);

            return back()->withErrors([
                'otp' => 'Invalid OTP code. Please try again.',
            ]);
        }

        $request->session()->put(self::SESSION_VERIFIED_USER_ID, $user->id);

        $this->clearOtpChallenge($request);

        return redirect()->route('portal.dashboard')->with('success', 'OTP verified. Welcome to your dashboard.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isAlumni()) {
            abort(403);
        }

        if ($this->resendCooldownSeconds($request) > 0) {
            return back()->withErrors([
                'otp' => 'Please wait a few seconds before requesting another OTP.',
            ]);
        }

        if (! $this->issueCode($request)) {
            return back()->withErrors([
                'otp' => 'Unable to send OTP email right now. Please try again shortly.',
            ]);
        }

        return back()->with('success', 'A new OTP was sent to your email.');
    }

    public static function resetSessionState(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_VERIFIED_USER_ID,
            self::SESSION_CODE_HASH,
            self::SESSION_CODE_EXPIRES_AT,
            self::SESSION_ATTEMPTS,
            self::SESSION_LAST_SENT_AT,
        ]);
    }

    private function issueCode(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        $otp = (string) random_int(100000, 999999);

        $request->session()->put(self::SESSION_CODE_HASH, Hash::make($otp));
        $request->session()->put(self::SESSION_CODE_EXPIRES_AT, now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp);
        $request->session()->put(self::SESSION_ATTEMPTS, 0);
        $request->session()->put(self::SESSION_LAST_SENT_AT, now()->timestamp);

        try {
            Mail::raw(
                "Your Alumni Portal OTP is {$otp}. It expires in ".self::OTP_TTL_MINUTES.' minutes.',
                function ($message) use ($user): void {
                    $message->to($user->email)->subject('Alumni Portal OTP Verification');
                }
            );
        } catch (Throwable $exception) {
            Log::error('Failed to deliver alumni OTP email.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    private function hasActiveCode(Request $request): bool
    {
        $expiresAt = (int) $request->session()->get(self::SESSION_CODE_EXPIRES_AT, 0);
        $hash = (string) $request->session()->get(self::SESSION_CODE_HASH, '');

        return $hash !== '' && $expiresAt > now()->timestamp;
    }

    private function isVerifiedForCurrentUser(Request $request): bool
    {
        return (int) $request->session()->get(self::SESSION_VERIFIED_USER_ID, 0) === (int) $request->user()?->id;
    }

    private function clearOtpChallenge(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_CODE_HASH,
            self::SESSION_CODE_EXPIRES_AT,
            self::SESSION_ATTEMPTS,
            self::SESSION_LAST_SENT_AT,
        ]);
    }

    private function resendCooldownSeconds(Request $request): int
    {
        $lastSentAt = (int) $request->session()->get(self::SESSION_LAST_SENT_AT, 0);
        if ($lastSentAt === 0) {
            return 0;
        }

        $elapsed = now()->timestamp - $lastSentAt;

        return max(0, self::OTP_RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        if (strlen($local) <= 2) {
            return str_repeat('*', strlen($local)).'@'.$domain;
        }

        return substr($local, 0, 2).str_repeat('*', max(1, strlen($local) - 2)).'@'.$domain;
    }
}
