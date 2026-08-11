<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LinkedAccountSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.partial.edit');
    }

    public function photo(User $user): Response
    {
        $path = trim((string) $user->profile_photo_path);

        if ($path === '') {
            abort(404);
        }

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'public, max-age=86400',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function update(Request $request, LinkedAccountSyncService $syncService): RedirectResponse
    {
        $user = $request->user();
        $linkedAlumniId = $user->isAlumni() ? $user->alumni_id : null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'remove_profile_photo' => ['nullable', 'boolean'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('alumni', 'email')->ignore($linkedAlumniId),
            ],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $user->save();

        $syncService->syncAlumniFromUser($user->fresh(['alumni']));

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully!');
    }
}
