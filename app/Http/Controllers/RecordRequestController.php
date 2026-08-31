<?php

namespace App\Http\Controllers;

use App\Classes\FirebaseService;
use App\Models\RecordRequest;
use App\Notifications\RecordRequestUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

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

    public function pendingNotifications(Request $request): JsonResponse
    {
        $latestSeenId = max(0, (int) $request->query('after', 0));

        $recentPendingRequests = RecordRequest::query()
            ->with('alumni')
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        $newPendingRequests = $recentPendingRequests
            ->filter(fn (RecordRequest $recordRequest): bool => $recordRequest->id > $latestSeenId)
            ->sortBy('id')
            ->values();

        return response()->json([
            'count' => RecordRequest::query()
                ->where('status', 'pending')
                ->count(),
            'latest_id' => (int) ($recentPendingRequests->max('id') ?? 0),
            'new' => $newPendingRequests->map(function (RecordRequest $recordRequest): array {
                return [
                    'id' => $recordRequest->id,
                    'alumni_name' => $recordRequest->alumni?->full_name ?? 'Unknown alumni',
                    'student_id' => $recordRequest->alumni?->student_id_display ?? 'No student ID',
                    'request_type' => $recordRequest->request_type,
                    'year_requested' => $recordRequest->year_requested,
                    'requester_note' => $recordRequest->requester_note,
                    'submitted_date' => $recordRequest->created_at?->format('F d, Y'),
                    'submitted_time' => $recordRequest->created_at?->format('h:i A'),
                    'review_url' => route('requests.index'),
                ];
            })->values(),
        ]);
    }

    public function updateStatus(Request $request, RecordRequest $recordRequest, FirebaseService $firebase): RedirectResponse
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
            'admin_replied_at' => now(),
        ]);

        $recordRequest->loadMissing('alumni.user');
        $alumniUser = $recordRequest->alumni?->user;

        if ($alumniUser) {
            try {
                $freshRecordRequest = $recordRequest->fresh(['alumni']);

                $alumniUser->notify(new RecordRequestUpdated($freshRecordRequest));
                $firebase->sendToUser(
                    $alumniUser,
                    'Request update from admin',
                    'Your '.$freshRecordRequest->request_type.' request is now '.(RecordRequest::workflowStatuses()[$freshRecordRequest->status] ?? ucfirst(str_replace('_', ' ', $freshRecordRequest->status))).'.',
                    route('portal.dashboard'),
                    [
                        'kind' => 'record_request_updated',
                        'request_id' => $freshRecordRequest->id,
                    ]
                );
            } catch (Throwable $exception) {
                Log::error('Failed to notify alumni about record request update.', [
                    'request_id' => $recordRequest->id,
                    'user_id' => $alumniUser->id,
                    'email' => $alumniUser->email,
                    'exception' => $exception,
                ]);

                return redirect()
                    ->route('requests.index')
                    ->with('warning', 'Request status updated, but the alumni email notification could not be sent.');
            }
        }

        return redirect()->route('requests.index')->with('success', 'Request status updated successfully.');
    }
}
