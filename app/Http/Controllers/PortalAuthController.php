<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortalRegistrationRequest;
use App\Models\AcademicProgram;
use App\Models\User;
use App\Support\GmailAddress;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($request->boolean('switch') && $user?->isAlumni()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login');
        }

        if ($user?->isAlumni()) {
            return redirect()->route('portal.dashboard');
        }

        if ($user?->isAdmin()) {
            return redirect()->route('dashboard');
        }

        return response()->view('portal.auth.login')
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials['email'] = GmailAddress::normalize($credentials['email']);
        $request->merge(['email' => $credentials['email']]);

        $existingUser = User::query()->where('email', $credentials['email'])->first();

        if ($existingUser?->isAlumni() && ! GmailAddress::isAllowed($credentials['email'])) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => GmailAddress::message(),
                ]);
        }

        if (! Auth::attempt($credentials, false)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ]);
        }

        $request->session()->regenerate();
        PortalOtpController::resetSessionState($request);

        $user = $request->user();

        if ($user?->isAdmin()) {
            return redirect()->intended(route('dashboard'));
        }

        if ($user?->isAlumni() && ! GmailAddress::isAllowed($user->email)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => GmailAddress::message(),
                ]);
        }

        if ($user?->isAlumni() && ! $user->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => $user->isPendingApproval()
                        ? 'Your account request is still pending admin approval.'
                        : 'Your account request was not approved yet. Please contact the administrator.',
                ]);
        }

        if (! $user?->isAlumni()) {
            Auth::logout();

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'This account role is not allowed to sign in here.',
                ]);
        }

        if (! $user->hasCompletedPortalOtp()) {
            return redirect()->route('portal.otp.create');
        }

        return redirect()->intended(route('portal.dashboard'));
    }

    public function register(): View
    {
        return view('portal.auth.register', [
            'programsByLevel' => AcademicProgram::groupedByLevel(),
            'registrationToday' => now()->toDateString(),
            'maximumGraduationYear' => now()->year + 1,
        ]);
    }

    public function saveRegistration(PortalRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $details = $request->safe()->only([
            'student_id', 'first_name', 'middle_name', 'last_name', 'maiden_name',
            'birthday', 'education_level', 'course', 'year_graduated', 'contact_number', 'address',
        ]);
        $details['year_graduated'] = (int) $validated['year_graduated'];
        $fullName = implode(' ', array_filter([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
        ], static fn (?string $part): bool => filled($part)));

        try {
            User::create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'alumni',
                'account_status' => 'pending',
                'approved_at' => null,
                'portal_otp_verified_at' => null,
                'alumni_id' => null,
                'registration_details' => $details,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            // Another submission can reserve this email after validation succeeds.
            if (! User::query()->where('email', $validated['email'])->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'email' => PortalRegistrationRequest::EMAIL_IN_USE_MESSAGE,
            ]);
        }

        return redirect()->route('portal.login')->with('success', 'Your account request has been submitted. The administrator will compare your details with the school alumni records before approving access. After approval, use your Gmail and password to log in and verify your Gmail with a one-time OTP.');
    }
}
