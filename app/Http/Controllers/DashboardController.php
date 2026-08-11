<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Alumni;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\RecordRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'alumniCount' => Alumni::count(),
            'requestCount' => RecordRequest::count(),
            'pendingRequestCount' => RecordRequest::whereIn('status', ['pending', 'processing'])->count(),
            'readyForPickupCount' => RecordRequest::where('status', 'ready_for_pickup')->count(),
            'eventCount' => Event::count(),
            'announcementCount' => Announcement::count(),
            'activityCount' => Activity::count(),
            'upcomingEvents' => Event::whereDate('event_date', '>=', today())
                ->orderBy('event_date')
                ->take(5)
                ->get(),
            'recentAnnouncements' => Announcement::query()
                ->latest('published_at')
                ->latest()
                ->take(4)
                ->get(),
            'recentActivities' => Activity::query()
                ->orderBy('activity_date')
                ->latest('id')
                ->take(4)
                ->get(),
            'recentAlumni' => Alumni::latest()->take(5)->get(),
            'pendingRequests' => RecordRequest::with('alumni')
                ->whereIn('status', ['pending', 'processing', 'ready_for_pickup'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
