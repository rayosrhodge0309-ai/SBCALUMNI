<?php

namespace App\Http\Controllers;

use App\Models\RecordRequest;
use App\Services\PortalContentService;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function index(PortalContentService $contentService): View
    {
        $user = auth()->user();
        $alumnus = $user->alumni;

        abort_if(! $alumnus, 403);

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
            'announcements' => $contentService->announcements(),
            'activities' => $contentService->activities(6),
            'upcomingEvents' => $contentService->events(4),
            'statusOptions' => RecordRequest::workflowStatuses(),
        ]);
    }
}
