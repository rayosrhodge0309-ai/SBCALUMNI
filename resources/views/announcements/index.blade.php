@extends('layouts.app')

@section('title', 'Announcements')
@section('subtitle', 'Post school notices that alumni can see on the landing page and their dashboard.')

@section('content')
    <div class="d-flex justify-content-end mb-4">
        <a href="{{ route('announcements.create') }}" class="btn btn-primary">Add Announcement</a>
    </div>

    <div class="page-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" data-mobile-card-table>
                <thead class="table-light">
                    <tr>
                        <th>Label</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Published</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($announcements as $announcement)
                        <tr>
                            <td data-label="Label">{{ $announcement->label ?: 'Announcement' }}</td>
                            <td data-label="Title">
                                <div class="fw-semibold">{{ $announcement->title }}</div>
                                @if ($announcement->media_url)
                                    <div class="small mb-1">
                                        <span class="badge text-bg-info">{{ $announcement->isImageMedia() ? 'Photo' : 'Video' }}</span>
                                    </div>
                                @endif
                                <div class="small text-secondary">{{ \Illuminate\Support\Str::limit($announcement->content, 90) }}</div>
                            </td>
                            <td data-label="Status">
                                <span class="badge {{ $announcement->is_published ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $announcement->is_published ? 'Published' : 'Hidden' }}
                                </span>
                            </td>
                            <td data-label="Published">{{ $announcement->published_at?->format('M d, Y h:i A') ?: 'Not scheduled' }}</td>
                            <td class="text-end" data-label="Actions">
                                <div class="d-inline-flex gap-2 mobile-table-actions">
                                    <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('announcements.destroy', $announcement) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this announcement?')" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">No announcements have been posted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($announcements->hasPages())
        <div class="mt-4">
            {{ $announcements->links() }}
        </div>
    @endif
@endsection
