<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\User;
use App\Support\StudentIdFormatter;
use App\Services\LinkedAccountSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isAlumni()) {
            return redirect()->route('portal.dashboard');
        }

        if ($request->boolean('switch') && $user) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login');
        }

        return view('portal.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
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

        return redirect()->route('portal.otp.create');
    }

    public function register(): View
    {
        return view('portal.auth.register');
    }

    public function saveRegistration(Request $request, LinkedAccountSyncService $syncService): RedirectResponse
    {
        $request->merge([
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ]);

        $submittedStudentId = trim((string) $request->input('student_id', ''));
        $studentIdLookup = $this->normalizeStudentId($submittedStudentId);
        $existingAlumnus = $studentIdLookup !== ''
            ? Alumni::query()
                ->where(function ($query) use ($studentIdLookup, $submittedStudentId) {
                    foreach (StudentIdFormatter::variants($studentIdLookup) as $variant) {
                        $query->orWhere('student_id', $variant);
                    }

                    if ($submittedStudentId !== $studentIdLookup) {
                        $query->orWhere('student_id', $submittedStudentId);
                    }
                })
                ->first()
            : null;

        $validated = $request->validate([
            'student_id' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birthday' => 'nullable|date|before_or_equal:today',
            'education_level' => [$existingAlumnus ? 'nullable' : 'required', 'string', 'max:100'],
            'course' => [$existingAlumnus ? 'nullable' : 'required', 'string', 'max:150'],
            'year_graduated' => 'required|integer|min:1900|max:'.(now()->year + 1),
            'email' => 'required|email|max:255',
            'contact_number' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'Password and Confirm Password must match.',
            'password.min' => 'Password must be at least :min characters.',
            'password.letters' => 'Password must include at least one letter.',
            'password.numbers' => 'Password must include at least one number.',
        ]);

        $normalizedEmail = Str::lower(trim($validated['email']));
        $alumniEmailInUse = Alumni::query()
            ->where('email', $normalizedEmail)
            ->when($existingAlumnus, fn ($query) => $query->where('id', '!=', $existingAlumnus->id))
            ->exists();

        if ($alumniEmailInUse) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => 'That email is already linked to another alumni record. Please contact the administrator.',
                ]);
        }

        if ($existingAlumnus) {
            $matchesExistingRecord = Str::lower(trim((string) $existingAlumnus->first_name)) === Str::lower(trim($validated['first_name']))
                && Str::lower(trim((string) $existingAlumnus->last_name)) === Str::lower(trim($validated['last_name']))
                && (int) $existingAlumnus->year_graduated === (int) $validated['year_graduated'];

            if (! $matchesExistingRecord) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'student_id' => 'This student ID already exists, but the name or graduation year does not match. Please contact the administrator for verification.',
                    ]);
            }

            $existingUser = $existingAlumnus->user()->first();

            if ($existingUser && ! $existingUser->isAlumni()) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'student_id' => 'This alumni record is linked to a non-portal account. Please contact the school administrator.',
                    ]);
            }

            if ($existingUser?->isApproved()) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'email' => 'This alumni account is already active. Please use the login page instead.',
                    ]);
            }

            $emailUsedByAnotherUser = User::query()
                ->where('email', $normalizedEmail)
                ->when($existingUser, fn ($query) => $query->where('id', '!=', $existingUser->id))
                ->exists();

            if ($emailUsedByAnotherUser) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'email' => 'That email is already used by another account. Please contact the school administrator.',
                    ]);
            }

            $existingAlumnus->update([
                'email' => $normalizedEmail,
                'contact_number' => $validated['contact_number'] ?? $existingAlumnus->contact_number,
                'address' => $validated['address'] ?? $existingAlumnus->address,
                'birthday' => $validated['birthday'] ?? $existingAlumnus->birthday,
                'education_level' => $validated['education_level'] ?? $existingAlumnus->education_level,
                'course' => $validated['course'] ?? $existingAlumnus->course,
            ]);

            $syncService->syncOrCreateUserFromAlumni($existingAlumnus->fresh(), true);
            $portalUser = $existingAlumnus->fresh(['user'])->user;

            if (! $portalUser) {
                return back()
                    ->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors([
                        'email' => 'We could not create your alumni portal account right now. Please contact the administrator.',
                    ]);
            }

            Auth::login($portalUser);
            $request->session()->regenerate();
            PortalOtpController::resetSessionState($request);

            return redirect()
                ->route('portal.otp.create')
                ->with('success', 'Your alumni record has been verified. Please check your email for the OTP.');
        }

        $emailUsedByAnotherUser = User::query()
            ->where('email', $normalizedEmail)
            ->exists();

        if ($emailUsedByAnotherUser) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => 'That email is already used by another account. Please contact the school administrator.',
                ]);
        }

        $alumnus = Alumni::create([
            'student_id' => $submittedStudentId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'birthday' => $validated['birthday'] ?? null,
            'education_level' => $validated['education_level'],
            'course' => $validated['course'],
            'year_graduated' => $validated['year_graduated'],
            'email' => $normalizedEmail,
            'contact_number' => $validated['contact_number'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        User::create([
            'name' => $alumnus->full_name,
            'email' => $normalizedEmail,
            'password' => Hash::make($validated['password']),
            'role' => 'alumni',
            'account_status' => 'pending',
            'approved_at' => null,
            'alumni_id' => $alumnus->id,
        ]);

        return redirect()
            ->route('portal.login')
            ->with('success', 'Your account request has been submitted. Please wait for admin approval before logging in.');
    }

    private function normalizeStudentId(string $studentId): string
    {
        return StudentIdFormatter::normalize($studentId);
    }
}
