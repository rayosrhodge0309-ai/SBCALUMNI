<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LinkedAccountSyncService
{
    public function syncUserFromAlumni(Alumni $alumnus): void
    {
        $alumnus->loadMissing('user');
        $user = $alumnus->user;

        if (! $user || ! $user->isAlumni()) {
            return;
        }

        $updates = [
            'name' => $alumnus->full_name,
        ];

        if ($alumnus->email && ! $this->emailExistsOnAnotherUser($user->id, $alumnus->email)) {
            $updates['email'] = $alumnus->email;
        }

        $user->fill($updates);

        if ($user->isDirty()) {
            $user->save();
        }
    }

    public function syncOrCreateUserFromAlumni(Alumni $alumnus, bool $activateExisting = false): string
    {
        $alumnus->loadMissing('user');
        $user = $alumnus->user;

        if ($user && ! $user->isAlumni()) {
            return 'skipped_non_alumni_link';
        }

        if (! $user) {
            if (! $alumnus->email) {
                return 'skipped_no_email';
            }

            if ($this->emailExistsOnAnotherUser(null, $alumnus->email)) {
                return 'skipped_email_conflict';
            }

            $user = User::create([
                'name' => $alumnus->full_name,
                'email' => $alumnus->email,
                'password' => Hash::make(Str::random(40)),
                'role' => 'alumni',
                'account_status' => 'approved',
                'approved_at' => now(),
                'alumni_id' => $alumnus->id,
            ]);

            return $user->exists ? 'created' : 'skipped_create_failed';
        }

        $updates = [
            'name' => $alumnus->full_name,
        ];

        if ($alumnus->email && ! $this->emailExistsOnAnotherUser($user->id, $alumnus->email)) {
            $updates['email'] = $alumnus->email;
        }

        if ($activateExisting) {
            $updates['account_status'] = 'approved';
            $updates['approved_at'] = now();
        }

        $user->fill($updates);

        if ($user->isDirty()) {
            $user->save();

            return 'updated';
        }

        return 'unchanged';
    }

    public function backfillMissingPortalUsers(): int
    {
        return 0;
    }

    public function syncAlumniFromUser(User $user): void
    {
        $alumnus = $user->alumni;

        if (! $user->isAlumni() || ! $alumnus) {
            return;
        }

        [$firstName, $lastName] = $this->splitFullName($user->name);

        $alumnus->fill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
        ]);

        if ($alumnus->isDirty()) {
            $alumnus->save();
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    public function splitFullName(string $fullName): array
    {
        $fullName = trim($fullName);

        if ($fullName === '') {
            return ['', ''];
        }

        if (str_contains($fullName, ',')) {
            [$lastName, $firstName] = array_map('trim', explode(',', $fullName, 2));

            return [$firstName, $lastName];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];

        if (count($parts) <= 1) {
            return [$fullName, $fullName];
        }

        $lastName = array_pop($parts);

        return [implode(' ', $parts), (string) $lastName];
    }

    private function emailExistsOnAnotherUser(?int $userId, string $email): bool
    {
        $query = User::query()
            ->where('email', $email);

        if ($userId !== null) {
            $query->where('id', '!=', $userId);
        }

        return $query->exists();
    }
}
