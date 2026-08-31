<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('announcements.index', [
            'announcements' => Announcement::query()
                ->latest('published_at')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $this->resolvePublishedAt($validated);
        $media = $request->file('media');

        unset($validated['media'], $validated['remove_media']);

        if ($media) {
            $validated['media_path'] = $media->store('announcement-media', 'public');
            $validated['media_type'] = $this->resolveMediaType($media->getMimeType());
        }

        Announcement::create($validated);

        return redirect()->route('announcements.index')->with('success', 'Announcement created successfully.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $this->resolvePublishedAt($validated, $announcement);
        $media = $request->file('media');
        $shouldClearExistingMedia = $request->boolean('remove_media') || $media;

        unset($validated['media'], $validated['remove_media']);

        if ($shouldClearExistingMedia && $announcement->media_path) {
            Storage::disk('public')->delete($announcement->media_path);
            $validated['media_path'] = null;
            $validated['media_type'] = null;
        }

        if ($media) {
            $validated['media_path'] = $media->store('announcement-media', 'public');
            $validated['media_type'] = $this->resolveMediaType($media->getMimeType());
        }

        $announcement->update($validated);

        return redirect()->route('announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted successfully.');
    }

    public function media(Announcement $announcement): Response
    {
        abort_unless($announcement->media_path, 404);
        abort_unless(Storage::disk('public')->exists($announcement->media_path), 404);

        /** @var ResponseFactory $response */
        $response = response();

        return $response->file(Storage::disk('public')->path($announcement->media_path), [
            'Cache-Control' => 'public, max-age=86400',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function recordView(Announcement $announcement): JsonResponse
    {
        abort_unless($announcement->is_published, 404);

        $announcement->increment('views_count');

        return response()->json([
            'views_count' => $announcement->views_count,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'label' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'media' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg,video/quicktime|max:20480',
            'remove_media' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolvePublishedAt(array $validated, ?Announcement $announcement = null): ?string
    {
        $isPublished = (bool) ($validated['is_published'] ?? false);

        if (! $isPublished) {
            return null;
        }

        if (! empty($validated['published_at'])) {
            return Carbon::parse($validated['published_at'])->format('Y-m-d H:i:s');
        }

        return $announcement?->published_at?->format('Y-m-d H:i:s')
            ?? now()->format('Y-m-d H:i:s');
    }

    private function resolveMediaType(?string $mimeType): ?string
    {
        if (! $mimeType) {
            return null;
        }

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return null;
    }
}
