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
                        @php
                            $statusLabel = $statusOptions[$recordRequest->status] ?? ucfirst(str_replace('_', ' ', $recordRequest->status));
                            $alumniName = $recordRequest->alumni?->full_name ?? 'Unknown alumni';
                            $studentId = $recordRequest->alumni?->student_id_display ?? 'No student ID';
                            $schoolLevel = $recordRequest->alumni?->education_level ?? 'Unknown';
                            $requesterNote = filled($recordRequest->requester_note) ? $recordRequest->requester_note : 'No message provided.';
                            $adminNotes = filled($recordRequest->admin_notes) ? $recordRequest->admin_notes : 'No admin update yet.';
                            $processedText = $recordRequest->processedBy
                                ? $recordRequest->processedBy->name.($recordRequest->processed_at ? ' on '.$recordRequest->processed_at->format('M d, Y h:i A') : '')
                                : 'Not processed yet.';
                        @endphp
                        <tr class="record-request-row"
                            tabindex="0"
                            role="button"
                            aria-label="View request from {{ $alumniName }}"
                            data-request-row
                            data-request-id="{{ $recordRequest->id }}"
                            data-request-alumni="{{ $alumniName }}"
                            data-request-student-id="{{ $studentId }}"
                            data-request-school-level="{{ $schoolLevel }}"
                            data-request-type="{{ $recordRequest->request_type }}"
                            data-request-year="{{ $recordRequest->year_requested }}"
                            data-request-status="{{ $statusLabel }}"
                            data-request-processed="{{ $processedText }}">
                            <td data-label="Alumni">
                                <div class="fw-semibold">{{ $recordRequest->alumni?->full_name ?? 'Unknown alumni' }}</div>
                                <div class="small text-secondary">{{ $recordRequest->alumni?->student_id_display ?? 'No student ID' }}</div>
                            </td>
                            <td data-label="School Level">{{ $recordRequest->alumni?->education_level ?? 'Unknown' }}</td>
                            <td data-label="Request">{{ $recordRequest->request_type }}</td>
                            <td data-label="Year">{{ $recordRequest->year_requested }}</td>
                            <td class="record-request-message-cell" data-label="Alumni Message">
                                <div class="record-request-message-preview">{!! $recordRequest->requester_note ? nl2br(e($recordRequest->requester_note)) : '-' !!}</div>
                                <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-request-view>
                                    View
                                </button>
                                <template data-request-message-template>
                                    {!! nl2br(e($requesterNote)) !!}
                                </template>
                            </td>
                            <td data-label="Status">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                    {{ $statusLabel }}
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
                            <td class="record-request-admin-cell" data-label="Admin Update">
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
                                <template data-request-admin-notes-template>
                                    {!! nl2br(e($adminNotes)) !!}
                                </template>
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

    <div class="modal fade" id="recordRequestDetailsModal" tabindex="-1" aria-labelledby="recordRequestDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content record-request-modal">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="recordRequestDetailsModalLabel" data-request-modal-title>Record Request</h5>
                        <div class="small text-secondary" data-request-modal-subtitle></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="record-request-detail-grid mb-4">
                        <div>
                            <div class="record-request-detail-label">Alumni</div>
                            <div class="fw-semibold" data-request-modal-alumni></div>
                            <div class="small text-secondary" data-request-modal-student-id></div>
                        </div>
                        <div>
                            <div class="record-request-detail-label">School Level</div>
                            <div data-request-modal-school-level></div>
                        </div>
                        <div>
                            <div class="record-request-detail-label">Request</div>
                            <div data-request-modal-type></div>
                        </div>
                        <div>
                            <div class="record-request-detail-label">Year</div>
                            <div data-request-modal-year></div>
                        </div>
                        <div>
                            <div class="record-request-detail-label">Status</div>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis" data-request-modal-status></span>
                        </div>
                        <div>
                            <div class="record-request-detail-label">Processed By</div>
                            <div data-request-modal-processed></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="record-request-detail-label mb-2">Alumni Message</div>
                        <div class="record-request-full-message" data-request-modal-message></div>
                    </div>

                    <div>
                        <div class="record-request-detail-label mb-2">Admin Update</div>
                        <div class="record-request-admin-note" data-request-modal-admin-notes></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @if ($requests->hasPages())
        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    @endif
@endsection

