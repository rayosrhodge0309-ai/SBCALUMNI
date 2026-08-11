<?php

namespace App\Http\Controllers;

use App\Models\RecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecordRequestController extends Controller
{
    public function index(): View
    {
        $requests = RecordRequest::with(['alumni', 'processedBy'])
            ->latest()
            ->paginate(10);

        return view('admin.requests.index', [
            'requests' => $requests,
            'statusOptions' => RecordRequest::workflowStatuses(),
        ]);
    }

    public function updateStatus(Request $request, RecordRequest $recordRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(RecordRequest::workflowStatuses()))],
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $status = $validated['status'];

        $recordRequest->update([
            'status' => $status,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'processed_by' => $request->user()->id,
            'processed_at' => $status === 'pending' ? null : now(),
        ]);

        return redirect()->route('requests.index')->with('success', 'Request status updated successfully.');
    }
}
