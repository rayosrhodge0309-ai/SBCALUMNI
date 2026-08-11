@extends('layouts.app')

@section('title', 'Pending Account Requests')
@section('subtitle', '')

@section('content')
    <div class="page-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" data-mobile-card-table>
                <thead class="table-light">
                    <tr>
                        <th>Applicant</th>
                        <th>Student Record</th>
                        <th>Submitted</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                        <tbody>
                            @forelse ($pendingUsers as $managedUser)
                                <tr>
                                    <td data-label="Applicant">
                                        <div class="fw-semibold">{{ $managedUser->name }}</div>
                                        <div class="small text-secondary">{{ $managedUser->email }}</div>
                                    </td>
                                    <td data-label="Student Record">
                                        @if ($managedUser->alumni)
                                            <div class="fw-semibold">{{ $managedUser->alumni->student_id_display }}</div>
                                            <div class="small text-secondary">{{ $managedUser->alumni->full_name }}</div>
                                            <div class="small text-secondary">{{ $managedUser->alumni->academic_label }}</div>
                                        @else
                                            <span class="text-secondary">No alumni record linked</span>
                                        @endif
                                    </td>
                                    <td data-label="Submitted">
                                        <div>{{ $managedUser->created_at->format('F d, Y') }}</div>
                                        <div class="small text-secondary">{{ $managedUser->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="text-end" data-label="Actions">
                                        <div class="d-inline-flex flex-wrap gap-2 justify-content-end mobile-table-actions">
                                            <form method="POST" action="{{ route('users.approve', $managedUser) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('users.reject', $managedUser) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this account request?')">Reject</button>
                                            </form>
                                            <a href="{{ route('users.edit', $managedUser) }}" class="btn btn-sm btn-outline-primary">Review</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                            <td colspan="4" class="text-center text-secondary py-5">No pending account requests.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($pendingUsers->hasPages())
        <div class="mt-4">
            {{ $pendingUsers->links() }}
        </div>
    @endif
@endsection
