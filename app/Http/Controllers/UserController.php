<?php

namespace App\Http\Controllers;

use App\Classes\FirebaseService;
use App\Models\User;
use App\Notifications\AlumniAccountApproved;
use App\Services\LinkedAccountSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_id' => $user->alumni?->student_id_display,
                    'student_name' => $user->alumni?->full_name,
                    'academic_label' => $user->alumni?->academic_label,
                    'submitted_date' => $user->created_at?->format('F d, Y'),
                    'submitted_time' => $user->created_at?->format('h:i A'),
                    'approve_url' => route('users.approve', $user),
                    'reject_url' => route('users.reject', $user),
                    'review_url' => route('users.edit', $user),
                ];
            })->values(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'managedUser' => $user->load('alumni'),
        ]);
    }

    public function update(Request $request, User $user, LinkedAccountSyncService $syncService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('alumni', 'email')->ignore($user->alumni_id),
            ],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $syncService->syncAlumniFromUser($user->fresh(['alumni']));

        return redirect()->route('users.index')->with('success', 'User account updated successfully.');
    }

    public function approve(User $user, LinkedAccountSyncService $syncService, FirebaseService $firebase): RedirectResponse
    {
        abort_unless($user->isAlumni(), 404);

        $wasApproved = $user->isApproved();

        $updates = [
            'account_status' => 'approved',
            'approved_at' => now(),
            'portal_otp_verified_at' => now(),
        ];

        $user->forceFill($updates)->save();

        $syncService->syncAlumniFromUser($user->fresh(['alumni']));

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
