<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LandingProfileSettingsService
{
    private const BOARD_SETTING_KEY = 'landing_board_members';
    private const BOARD_GROUP = 'board-members';

    private const TEAM_SETTING_KEY = 'landing_alumni_office_team';
    private const TEAM_GROUP = 'alumni-office-team';

    private const PLACEHOLDER_PHOTO = 'images/profile-placeholder.svg';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function boardMembers(): array
    {
        return $this->resolveProfiles(
            self::BOARD_SETTING_KEY,
            self::BOARD_GROUP,
            config('portal_content.board_members', [])
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function alumniOfficeTeam(): array
    {
        return $this->resolveProfiles(
            self::TEAM_SETTING_KEY,
            self::TEAM_GROUP,
            config('portal_content.alumni_office_team', [])
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $submittedMembers
     * @param  array<string, UploadedFile|mixed>  $uploadedPhotos
     * @param  array<string, mixed>  $removePhotos
     * @return array<int, array<string, mixed>>
     */
    public function saveBoardMembers(array $submittedMembers, array $uploadedPhotos = [], array $removePhotos = []): array
    {
        return $this->saveProfiles(
            self::BOARD_SETTING_KEY,
            self::BOARD_GROUP,
            config('portal_content.board_members', []),
            $submittedMembers,
            $uploadedPhotos,
            $removePhotos,
            'landing-profile-photos/board-members'
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $submittedMembers
     * @param  array<string, UploadedFile|mixed>  $uploadedPhotos
     * @param  array<string, mixed>  $removePhotos
     * @return array<int, array<string, mixed>>
     */
    public function saveAlumniOfficeTeam(array $submittedMembers, array $uploadedPhotos = [], array $removePhotos = []): array
    {
        return $this->saveProfiles(
            self::TEAM_SETTING_KEY,
            self::TEAM_GROUP,
            config('portal_content.alumni_office_team', []),
            $submittedMembers,
            $uploadedPhotos,
            $removePhotos,
            'landing-profile-photos/alumni-office-team'
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $defaults
     * @return array<int, array<string, mixed>>
     */
    private function resolveProfiles(string $settingKey, string $group, array $defaults): array
    {
        $storedProfiles = $this->decodeStoredProfiles($settingKey);
        $storedByKey = collect($storedProfiles)
            ->filter(fn ($profile) => is_array($profile) && is_string($profile['key'] ?? null))
            ->keyBy('key');

        $profiles = [];

        foreach ($defaults as $default) {
            if (! is_array($default)) {
                continue;
            }

            $key = $this->sanitizeKey($default['key'] ?? null);

            if ($key === '') {
                continue;
            }

            $stored = $storedByKey->get($key);

            $photoPath = $this->sanitizePath($stored['photo_path'] ?? $default['photo_path'] ?? null);

            $profiles[] = [
                'key' => $key,
                'name' => $this->sanitizeText($stored['name'] ?? $default['name'] ?? ''),
                'role' => $this->sanitizeText($stored['role'] ?? $default['role'] ?? ''),
                'details' => $this->sanitizeText($stored['details'] ?? $default['details'] ?? ''),
                'photo_path' => $photoPath,
                'photo_url' => $this->resolvePhotoUrl($group, $key, $photoPath),
                'initials' => $this->buildInitials($stored['name'] ?? $default['name'] ?? ''),
            ];
        }

        return $profiles;
    }

    /**
     * @param  array<int, array<string, mixed>>  $defaults
     * @param  array<string, array<string, mixed>>  $submittedMembers
     * @param  array<string, UploadedFile|mixed>  $uploadedPhotos
     * @param  array<string, mixed>  $removePhotos
     * @return array<int, array<string, mixed>>
     */
    private function saveProfiles(
        string $settingKey,
        string $group,
        array $defaults,
        array $submittedMembers,
        array $uploadedPhotos,
        array $removePhotos,
        string $folder
    ): array {
        $storedProfiles = $this->decodeStoredProfiles($settingKey);
        $storedByKey = collect($storedProfiles)
            ->filter(fn ($profile) => is_array($profile) && is_string($profile['key'] ?? null))
            ->keyBy('key');

        $profiles = [];

        foreach ($defaults as $default) {
            if (! is_array($default)) {
                continue;
            }

            $key = $this->sanitizeKey($default['key'] ?? null);

            if ($key === '') {
                continue;
            }

            $stored = $storedByKey->get($key, []);
            $submitted = is_array($submittedMembers[$key] ?? null) ? $submittedMembers[$key] : [];

            $photoPath = $this->sanitizePath($stored['photo_path'] ?? $default['photo_path'] ?? null);

            if ($this->shouldRemovePhoto($removePhotos[$key] ?? null)) {
                $this->deletePhoto($photoPath);
                $photoPath = null;
            }

            $upload = $uploadedPhotos[$key] ?? null;

            if ($upload instanceof UploadedFile && $upload->isValid()) {
                if ($photoPath) {
                    $this->deletePhoto($photoPath);
                }

                $photoPath = $upload->store($folder, 'public');
            }

            $profiles[] = [
                'key' => $key,
                'name' => $this->sanitizeText($submitted['name'] ?? $stored['name'] ?? $default['name'] ?? ''),
                'role' => $this->sanitizeText($submitted['role'] ?? $stored['role'] ?? $default['role'] ?? ''),
                'details' => $this->sanitizeText($submitted['details'] ?? $stored['details'] ?? $default['details'] ?? ''),
                'photo_path' => $photoPath,
                'photo_url' => $this->resolvePhotoUrl($group, $key, $photoPath),
                'initials' => $this->buildInitials($submitted['name'] ?? $stored['name'] ?? $default['name'] ?? ''),
            ];
        }

        SiteSetting::setMany([
            $settingKey => json_encode($profiles, JSON_UNESCAPED_SLASHES),
        ]);

        return $profiles;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeStoredProfiles(string $settingKey): array
    {
        $raw = SiteSetting::getValue($settingKey);

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        return [];
    }

    private function resolvePhotoUrl(string $group, string $key, ?string $path): string
    {
        if ($path === null || $path === '') {
            return asset(self::PLACEHOLDER_PHOTO);
        }

        if ($this->isStoragePath($path)) {
            $storagePath = $this->stripStoragePrefix($path);

            if (Storage::disk('public')->exists($storagePath)) {
                return route('landing-profile-media.show', ['group' => $group, 'key' => $key]);
            }
        } elseif (Storage::disk('public')->exists($path)) {
            return route('landing-profile-media.show', ['group' => $group, 'key' => $key]);
        } elseif (is_file(public_path($path))) {
            return route('landing-profile-media.show', ['group' => $group, 'key' => $key]);
        }

        return asset(self::PLACEHOLDER_PHOTO);
    }

    private function deletePhoto(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if ($this->isStoragePath($path)) {
            Storage::disk('public')->delete($this->stripStoragePrefix($path));

            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function shouldRemovePhoto(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function sanitizeText(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function sanitizeKey(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function buildInitials(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return 'NA';
        }

        $initials = Str::of($value)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'NA';
    }

    private function sanitizePath(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $path = trim($value);

        return $path !== '' ? $path : null;
    }

    private function isStoragePath(string $path): bool
    {
        return Str::startsWith($path, 'storage:');
    }

    private function stripStoragePrefix(string $path): string
    {
        return (string) Str::after($path, 'storage:');
    }
}
