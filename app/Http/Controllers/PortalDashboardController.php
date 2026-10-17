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
        $linkedAlumnus = $syncService->resolveAlumniForUser($user);
        $alumnus = $linkedAlumnus ?? $user->registration_profile;

        $upcomingEvents = $contentService->events(null);
        $eventRegistrationsByEventId = $linkedAlumnus ? $linkedAlumnus->eventRegistrations()
            ->whereIn('event_id', $upcomingEvents->pluck('id'))
            ->get()
            ->keyBy('event_id') : collect();

        return view('portal.dashboard', [
            'alumnus' => $alumnus,
            'hasLinkedRecord' => $linkedAlumnus !== null,
            'requestTypes' => RecordRequest::requestTypes(),
            'requestCount' => $linkedAlumnus?->requests()->count() ?? 0,
            'pendingCount' => $linkedAlumnus?->requests()->whereIn('status', ['pending', 'processing'])->count() ?? 0,
            'readyCount' => $linkedAlumnus?->requests()->where('status', 'ready_for_pickup')->count() ?? 0,
            'recentRequests' => $linkedAlumnus?->requests()
                ->latest()
                ->take(5)
                ->get() ?? collect(),
            'requestUpdates' => $linkedAlumnus?->requests()
                ->whereNotNull('admin_replied_at')
                ->latest('admin_replied_at')
                ->take(5)
                ->get() ?? collect(),
            'announcements' => $contentService->announcements(),
            'activities' => $contentService->activities(6),
            'upcomingEvents' => $upcomingEvents,
            'eventRegistrationsByEventId' => $eventRegistrationsByEventId,
            'eventRegistrationStatuses' => EventRegistration::statusOptions(),
            'statusOptions' => RecordRequest::workflowStatuses(),
        ]);
    }
}
