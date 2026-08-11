<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::orderBy('event_date')->paginate(10);

        return view('events.index', compact('events'));
    }

    public function create(): View
    {
        return view('events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['is_published'] = $request->boolean('is_published');
        $media = $request->file('media');

        unset($validated['media'], $validated['remove_media']);

        if ($media) {
            $validated['media_path'] = $media->store('event-media', 'public');
            $validated['media_type'] = $this->resolveMediaType($media->getMimeType());
        }

        Event::create($validated);

        return redirect()->route('events.index')->with('success', 'Event created');
    }

    public function edit(Event $event): View
    {
        return view('events.edit', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['is_published'] = $request->boolean('is_published');
        $media = $request->file('media');
        $shouldClearExistingMedia = $request->boolean('remove_media') || $media;

        unset($validated['media'], $validated['remove_media']);

        if ($shouldClearExistingMedia && $event->media_path) {
            Storage::disk('public')->delete($event->media_path);
            $validated['media_path'] = null;
            $validated['media_type'] = null;
        }

        if ($media) {
            $validated['media_path'] = $media->store('event-media', 'public');
            $validated['media_type'] = $this->resolveMediaType($media->getMimeType());
        }

        $event->update($validated);

        return redirect()->route('events.index')->with('success', 'Updated');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Deleted');
    }

    public function media(Event $event): Response
    {
        abort_unless($event->media_path, 404);
        abort_unless(Storage::disk('public')->exists($event->media_path), 404);

        /** @var ResponseFactory $response */
        $response = response();

        return $response->file(Storage::disk('public')->path($event->media_path), [
            'Cache-Control' => 'public, max-age=86400',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'event_date' => 'required|date',
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
