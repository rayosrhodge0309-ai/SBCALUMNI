<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LinkedAccountSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

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

    public function approve(User $user, LinkedAccountSyncService $syncService): RedirectResponse
    {
        abort_unless($user->isAlumni(), 404);

        $user->forceFill([
            'account_status' => 'approved',
            'approved_at' => now(),
        ])->save();

        $syncService->syncAlumniFromUser($user->fresh(['alumni']));

        return redirect()->back()->with('success', 'The alumni account has been approved.');
    }

    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->isAlumni(), 404);

        $user->forceFill([
            'account_status' => 'rejected',
            'approved_at' => null,
        ])->save();

        return redirect()->back()->with('warning', 'The alumni account request has been rejected.');
    }
}
