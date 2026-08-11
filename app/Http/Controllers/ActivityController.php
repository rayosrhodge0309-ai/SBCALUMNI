<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ActivityController extends Controller
{
    public function show(Activity $activity): View
    {
        abort_unless($activity->is_published || auth()->user()?->isAdmin(), 404);

        $activity->increment('views_count');
        $activity->refresh();

        return view('activities.show', [
            'activity' => $activity,
            'relatedActivities' => Activity::query()
                ->published()
                ->whereKeyNot($activity->getKey())
                ->orderByDesc('activity_date')
                ->latest('id')
                ->take(4)
                ->get(),
        ]);
    }

    public function index(): View
    {
        return view('activities.index', [
            'activities' => Activity::query()
                ->orderBy('activity_date')
                ->latest('id')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('activities.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['is_published'] = $request->boolean('is_published');
        $media = $request->file('media');

        unset($validated['media'], $validated['remove_media']);

        if ($media) {
            $validated['media_path'] = $media->store('activity-media', 'public');
            $validated['media_type'] = $this->resolveMediaType($media->getMimeType());
        }

        Activity::create($validated);

        return redirect()->route('activities.index')->with('success', 'Activity created successfully.');
    }

    public function edit(Activity $activity): View
    {
        return view('activities.edit', compact('activity'));
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['is_published'] = $request->boolean('is_published');
        $media = $request->file('media');
        $shouldClearExistingMedia = $request->boolean('remove_media') || $media;

        unset($validated['media'], $validated['remove_media']);

        if ($shouldClearExistingMedia && $activity->media_path) {
            Storage::disk('public')->delete($activity->media_path);
            $validated['media_path'] = null;
            $validated['media_type'] = null;
        }

        if ($media) {
            $validated['media_path'] = $media->store('activity-media', 'public');
            $validated['media_type'] = $this->resolveMediaType($media->getMimeType());
        }

        $activity->update($validated);

        return redirect()->route('activities.index')->with('success', 'Activity updated successfully.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect()->route('activities.index')->with('success', 'Activity deleted successfully.');
    }

    public function media(Activity $activity): Response
    {
        abort_unless($activity->media_path, 404);
        abort_unless(Storage::disk('public')->exists($activity->media_path), 404);

        $response = response();
        $mimeType = Storage::disk('public')->mimeType($activity->media_path) ?: 'application/octet-stream';

        return $response->file(Storage::disk('public')->path($activity->media_path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($activity->media_path).'"',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'theme' => 'nullable|string|max:120',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'activity_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'media' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg,video/quicktime|max:20480',
            'remove_media' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ];
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
