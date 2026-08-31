@extends('layouts.app')

@section('title', 'Record Request Processing')
@section('subtitle', 'Only administrators can review and process alumni document requests.')

@section('content')
    <div class="page-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" data-mobile-card-table>
                <thead class="table-light">
                    <tr>
                        <th>Alumni</th>
                        <th>School Level</th>
                        <th>Request</th>
                        <th>Year</th>
                        <th>Alumni Message</th>
                        <th>Status</th>
                        <th>Admin Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $recordRequest)
                        <tr>
                            <td data-label="Alumni">
                                <div class="fw-semibold">{{ $recordRequest->alumni?->full_name ?? 'Unknown alumni' }}</div>
                                <div class="small text-secondary">{{ $recordRequest->alumni?->student_id_display ?? 'No student ID' }}</div>
                            </td>
                            <td data-label="School Level">{{ $recordRequest->alumni?->education_level ?? 'Unknown' }}</td>
                            <td data-label="Request">{{ $recordRequest->request_type }}</td>
                            <td data-label="Year">{{ $recordRequest->year_requested }}</td>
                            <td data-label="Alumni Message">{!! $recordRequest->requester_note ? nl2br(e($recordRequest->requester_note)) : '-' !!}</td>
                            <td data-label="Status">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                    {{ $statusOptions[$recordRequest->status] ?? ucfirst(str_replace('_', ' ', $recordRequest->status)) }}
                                </span>
                                @if ($recordRequest->processedBy)
                                    <div class="small text-secondary mt-2">
                                        {{ $recordRequest->processedBy->name }}
                                        @if ($recordRequest->processed_at)
                                            on {{ $recordRequest->processed_at->format('M d, Y h:i A') }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td style="min-width: 320px;" data-label="Admin Update">
                                <form method="POST" action="{{ route('requests.status', $recordRequest) }}">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="admin_notes" class="form-control mb-2" rows="3" placeholder="Optional pickup or processing note">{{ $recordRequest->admin_notes }}</textarea>
                                    <div class="d-flex gap-2">
                                        <select name="status" class="form-select form-select-sm">
                                            @foreach ($statusOptions as $statusValue => $statusLabel)
                                                <option value="{{ $statusValue }}" @selected($recordRequest->status === $statusValue)>{{ $statusLabel }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit" style="min-width: 4.5rem; white-space: nowrap;">Save</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No alumni requests are waiting for processing.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($requests->hasPages())
        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    @endif
@endsection