@push('styles')
    <style>
        .record-request-row {
            cursor: pointer;
            transition: background-color 0.16s ease, box-shadow 0.16s ease;
        }

        .record-request-row:hover > td,
        .record-request-row:focus-visible > td {
            background-color: rgba(11, 69, 184, 0.04) !important;
        }

        .record-request-row:focus-visible {
            outline: 3px solid rgba(11, 69, 184, 0.28);
            outline-offset: -3px;
        }

        .record-request-message-cell {
            min-width: 260px;
            max-width: 360px;
        }

        .record-request-message-preview {
            display: -webkit-box;
            overflow: hidden;
            line-height: 1.45;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            word-break: break-word;
        }

        .record-request-admin-cell {
            min-width: 320px;
        }

        .record-request-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .record-request-detail-label {
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .record-request-full-message,
        .record-request-admin-note {
            min-height: 6rem;
            padding: 1rem;
            border: 1px solid rgba(11, 69, 184, 0.18);
            border-radius: 0.75rem;
            background: rgba(11, 69, 184, 0.04);
            line-height: 1.65;
            white-space: normal;
            word-break: break-word;
        }

        .record-request-admin-note {
            min-height: 4rem;
            background: #fff;
        }

        .record-request-modal {
            border: 1px solid rgba(11, 69, 184, 0.18);
            border-radius: 0.9rem;
        }

        @media (max-width: 767.98px) {
            .record-request-row {
                cursor: default;
            }

            .record-request-message-cell {
                max-width: none;
            }

            .record-request-admin-cell {
                min-width: 0;
            }

            .record-request-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const modalElement = document.getElementById('recordRequestDetailsModal');

            if (!modalElement || !window.bootstrap) {
                return;
            }

            const modal = new bootstrap.Modal(modalElement);
            const fields = {
                title: modalElement.querySelector('[data-request-modal-title]'),
                subtitle: modalElement.querySelector('[data-request-modal-subtitle]'),
                alumni: modalElement.querySelector('[data-request-modal-alumni]'),
                studentId: modalElement.querySelector('[data-request-modal-student-id]'),
                schoolLevel: modalElement.querySelector('[data-request-modal-school-level]'),
                type: modalElement.querySelector('[data-request-modal-type]'),
                year: modalElement.querySelector('[data-request-modal-year]'),
                status: modalElement.querySelector('[data-request-modal-status]'),
                processed: modalElement.querySelector('[data-request-modal-processed]'),
                message: modalElement.querySelector('[data-request-modal-message]'),
                adminNotes: modalElement.querySelector('[data-request-modal-admin-notes]'),
            };

            const setText = (element, value) => {
                if (element) {
                    element.textContent = value || '';
                }
            };

            const openRequest = (row) => {
                const messageTemplate = row.querySelector('[data-request-message-template]');
                const adminNotesTemplate = row.querySelector('[data-request-admin-notes-template]');

                setText(fields.title, row.dataset.requestType || 'Record Request');
                setText(fields.subtitle, 'Request #' + (row.dataset.requestId || ''));
                setText(fields.alumni, row.dataset.requestAlumni);
                setText(fields.studentId, row.dataset.requestStudentId);
                setText(fields.schoolLevel, row.dataset.requestSchoolLevel);
                setText(fields.type, row.dataset.requestType);
                setText(fields.year, row.dataset.requestYear);
                setText(fields.status, row.dataset.requestStatus);
                setText(fields.processed, row.dataset.requestProcessed);

                if (fields.message && messageTemplate) {
                    fields.message.innerHTML = messageTemplate.innerHTML;
                }

                if (fields.adminNotes && adminNotesTemplate) {
                    fields.adminNotes.innerHTML = adminNotesTemplate.innerHTML;
                }

                modal.show();
            };

            document.querySelectorAll('[data-request-row]').forEach((row) => {
                row.addEventListener('click', (event) => {
                    if (event.target.closest('form, button, a, input, select, textarea, label')) {
                        return;
                    }

                    openRequest(row);
                });

                row.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    if (event.target.closest('form, button, a, input, select, textarea, label')) {
                        return;
                    }

                    event.preventDefault();
                    openRequest(row);
                });

                row.querySelectorAll('[data-request-view]').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        openRequest(row);
                    });
                });
            });
        })();
    </script>
@endpush
