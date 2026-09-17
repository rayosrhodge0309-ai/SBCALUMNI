<?php

namespace App\Http\Controllers;

use App\Models\EventRegistration;
use App\Models\RecordRequest;
use App\Services\LinkedAccountSyncService;
use App\Services\PortalContentService;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function index(PortalContentService $contentService, LinkedAccountSyncService $syncService): View
    {
        $user = auth()->user();
        $alumnus = $syncService->resolveOrCreateAlumniForUser($user);

        abort_if(! $alumnus, 403);

        $upcomingEvents = $contentService->events(null);
        $eventRegistrationsByEventId = $alumnus->eventRegistrations()
            ->whereIn('event_id', $upcomingEvents->pluck('id'))
            ->get()
            ->keyBy('event_id');

        return view('portal.dashboard', [
            'alumnus' => $alumnus,
            'requestTypes' => RecordRequest::requestTypes(),
            'requestCount' => $alumnus->requests()->count(),
            'pendingCount' => $alumnus->requests()->whereIn('status', ['pending', 'processing'])->count(),
            'readyCount' => $alumnus->requests()->where('status', 'ready_for_pickup')->count(),
            'recentRequests' => $alumnus->requests()
                ->latest()
                ->take(5)
                ->get(),
            'requestUpdates' => $alumnus->requests()
                ->whereNotNull('admin_replied_at')
                ->latest('admin_replied_at')
                ->take(5)
                ->get(),
            'announcements' => $contentService->announcements(),
            'activities' => $contentService->activities(6),
            'upcomingEvents' => $upcomingEvents,
            'eventRegistrationsByEventId' => $eventRegistrationsByEventId,
            'eventRegistrationStatuses' => EventRegistration::statusOptions(),
            'statusOptions' => RecordRequest::workflowStatuses(),
        ]);
    }
}
