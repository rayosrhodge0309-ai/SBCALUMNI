<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Services\LandingProfileSettingsService;
use App\Services\PortalContentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(
        PortalContentService $contentService,
        LandingProfileSettingsService $profileSettingsService
    ): View
    {
        $content = config('portal_content');
        $schoolAd = $this->resolveSchoolAd($content['school_ad'] ?? []);
        $boardMembers = $profileSettingsService->boardMembers();
        $alumniOfficeTeam = $profileSettingsService->alumniOfficeTeam();

        return view('welcome', [
            'content' => $content,
            'schoolAd' => $schoolAd,
            'landingStats' => [
                [
                    'value' => $contentService->activitiesCount(),
                    'label' => 'Alumni posts',
                    'href' => '#alumni-feed',
                ],
                [
                    'value' => count($boardMembers),
                    'label' => 'Board members',
                    'href' => '#leadership',
                ],
                [
                    'value' => count($alumniOfficeTeam),
                    'label' => 'Alumni officers',
                    'href' => '#alumni-office',
                ],
            ],
            'announcements' => $contentService->announcements(null),
            'announcementTotal' => $contentService->announcementsCount(),
            'activities' => $contentService->activities(null),
            'boardMembers' => $boardMembers,
            'alumniOfficeTeam' => $alumniOfficeTeam,
        ]);
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function resolveSchoolAd(array $defaults): array
    {
        $schoolAd = array_merge($defaults, [
            'photo_gallery' => $this->loadPhotoGallery($defaults),
        ]);

        $schoolAd['photo_slides'] = $this->buildPhotoSlides($schoolAd['photo_gallery']);

        return $schoolAd;
    }

    /**
     * @param  array<int, array<string, mixed>>  $slides
     * @return array<int, array{index:int,path:string,type:string,title:string,detail:string,url:string}>
     */
    private function buildPhotoSlides(array $slides): array
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
        if (Str::startsWith($path, 'storage:')) {
            $storagePath = Str::after($path, 'storage:');

            if (! Storage::disk('public')->exists($storagePath)) {
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

    private function sanitizeText(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
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
