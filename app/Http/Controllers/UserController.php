<?php

namespace App\Http\Controllers;

use App\Classes\FirebaseService;
use App\Models\User;
use App\Notifications\AlumniAccountApproved;
use App\Services\AlumniAccountReviewService;
use App\Services\LinkedAccountSyncService;
use App\Support\GmailAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->with('alumni')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%")
                        ->orWhere('account_status', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function pending(): View
    {
        $pendingUsers = User::query()
            ->with('alumni')
            ->where('role', 'alumni')
            ->where('account_status', 'pending')
            ->latest()
            ->paginate(10);

        return view('users.pending', [
            'pendingUsers' => $pendingUsers,
        ]);
    }

    public function pendingNotifications(Request $request): JsonResponse
    {
        $latestSeenId = max(0, (int) $request->query('after', 0));

        $recentPendingUsers = User::query()
            ->with('alumni')
            ->where('role', 'alumni')
            ->where('account_status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        $newPendingUsers = $recentPendingUsers
            ->filter(fn (User $user): bool => $user->id > $latestSeenId)
            ->sortBy('id')
            ->values();

        return response()->json([
            'count' => User::query()
                ->where('role', 'alumni')
                ->where('account_status', 'pending')
                ->count(),
            'latest_id' => (int) ($recentPendingUsers->max('id') ?? 0),
            'new' => $newPendingUsers->map(function (User $user): array {
                $profile = $user->registration_profile;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_id' => $profile->student_id_display,
                    'student_name' => $profile->full_name,
                    'maiden_name' => $profile->maiden_name,
                    'academic_label' => $profile->academic_label,
                    'submitted_date' => $user->created_at?->format('F d, Y'),
                    'submitted_time' => $user->created_at?->format('h:i A'),
                    'approve_url' => route('users.approve', $user),
                    'reject_url' => route('users.reject', $user),
                    'review_url' => route('users.edit', $user),
                ];
            })->values(),
        ]);
    }

    public function edit(Request $request, User $user, AlumniAccountReviewService $reviewService): View
    {
        $recordSearch = trim((string) $request->query('record_search', ''));
        $matchingRecords = collect();
        $recordMatchError = null;

        if ($user->isAlumni()) {
            try {
                $matchingRecords = $reviewService->matchingRecords($user, $recordSearch);
            } catch (Throwable $exception) {
                Log::error('Unable to load alumni record matches during account review.', [
                    'user_id' => $user->id,
                    'exception' => $exception,
                ]);

                $recordMatchError = 'School-record matching is temporarily unavailable. You can still review the submitted account details.';
            }
        }

        return view('users.edit', [
            'managedUser' => $user->load('alumni'),
            'matchingRecords' => $matchingRecords,
            'recordSearch' => $recordSearch,
            'recordMatchError' => $recordMatchError,
        ]);
    }

    public function addToAlumniRecords(Request $request, User $user, AlumniAccountReviewService $reviewService): RedirectResponse
    {
        abort_unless($user->isAlumni(), 404);

        $validated = $request->validate([
            'record_choice' => ['required', Rule::in(['existing', 'new'])],
            'alumni_record_id' => ['required_if:record_choice,existing', 'nullable', 'integer', Rule::exists('alumni', 'id')],
        ]);

        $reviewService->addToRecords($user, $validated['record_choice'] === 'existing' ? (int) $validated['alumni_record_id'] : null);

        return redirect()->route('users.edit', $user)->with('success', 'Account added to Alumni Records. The login account remains in User Accounts.');
    }

    public function update(Request $request, User $user, LinkedAccountSyncService $syncService): RedirectResponse
    {
        $emailRules = [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user->id),
            Rule::unique('alumni', 'email')->ignore($user->alumni_id),
        ];

        if ($user->isAlumni()) {
            $emailRules[] = GmailAddress::validationRule();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => $emailRules,
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $newEmail = $user->isAlumni()
            ? GmailAddress::normalize($validated['email'])
            : $validated['email'];
        $emailChanged = GmailAddress::normalize($user->email) !== GmailAddress::normalize($newEmail);

        $user->name = $validated['name'];
        $user->email = $newEmail;

        if ($user->isAlumni() && $emailChanged) {
            $user->portal_otp_verified_at = null;
        }

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $syncService->syncAlumniFromUser($user->fresh(['alumni']));

        return redirect()->route('users.index')->with('success', 'User account updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()->route('users.index')
                ->with('warning', 'You cannot delete the account you are currently signed in to.');
        }

        DB::transaction(function () use ($user): void {
            PasswordBroker::broker()->deleteToken($user);
            // Delete login access only; keep the linked alumni record and its history.
            $user->delete();
        });

        $response = redirect()->route('users.index')
            ->with('success', 'User account deleted successfully. Any linked alumni record has been kept.');

        if ($user->profile_photo_path) {
            try {
                $photoDeleted = Storage::disk('public')->delete($user->profile_photo_path);
            } catch (Throwable $exception) {
                Log::warning('Unable to remove deleted account profile photo.', [
                    'user_id' => $user->id,
                    'exception' => $exception,
                ]);
                $photoDeleted = false;
            }

            if (! $photoDeleted) {
                $response->with('warning', 'The account was deleted, but its profile picture could not be removed from storage.');
            }
        }

        return $response;
    }

    public function approve(User $user, FirebaseService $firebase): RedirectResponse
    {
        abort_unless($user->isAlumni(), 404);

        $wasApproved = $user->isApproved();

        $updates = [
            'account_status' => 'approved',
            'approved_at' => now(),
        ];

        if (! $wasApproved) {
            $updates['portal_otp_verified_at'] = null;
        }

        $user->forceFill($updates)->save();

        if (! $wasApproved) {
            try {
                $user->notify(new AlumniAccountApproved);
                $firebase->sendToUser(
                    $user,
                    'Account approved',
                    'Your alumni portal account has been approved.',
                    route('portal.login'),
                    ['kind' => 'alumni_account_approved']
                );
            } catch (Throwable $exception) {
                Log::error('Failed to notify alumni account approval.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'exception' => $exception,
                ]);

                return redirect()->back()->with('warning', 'The alumni account has been approved, but the email notification could not be sent.');
            }
        }

        return redirect()->back()->with('success', 'The alumni account has been approved and the alumni was notified.');
    }

    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->isAlumni(), 404);

        $user->forceFill([
            'account_status' => 'rejected',
            'approved_at' => null,
            'portal_otp_verified_at' => null,
        ])->save();

        return redirect()->back()->with('warning', 'The alumni account request has been rejected.');
    }
}
