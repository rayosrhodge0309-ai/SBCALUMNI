@extends('layouts.app')

@section('title', 'Pending Account Requests')
@section('subtitle', '')

@section('content')
    <div class="page-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0" data-mobile-card-table data-pending-accounts-table>
                <thead class="table-light">
                    <tr>
                        <th>Applicant</th>
                        <th>Student Record</th>
                        <th>Submitted</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                        <tbody data-pending-accounts-body>
                            @forelse ($pendingUsers as $managedUser)
                                <tr data-pending-user-id="{{ $managedUser->id }}">
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
                                <tr data-pending-empty-row>
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

@push('scripts')
    <script>
        (function () {
            var tableBody = document.querySelector('[data-pending-accounts-body]');

            if (!tableBody) {
                return;
            }

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            function appendText(parent, className, text) {
                var element = document.createElement('div');
                element.className = className;
                element.textContent = text;
                parent.appendChild(element);

                return element;
            }

            function createPatchForm(action, buttonClass, label, confirmMessage) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = action;

                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = csrfToken;

                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'PATCH';

                var button = document.createElement('button');
                button.type = 'submit';
                button.className = buttonClass;
                button.textContent = label;

                if (confirmMessage) {
                    button.addEventListener('click', function (event) {
                        if (!window.confirm(confirmMessage)) {
                            event.preventDefault();
                        }
                    });
                }

                form.appendChild(csrf);
                form.appendChild(method);
                form.appendChild(button);

                return form;
            }

            function createPendingRow(request) {
                if (!request.id || tableBody.querySelector('[data-pending-user-id="' + request.id + '"]')) {
                    return null;
                }

                var row = document.createElement('tr');
                row.setAttribute('data-pending-user-id', request.id);

                var applicant = document.createElement('td');
                applicant.setAttribute('data-label', 'Applicant');
                appendText(applicant, 'fw-semibold', request.name || 'New applicant');
                appendText(applicant, 'small text-secondary', request.email || '');

                var studentRecord = document.createElement('td');
                studentRecord.setAttribute('data-label', 'Student Record');

                if (request.student_id || request.student_name || request.academic_label) {
                    appendText(studentRecord, 'fw-semibold', request.student_id || 'No student ID');
                    appendText(studentRecord, 'small text-secondary', request.student_name || request.name || '');
                    appendText(studentRecord, 'small text-secondary', request.academic_label || '');
                } else {
                    var emptyRecord = document.createElement('span');
                    emptyRecord.className = 'text-secondary';
                    emptyRecord.textContent = 'No alumni record linked';
                    studentRecord.appendChild(emptyRecord);
                }

                var submitted = document.createElement('td');
                submitted.setAttribute('data-label', 'Submitted');
                appendText(submitted, '', request.submitted_date || 'Today');
                appendText(submitted, 'small text-secondary', request.submitted_time || '');

                var actions = document.createElement('td');
                actions.className = 'text-end';
                actions.setAttribute('data-label', 'Actions');

                var actionGroup = document.createElement('div');
                actionGroup.className = 'd-inline-flex flex-wrap gap-2 justify-content-end mobile-table-actions';

                if (request.approve_url) {
                    actionGroup.appendChild(createPatchForm(request.approve_url, 'btn btn-sm btn-success', 'Approve'));
                }

                if (request.reject_url) {
                    actionGroup.appendChild(createPatchForm(request.reject_url, 'btn btn-sm btn-outline-danger', 'Reject', 'Reject this account request?'));
                }

                if (request.review_url) {
                    var review = document.createElement('a');
                    review.href = request.review_url;
                    review.className = 'btn btn-sm btn-outline-primary';
                    review.textContent = 'Review';
                    actionGroup.appendChild(review);
                }

                actions.appendChild(actionGroup);
                row.appendChild(applicant);
                row.appendChild(studentRecord);
                row.appendChild(submitted);
                row.appendChild(actions);

                return row;
            }

            document.addEventListener('admin:pending-account-request', function (event) {
                var requests = event.detail && Array.isArray(event.detail.requests) ? event.detail.requests : [];
                var emptyRow = document.querySelector('[data-pending-empty-row]');

                if (emptyRow && requests.length > 0) {
                    emptyRow.remove();
                }

                requests.forEach(function (request) {
                    var row = createPendingRow(request);

                    if (row) {
                        tableBody.prepend(row);
                    }
                });
            });
        })();
    </script>
@endpush
