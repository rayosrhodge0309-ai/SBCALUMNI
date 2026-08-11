@extends('layouts.app')

@section('title', 'Events')
@section('subtitle', 'Schedule and maintain alumni meetings, reunions, and campus activities.')

@section('content')
    <div class="d-flex justify-content-end mb-4">
        <a href="{{ route('events.create') }}" class="btn btn-primary">Add Event</a>
    </div>

    <div class="page-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" data-mobile-card-table>
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td class="fw-semibold" data-label="Title">{{ $event->title }}</td>
                            <td data-label="Date">{{ $event->event_date->format('M d, Y') }}</td>
                            <td data-label="Location">{{ $event->location ?: 'TBA' }}</td>
                            <td data-label="Status">
                                <span class="badge {{ $event->is_published ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $event->is_published ? 'Published' : 'Hidden' }}
                                </span>
                            </td>
                            <td data-label="Description">
                                @if ($event->media_url)
                                    <div class="small mb-1">
                                        <span class="badge text-bg-info">{{ $event->isImageMedia() ? 'Photo' : 'Video' }}</span>
                                    </div>
                                @endif
                                {{ \Illuminate\Support\Str::limit($event->description, 80) }}
                            </td>
                            <td class="text-end" data-label="Actions">
                                <div class="d-inline-flex gap-2 mobile-table-actions">
                                    <a href="{{ route('events.edit', $event) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('events.destroy', $event) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this event?')" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No events available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($events->hasPages())
        <div class="mt-4">
            {{ $events->links() }}
        </div>
    @endif
@endsection
