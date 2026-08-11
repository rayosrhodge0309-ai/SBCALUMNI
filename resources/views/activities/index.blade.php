@extends('layouts.app')

@section('title', 'SBC Alumni Posts')
@section('subtitle', 'Manage alumni posts that appear on the landing page and alumni portal.')

@section('content')
    <div class="d-flex justify-content-end mb-4">
        <a href="{{ route('activities.create') }}" class="btn btn-primary">Create Post</a>
    </div>

    <div class="page-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" data-mobile-card-table>
                <thead class="table-light">
                    <tr>
                        <th>Theme</th>
                        <th>Title</th>
                        <th>Media</th>
                        <th>Date</th>
                        <th>Views</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr>
                            <td data-label="Theme">{{ $activity->theme ?: 'Activity' }}</td>
                            <td data-label="Title">
                                <div class="fw-semibold">{{ $activity->title }}</div>
                                <div class="small text-secondary">{{ \Illuminate\Support\Str::limit($activity->description, 90) }}</div>
                            </td>
                            <td data-label="Media">
                                @if ($activity->isImageMedia())
                                    <span class="badge text-bg-info">Photo</span>
                                @elseif ($activity->isVideoMedia())
                                    <span class="badge text-bg-warning">Video</span>
                                @else
                                    <span class="text-secondary small">None</span>
                                @endif
                            </td>
                            <td data-label="Date">{{ $activity->activity_date?->format('M d, Y') ?: 'TBA' }}</td>
                            <td data-label="Views">{{ number_format($activity->views_count ?? 0) }}</td>
                            <td data-label="Status">
                                <span class="badge {{ $activity->is_published ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $activity->is_published ? 'Published' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end" data-label="Actions">
                                <div class="d-inline-flex gap-2 mobile-table-actions">
                                    <a href="{{ route('activities.show', $activity) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="{{ route('activities.edit', $activity) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('activities.destroy', $activity) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this activity?')" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No alumni posts have been posted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($activities->hasPages())
        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    @endif
@endsection
