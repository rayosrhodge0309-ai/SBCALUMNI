<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\PortalPasswordResetRequested;
use App\Support\GmailAddress;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class PortalPasswordResetController extends Controller
{
    private const RESET_CODE_MIN = 100000;
    private const RESET_CODE_MAX = 999999;

    public function create(): View
    {
        return view('portal.auth.forgot-password');
    }

    public function code(Request $request): View
    {
        return view('portal.auth.reset-password', [
            'token' => '',
            'email' => GmailAddress::normalize($request->query('email', '')),
            'usesResetCode' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', GmailAddress::validationRule()],
        ]);

        $email = GmailAddress::normalize($validated['email']);
        $user = $this->findApprovedAlumniUser($email);

        if (! $user) {
            return redirect()
                ->route('portal.password.code', ['email' => $email])
                ->with('status', 'If this Gmail has an approved alumni portal account, we sent a password reset code.');
        }

        if ($this->resetCodeRecentlyCreated($email)) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'email' => 'Please wait before requesting another password reset code.',
                ]);
        }

        try {
            $resetCode = (string) random_int(self::RESET_CODE_MIN, self::RESET_CODE_MAX);
            $this->storeResetToken($email, $resetCode);

            $user->notify(new PortalPasswordResetRequested(
                $resetCode,
                $this->buildResetUrl($request, $user, $resetCode)
            ));
        } catch (Throwable $exception) {
            Log::error('Failed to send alumni portal password reset code.', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            $this->deleteResetToken($email);

            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'email' => 'We could not send the reset code right now. Please try again shortly.',
                ]);
        }

        return redirect()
            ->route('portal.password.code', ['email' => $email])
            ->with('status', 'We sent a 6-digit password reset code to your Gmail. Enter it below to create a new password.');
    }

    public function edit(Request $request, string $token): View|RedirectResponse
    {
        $email = GmailAddress::normalize($request->query('email', ''));

        if ($email !== '' && ! GmailAddress::isAllowed($email)) {
            return redirect()
                ->route('portal.password.request')
                ->withErrors([
                    'email' => GmailAddress::message(),
                ]);
        }

        return view('portal.auth.reset-password', [
            'token' => $token,
            'email' => $email,
            'usesResetCode' => false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $submittedToken = trim((string) ($request->input('reset_code') ?: $request->input('token')));

        $validated = $request->validate([
            'token' => ['nullable', 'string'],
            'reset_code' => ['nullable', 'digits:6'],
            'email' => ['required', 'email', 'max:255', GmailAddress::validationRule()],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'reset_code.digits' => 'Enter the 6-digit reset code from your Gmail.',
            'password.confirmed' => 'Password and Confirm Password must match.',
            'password.min' => 'Password must be at least :min characters.',
            'password.letters' => 'Password must include at least one letter.',
            'password.numbers' => 'Password must include at least one number.',
        ]);

        if ($submittedToken === '') {
            return back()
                ->withInput(['email' => $request->input('email')])
                ->withErrors([
                    'reset_code' => 'Enter the 6-digit reset code from your Gmail.',
                ]);
        }

        $email = GmailAddress::normalize($validated['email']);

        $status = PasswordBroker::broker()->reset([
            ...$this->alumniResetCredentials($email),
            'token' => $submittedToken,
            'password' => $validated['password'],
            'password_confirmation' => $request->input('password_confirmation'),
        ], function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status === PasswordBroker::PASSWORD_RESET) {
            return redirect()
                ->route('portal.login')
                ->with('success', 'Your password has been reset. You can now log in with your new password.');
        }

        return back()
            ->withInput(['email' => $email])
            ->withErrors([
                'reset_code' => $status === PasswordBroker::INVALID_TOKEN
                    ? 'This reset code is invalid or expired. Please request a new code.'
                    : 'We could not reset the password for this alumni Gmail account.',
            ]);
    }

    private function findApprovedAlumniUser(string $email): ?User
    {
        return User::query()
            ->where($this->alumniResetCredentials($email))
            ->first();
    }

    private function resetCodeRecentlyCreated(string $email): bool
    {
        $record = DB::table($this->resetTokenTable())
            ->where('email', $email)
            ->first();

        if (! $record?->created_at) {
            return false;
        }

        return Carbon::parse($record->created_at)
            ->addSeconds((int) config('auth.passwords.users.throttle', 60))
            ->isFuture();
    }

    private function storeResetToken(string $email, string $token): void
    {
        DB::table($this->resetTokenTable())->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );
    }

    private function deleteResetToken(string $email): void
    {
        DB::table($this->resetTokenTable())
            ->where('email', $email)
            ->delete();
    }

    private function resetTokenTable(): string
    {
        return (string) config('auth.passwords.users.table', 'password_reset_tokens');
    }

    /**
     * @return array<string, string>
     */
    private function alumniResetCredentials(string $email): array
    {
        return [
            'email' => $email,
            'role' => 'alumni',
            'account_status' => 'approved',
        ];
    }

    private function buildResetUrl(Request $request, User $user, string $token): string
    {
        $configuredUrl = rtrim((string) config('app.url'), '/');
        $configuredPath = parse_url($configuredUrl, PHP_URL_PATH) ?: '';
        $requestRoot = rtrim($request->root(), '/');
        $requestHost = $request->getHost();
        $localHosts = ['localhost', '127.0.0.1', '::1'];

        if (in_array($requestHost, $localHosts, true) && $configuredUrl !== '') {
            $root = $configuredUrl;
        } else {
            $root = $requestRoot;
            $requestPath = parse_url($requestRoot, PHP_URL_PATH) ?: '';

            if ($configuredPath !== '' && $configuredPath !== '/' && ! str_ends_with($requestPath, $configuredPath)) {
                $root = rtrim($request->getSchemeAndHttpHost(), '/').$configuredPath;
            }
        }

        return rtrim($root, '/').route('portal.password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false);
    }
}
