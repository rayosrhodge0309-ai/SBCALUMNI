@extends('layouts.app')

@section('title', 'Alumni Records')
@section('subtitle', '')

@section('content')
    @php
        $hasAlumni = $alumni->isNotEmpty();
        $courseGroups = $hasAlumni
            ? $alumni->getCollection()
                ->sortBy(function ($record) {
                    return implode('|', [
                        $record->program_group_sort_key,
                        \Illuminate\Support\Str::lower((string) $record->last_name),
                        \Illuminate\Support\Str::lower((string) $record->first_name),
                        (string) $record->id,
                    ]);
                })
                ->groupBy(fn ($record) => $record->program_group_label)
            : collect();
    @endphp

    <div class="alumni-records-page">
    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="page-card p-4 h-100">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <form method="GET" action="{{ route('alumni.index') }}" class="row g-2 flex-grow-1">
                        <div class="col-md-8 col-lg-7">
                            <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search by name, student ID, level, program, or year">
                        </div>
                        <div class="col-md-auto">
                            <button class="btn btn-outline-primary" type="submit">Search</button>
                        </div>
                        @if ($search !== '')
                            <div class="col-md-auto">
                                <a href="{{ route('alumni.index') }}" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        @endif
                    </form>

                    <a href="{{ route('alumni.create') }}" class="btn btn-primary">Add Alumni</a>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="page-card p-4 h-100">
                <h3 class="h5 mb-3">Import Alumni File</h3>
                <form method="POST" action="{{ route('alumni.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="file">Excel / CSV / TSV / TXT</label>
                        <input id="file" type="file" name="file" class="form-control" accept=".csv,.tsv,.txt,.xlsx" required>
                    </div>
                    <input type="hidden" name="replace_existing" value="0">
                    <div class="form-check mb-3">
                        <input id="replace-existing" type="checkbox" name="replace_existing" value="1" class="form-check-input" checked>
                        <label class="form-check-label" for="replace-existing">
                            Make this workbook the complete student list
                        </label>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary" type="submit">Import File</button>
                        <a href="/samples/alumni-import-template.csv" class="btn btn-outline-secondary">Download Template</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="page-card p-0 overflow-hidden">
        @if ($hasAlumni)
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 px-4 py-3 border-bottom bg-light">
                <div class="text-secondary small">
                    Select one or more records from this page, or use select all to make cleanup faster.
                </div>

                <form method="POST" action="{{ route('alumni.bulk-destroy', request()->query()) }}" id="bulk-delete-alumni-form" class="d-flex align-items-center gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" data-bulk-delete-button>
                        Delete Selected
                    </button>
                </form>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table align-middle mb-0" data-mobile-card-table>
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center" style="width: 3.5rem;">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="select-all-alumni"
                                data-select-all-alumni
                                aria-label="Select all alumni records on this page"
                            >
                        </th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col" style="width: 14rem;">Student ID</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courseGroups as $course => $records)
                        <tr class="table-light">
                            <th colspan="5" class="text-danger fw-semibold text-center px-4 py-3">{{ $course }}</th>
                        </tr>
                        @foreach ($records as $record)
                            <tr>
                                <td class="text-center" data-label="Select">
                                <input
                                    class="form-check-input alumni-row-checkbox"
                                    type="checkbox"
                                    name="alumni_ids[]"
                                    value="{{ $record->id }}"
                                        form="bulk-delete-alumni-form"
                                        data-alumni-checkbox
                                        aria-label="Select {{ $record->full_name }} for deletion"
                                    >
                                </td>
                                <td data-label="Name">
                                    <div class="fw-semibold">{{ $record->full_name }}</div>
                                    <div class="small text-secondary">
                                        {{ $record->academic_label }}
                                    </div>
                                    @if ($record->birthday)
                                        <div class="small text-secondary mt-1">
                                            Birthday: {{ $record->birthday->format('F j, Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Email">
                                    @if ($record->email && $record->user && $record->email !== $record->user->email)
                                        <div class="small text-secondary mt-1">Alumni: {{ $record->email }}</div>
                                        <div class="small fw-semibold">Portal: {{ $record->user->email }}</div>
                                    @else
                                        <div class="small fw-semibold mt-1">{{ $record->user->email ?? $record->email ?? 'No email' }}</div>
                                    @endif
                                </td>
                                <td class="student-id-display" data-label="ID Number">{{ $record->student_id_display }}</td>
                                <td class="text-end" data-label="Actions">
                                    @if ($record->user)
                                        <a href="{{ route('users.edit', $record->user) }}" class="small text-decoration-none">Edit account</a>
                                    @else
                                        <div class="text-secondary small mb-2">Not claimed yet</div>
                                        @if ($record->email)
                                            <div class="small text-secondary mb-2">The alumnus can self-register, or admin can create the portal account manually.</div>
                                            <form method="POST" action="{{ route('alumni.portal-account', $record) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">Create Portal Account</button>
                                                </form>
                                        @else
                                            <span class="small text-danger">Add alumni email first</span>
                                        @endif
                                    @endif
                                    <div class="d-inline-flex gap-2 mobile-table-actions">
                                        <a href="{{ route('alumni.edit', ['alumnus' => $record]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" action="{{ route('alumni.destroy', ['alumnus' => $record]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this alumni record?')" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">No alumni records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($alumni->hasPages())
        <div class="mt-4">
            {{ $alumni->links() }}
        </div>
    @endif
    </div>

@push('styles')
    <style>
        /* Scoped to this view: make page cards white with black text, don't change buttons */
        .alumni-records-page .page-card {
            background: #ffffff !important;
        }

        .alumni-records-page .page-card h3,
        .alumni-records-page .page-card label,
        .alumni-records-page .page-card .form-label,
        .alumni-records-page .page-card .text-secondary,
        .alumni-records-page .page-card .small,
        .alumni-records-page .page-card .table-light th,
        .alumni-records-page .page-card .table-light td,
        .alumni-records-page .page-card .fw-semibold,
        .alumni-records-page .page-card .student-id-display,
        .alumni-records-page .page-card .landing-chip {
            color: #000000 !important;
        }

        /* Replace gold/yellow table header background with a dark blue on this page only */
        .alumni-records-page .page-card .table-light {
            --bs-table-bg: #0b45b8 !important;
            background: #0b45b8 !important;
        }

        .alumni-records-page .page-card .table-light th {
            background: #0b45b8 !important;
            border-color: #083b8f !important;
            color: #ffffff !important;
        }

        .alumni-records-page .page-card table[data-mobile-card-table] tbody tr.table-light th {
            background: #0b45b8 !important;
            border-color: #083b8f !important;
            color: #ffffff !important;
        }

        /* Ensure form controls show dark text */
        .alumni-records-page .page-card .form-control {
            color: #000000 !important;
            background-color: #fff !important;
        }

        /* Do not override button colors (preserve bootstrap button styles) */
        .alumni-records-page .page-card .btn {
            /* no changes */
        }
    </style>
@endpush

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.querySelector('[data-select-all-alumni]');
            const checkboxes = Array.from(document.querySelectorAll('[data-alumni-checkbox]'));
            const bulkDeleteForm = document.querySelector('[data-bulk-delete-button]')?.form;

            if (!selectAll || checkboxes.length === 0 || !bulkDeleteForm) {
                return;
            }

            const syncSelectAllState = () => {
                const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

                selectAll.checked = checkedCount === checkboxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            };

            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });

                selectAll.indeterminate = false;
            });

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncSelectAllState);
            });

            bulkDeleteForm.addEventListener('submit', (event) => {
                const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

                if (selectedCount === 0) {
                    event.preventDefault();
                    alert('Select at least one alumni record to delete.');
                    return;
                }

                const recordLabel = selectedCount === 1 ? 'record' : 'records';

                if (!confirm(`Delete ${selectedCount} selected alumni ${recordLabel}?`)) {
                    event.preventDefault();
                }
            });

            syncSelectAllState();
        });
    </script>
@endpush
