<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\LandingProfileSettingsService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LandingVideoSettingController extends Controller
{
    public function edit(LandingProfileSettingsService $profileSettingsService): View
    {
        $schoolAd = $this->schoolAdSettings();
        $photoGallery = $this->buildPhotoGalleryPreview($schoolAd['photo_gallery'] ?? []);

        return view('admin.settings.landing-video', [
            'schoolAd' => $schoolAd,
            'photoGallery' => $photoGallery,
            'boardMembers' => $profileSettingsService->boardMembers(),
            'alumniOfficeTeam' => $profileSettingsService->alumniOfficeTeam(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo_files' => 'nullable|array|max:12',
            'photo_files.*' => [
                'nullable',
                'file',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value || ! method_exists($value, 'getMimeType')) {
                        return;
                    }

                    $allowed = [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'video/mp4',
                        'video/webm',
                        'video/ogg',
                        'video/quicktime',
                        'video/x-msvideo',
                        'video/x-matroska',
                    ];

                    if (! in_array((string) $value->getMimeType(), $allowed, true)) {
                        $fail('Each file must be an image or a video.');
                    }
                },
            ],
            'new_slide_titles' => 'nullable|array',
            'new_slide_titles.*' => 'nullable|string|max:120',
            'new_slide_details' => 'nullable|array',
            'new_slide_details.*' => 'nullable|string|max:280',
            'existing_slide_titles' => 'nullable|array',
            'existing_slide_titles.*' => 'nullable|string|max:120',
            'existing_slide_details' => 'nullable|array',
            'existing_slide_details.*' => 'nullable|string|max:280',
            'remove_slide_indexes' => 'nullable|array',
            'remove_slide_indexes.*' => 'nullable|integer|min:0',
            'remove_photos' => 'nullable|boolean',
        ]);

        $uploads = $request->file('photo_files', []);
        $removePhotos = $request->boolean('remove_photos');
        $removeIndexes = collect($validated['remove_slide_indexes'] ?? [])
            ->map(fn ($index) => (int) $index)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $existingTitles = $validated['existing_slide_titles'] ?? [];
        $existingDetails = $validated['existing_slide_details'] ?? [];
        $newTitles = $validated['new_slide_titles'] ?? [];
        $newDetails = $validated['new_slide_details'] ?? [];

        $slides = $this->loadPhotoGallery(config('portal_content.school_ad', []));

        if ($removePhotos) {
            foreach ($slides as $slide) {
                $this->deleteMediaPath($slide['path'] ?? null);
            }

            $slides = [];
        } else {
            $galleryMap = array_values($slides);

            foreach (array_reverse($removeIndexes) as $index) {
                if (! array_key_exists($index, $galleryMap)) {
                    continue;
                }

                $this->deleteMediaPath($galleryMap[$index]['path'] ?? null);
                unset($galleryMap[$index]);
            }

            $slides = [];

            foreach ($galleryMap as $index => $slide) {
                $path = (string) ($slide['path'] ?? '');

                $slides[] = [
                    'path' => $path,
                    'type' => $this->normalizeMediaType($slide['type'] ?? null, $path),
                    'title' => $this->sanitizeText($existingTitles[$index] ?? $slide['title'] ?? null),
                    'detail' => $this->sanitizeText($existingDetails[$index] ?? $slide['detail'] ?? null),
                ];
            }
        }

        foreach (is_array($uploads) ? array_values($uploads) : [] as $index => $upload) {
            if (! $upload) {
                continue;
            }

            $storedPhotoPath = $upload->store('landing-slider', 'public');
            $mediaType = $this->detectMediaType($upload->getMimeType());

            $slides[] = [
                'path' => 'storage:'.$storedPhotoPath,
                'type' => $mediaType,
                'title' => $this->sanitizeText($newTitles[$index] ?? null),
                'detail' => $this->sanitizeText($newDetails[$index] ?? null),
            ];
        }

        SiteSetting::setMany([
            'landing_school_ad_photo_gallery' => json_encode(array_values($slides), JSON_UNESCAPED_SLASHES),
        ]);

        return redirect()
            ->route('admin.settings.landing-video.edit')
            ->with('success', 'Landing page slider updated successfully.');
    }

    public function updateProfiles(Request $request, LandingProfileSettingsService $profileSettingsService): RedirectResponse
    {
        $validated = $request->validate([
            'board_members' => 'required|array',
            'board_members.*.name' => 'required|string|max:255',
            'board_members.*.role' => 'required|string|max:255',
            'alumni_office_team' => 'required|array',
            'alumni_office_team.*.name' => 'required|string|max:255',
            'alumni_office_team.*.role' => 'required|string|max:255',
            'alumni_office_team.*.details' => 'nullable|string|max:280',
            'board_member_photos' => 'nullable|array',
            'board_member_photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'alumni_office_team_photos' => 'nullable|array',
            'alumni_office_team_photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'remove_board_member_photos' => 'nullable|array',
            'remove_alumni_office_team_photos' => 'nullable|array',
        ]);

        $profileSettingsService->saveBoardMembers(
            $validated['board_members'] ?? [],
            $request->file('board_member_photos', []),
            $request->input('remove_board_member_photos', [])
        );

        $profileSettingsService->saveAlumniOfficeTeam(
            $validated['alumni_office_team'] ?? [],
            $request->file('alumni_office_team_photos', []),
            $request->input('remove_alumni_office_team_photos', [])
        );

        return redirect()
            ->route('admin.settings.landing-video.edit')
            ->with('success', 'Leadership profile photos and details updated successfully.');
    }

    public function media(string $kind, ?int $index = null): Response
    {
        if (! in_array($kind, ['photo', 'video', 'poster'], true)) {
            abort(404);
        }

        if (in_array($kind, ['photo', 'video'], true) && $index !== null) {
            $slides = $this->loadPhotoGallery(config('portal_content.school_ad', []));
            $slide = $slides[$index] ?? null;
            $path = is_array($slide) ? ($slide['path'] ?? null) : null;

            if (! $path) {
                abort(404);
            }

            return $this->streamMediaFile($path);
        }

        $path = null;

        if (! $path) {
            $settingKey = $kind === 'video'
                ? 'landing_school_ad_video_path'
                : 'landing_school_ad_poster_path';

            $defaultPath = config('portal_content.school_ad.'.($kind === 'video' ? 'video_path' : 'poster_path'));
            $path = SiteSetting::getValue($settingKey, $defaultPath);
        }

        if (! $path) {
            abort(404);
        }

        return $this->streamMediaFile($path);
    }

    public function profileMedia(string $group, string $key, LandingProfileSettingsService $profileSettingsService): Response
    {
        if (! in_array($group, ['board-members', 'alumni-office-team'], true)) {
            abort(404);
        }

        $profiles = $group === 'board-members'
            ? $profileSettingsService->boardMembers()
            : $profileSettingsService->alumniOfficeTeam();

        $profile = collect($profiles)->firstWhere('key', $key);
        $path = is_array($profile) ? ($profile['photo_path'] ?? null) : null;

        if (! is_string($path) || trim($path) === '') {
            abort(404);
        }

        return $this->streamLandingProfileFile($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function schoolAdSettings(): array
    {
        $defaults = config('portal_content.school_ad', []);

        return array_merge($defaults, [
            'photo_gallery' => $this->loadPhotoGallery($defaults),
        ]);
    }

    /**
     * @param  array<int, array{path:string,title:string,detail:string,type:string}>  $slides
     * @return array<int, array{index:int,path:string,type:string,title:string,detail:string,url:string}>
     */
    private function buildPhotoGalleryPreview(array $slides): array
    {
        $items = [];

        foreach (array_values($slides) as $index => $slide) {
            $path = $slide['path'] ?? null;

            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $url = $this->resolvePhotoUrl($path, $index, $this->normalizeMediaType($slide['type'] ?? null, $path));

            if (! $url) {
                continue;
            }

            $items[] = [
                'index' => $index,
                'path' => $path,
                'type' => $this->normalizeMediaType($slide['type'] ?? null, $path),
                'title' => $this->sanitizeText($slide['title'] ?? null),
                'detail' => $this->sanitizeText($slide['detail'] ?? null),
                'url' => $url,
            ];
        }

        return $items;
    }

    private function resolvePhotoUrl(string $path, int $index, ?string $type = null): ?string
    {
        if ($this->isStoragePath($path)) {
            if (! Storage::disk('public')->exists($this->stripStoragePrefix($path))) {
                return null;
            }
        } elseif (! is_file(public_path($path))) {
            return null;
        }

        return route('landing-media.show', ['kind' => $this->normalizeMediaType($type, $path), 'index' => $index]);
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<int, array{path:string,title:string,detail:string,type:string}>
     */
    private function loadPhotoGallery(array $defaults): array
    {
        $raw = SiteSetting::getValue('landing_school_ad_photo_gallery');
        $slides = [];

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                $slides = $decoded;
            }
        } elseif (is_array($raw)) {
            $slides = $raw;
        }

        $normalized = $this->normalizePhotoSlides($slides);

        if ($normalized !== []) {
            return $normalized;
        }

        $defaultSlides = $defaults['slides'] ?? [];

        return is_array($defaultSlides) ? $this->normalizePhotoSlides($defaultSlides) : [];
    }

    /**
     * @param  mixed  $slides
     * @return array<int, array{path:string,title:string,detail:string,type:string}>
     */
    private function normalizePhotoSlides($slides): array
    {
        if (! is_array($slides)) {
            return [];
        }

        $normalized = [];

        foreach ($slides as $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $path = $slide['path'] ?? null;

            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $normalized[] = [
                'path' => trim($path),
                'title' => $this->sanitizeText($slide['title'] ?? null),
                'detail' => $this->sanitizeText($slide['detail'] ?? null),
                'type' => $this->normalizeMediaType($slide['type'] ?? null, $path),
            ];
        }

        return $normalized;
    }

    private function streamMediaFile(string $path): Response
    {
        /** @var ResponseFactory $response */
        $response = response();

        if ($this->isStoragePath($path)) {
            $storagePath = $this->stripStoragePrefix($path);

            abort_unless(Storage::disk('public')->exists($storagePath), 404);

            return $response->file(Storage::disk('public')->path($storagePath), [
                'Cache-Control' => 'public, max-age=86400',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        $publicPath = public_path($path);
        abort_unless(is_file($publicPath), 404);

        return $response->file($publicPath, [
            'Cache-Control' => 'public, max-age=86400',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    private function streamLandingProfileFile(string $path): Response
    {
        /** @var ResponseFactory $response */
        $response = response();

        if ($this->isStoragePath($path)) {
            $storagePath = $this->stripStoragePrefix($path);

            abort_unless(Storage::disk('public')->exists($storagePath), 404);

            return $response->file(Storage::disk('public')->path($storagePath), [
                'Cache-Control' => 'public, max-age=86400',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        if (Storage::disk('public')->exists($path)) {
            return $response->file(Storage::disk('public')->path($path), [
                'Cache-Control' => 'public, max-age=86400',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        $publicPath = public_path($path);
        abort_unless(is_file($publicPath), 404);

        return $response->file($publicPath, [
            'Cache-Control' => 'public, max-age=86400',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    private function isStoragePath(?string $path): bool
    {
        return is_string($path) && Str::startsWith($path, 'storage:');
    }

    private function stripStoragePrefix(string $path): string
    {
        return (string) Str::after($path, 'storage:');
    }

    private function sanitizeText(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function deleteMediaPath(?string $path): void
    {
        if (! $path) {
            return;
        }

        if ($this->isStoragePath($path)) {
            Storage::disk('public')->delete($this->stripStoragePrefix($path));
        }
    }

    private function detectMediaType(?string $mimeType): string
    {
        $mimeType = is_string($mimeType) ? strtolower(trim($mimeType)) : '';

        if ($mimeType !== '' && str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'photo';
    }

    private function normalizeMediaType(mixed $type, string $path): string
    {
        $type = is_string($type) ? strtolower(trim($type)) : '';

        if (in_array($type, ['photo', 'video'], true)) {
            return $type;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'], true)) {
            return 'video';
        }

        return 'photo';
    }
}
