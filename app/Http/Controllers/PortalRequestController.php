<?php

namespace App\Http\Controllers;

use App\Classes\FirebaseService;
use App\Models\RecordRequest;
use App\Models\User;
use App\Notifications\RecordRequestSubmitted;
use App\Services\LinkedAccountSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PortalRequestController extends Controller
{
    public function index(LinkedAccountSyncService $syncService): View
    {
        $alumnus = $syncService->resolveOrCreateAlumniForUser(auth()->user());

        abort_if(! $alumnus, 403);

        return view('portal.requests.index', [
            'alumnus' => $alumnus,
            'requests' => $alumnus->requests()->latest()->paginate(10),
            'requestTypes' => RecordRequest::requestTypes(),
            'statusOptions' => RecordRequest::workflowStatuses(),
        ]);
    }

    public function updateNotifications(Request $request, LinkedAccountSyncService $syncService): JsonResponse
    {
        $alumnus = $syncService->resolveOrCreateAlumniForUser($request->user());

        abort_if(! $alumnus, 403);

        $latestSeenTimestamp = max(0, (int) $request->query('after', 0));

        $recentUpdates = $alumnus->requests()
            ->whereNotNull('admin_replied_at')
            ->latest('admin_replied_at')
            ->take(10)
            ->get();

        $newUpdates = $recentUpdates
            ->filter(fn (RecordRequest $recordRequest): bool => (int) ($recordRequest->admin_replied_at?->timestamp ?? 0) > $latestSeenTimestamp)
            ->sortBy(fn (RecordRequest $recordRequest): int => (int) ($recordRequest->admin_replied_at?->timestamp ?? 0))
            ->values();

        return response()->json([
            'latest_timestamp' => (int) ($recentUpdates->max(fn (RecordRequest $recordRequest): int => (int) ($recordRequest->admin_replied_at?->timestamp ?? 0)) ?? 0),
            'new' => $newUpdates->map(function (RecordRequest $recordRequest): array {
                return [
                    'id' => $recordRequest->id,
                    'request_type' => $recordRequest->request_type,
                    'year_requested' => $recordRequest->year_requested,
                    'status' => RecordRequest::workflowStatuses()[$recordRequest->status] ?? ucfirst(str_replace('_', ' ', $recordRequest->status)),
                    'admin_notes' => $recordRequest->admin_notes,
                    'updated_at' => $recordRequest->admin_replied_at?->format('M d, Y h:i A'),
                    'updated_timestamp' => (int) ($recordRequest->admin_replied_at?->timestamp ?? 0),
                    'review_url' => route('portal.dashboard'),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request, FirebaseService $firebase, LinkedAccountSyncService $syncService): RedirectResponse
    {
        $alumnus = $syncService->resolveOrCreateAlumniForUser($request->user());

        abort_if(! $alumnus, 403);

        $isStandardRequest = in_array($request->input('request_type'), ['Alumni ID', 'Year Book'], true);

        $validated = $request->validate([
            'request_type' => ['required', Rule::in(RecordRequest::requestTypes())],
            'year_requested' => 'required|integer|digits:4|min:1900|max:'.(now()->year + 1),
            'requester_name' => [
                Rule::requiredIf($isStandardRequest),
                'nullable',
                'string',
                'max:150',
            ],
            'requester_course' => [
                Rule::requiredIf($isStandardRequest),
                'nullable',
                'string',
                'max:150',
            ],
            'requester_year_graduate' => [
                Rule::requiredIf($isStandardRequest),
                'nullable',
                'integer',
                'digits:4',
                'min:1900',
                'max:'.(now()->year + 1),
            ],
            'requester_note' => [
                Rule::requiredIf($request->input('request_type') === 'Facility Use-(Message/Note)'),
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'requester_name.required' => 'Please type the name requirement.',
            'requester_course.required' => 'Please type the course requirement.',
            'requester_year_graduate.required' => 'Please type the year graduated requirement.',
            'requester_note.required' => 'Please type your facility use message or note.',
        ]);

        $requesterNote = $validated['requester_note'] ?? null;

        if ($isStandardRequest) {
            $requesterNote = implode("\n", [
                'Name: '.$validated['requester_name'],
                'Course: '.$validated['requester_course'],
                'Yr Graduate: '.$validated['requester_year_graduate'],
            ]);
        }

        $recordRequest = RecordRequest::create([
            'alumni_id' => $alumnus->id,
            'request_type' => $validated['request_type'],
            'year_requested' => $validated['year_requested'],
            'requester_note' => $requesterNote,
            'status' => 'pending',
        ]);

        $recordRequest->loadMissing('alumni');

        User::query()
            ->where('role', 'admin')
            ->get()
            ->each(function (User $admin) use ($recordRequest, $firebase): void {
                try {
                    $admin->notify(new RecordRequestSubmitted($recordRequest));
                    $firebase->sendToUser(
                        $admin,
                        'New record request',
                        ($recordRequest->alumni?->full_name ?? 'An alumni student').' submitted a '.$recordRequest->request_type.' request.',
                        route('requests.index'),
                        [
                            'kind' => 'record_request_submitted',
                            'request_id' => $recordRequest->id,
                        ]
                    );
                } catch (Throwable $exception) {
                    Log::error('Failed to notify admin about submitted record request.', [
                        'request_id' => $recordRequest->id,
                        'admin_user_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'exception' => $exception,
                    ]);
                }
            });

        return redirect()->route('portal.requests.index')->with('success', 'Your request has been submitted for school processing.');
    }
}
