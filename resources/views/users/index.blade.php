@extends('layouts.app')

@section('title', 'User Accounts')
@section('subtitle', 'Review new account requests against the school alumni list, approve login access, and add verified accounts to Alumni Records.')

@section('content')
    <div class="page-card p-4 mb-4">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search by name, email, or role">
            </div>
            <div class="col-md-auto">
                <button class="btn btn-outline-primary" type="submit">Search</button>
            </div>
            @if ($search !== '')
                <div class="col-md-auto">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            @endif
        </form>
    </div>

    <div class="page-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" data-mobile-card-table>
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Linked Alumni Record</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $managedUser)
                        <tr>
                            <td data-label="User">
                                <div class="fw-semibold">{{ $managedUser->name }}</div>
                                <div class="small text-secondary">{{ $managedUser->email }}</div>
                            </td>
                            <td class="text-capitalize" data-label="Role">{{ $managedUser->role }}</td>
                            <td class="text-capitalize" data-label="Status">
                                {{ str_replace('_', ' ', $managedUser->account_status ?? 'approved') }}
                            </td>
                            <td data-label="Linked Alumni Record">
                                @if ($managedUser->alumni)
                                    <div class="fw-semibold">{{ $managedUser->alumni->full_name }}</div>
                                    <div class="small text-secondary">{{ $managedUser->alumni->student_id_display }}</div>
                                @else
                                    <span class="text-secondary">No alumni record linked</span>
                                @endif
                            </td>
                            <td class="text-end" data-label="Actions">
                                <div class="d-inline-flex flex-wrap gap-2 mobile-table-actions">
                                    @if ($managedUser->isAlumni() && $managedUser->isApproved() && ! $managedUser->alumni_id)
                                        <a href="{{ route('users.edit', $managedUser) }}#add-alumni-record" class="btn btn-sm btn-success">Add to Alumni Records</a>
                                    @endif
                                    <a href="{{ route('users.edit', $managedUser) }}" class="btn btn-sm btn-outline-primary">{{ $managedUser->isAlumni() ? 'Review Account' : 'Edit Account' }}</a>
                                    @unless ($managedUser->is(auth()->user()))
                                        <form method="POST" action="{{ route('users.destroy', $managedUser) }}" class="m-0" data-user-name="{{ $managedUser->name }}" onsubmit="return confirm('Delete the user account for ' + this.dataset.userName + '? This permanently removes login access. Any linked alumni record will be kept.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete account for {{ $managedUser->name }}">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">No user accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($users->hasPages())
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    @endif
@endsection
