<?php

namespace App\Http\Controllers;

use App\Models\RecordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortalRequestController extends Controller
{
    public function index(): View
    {
        $alumnus = auth()->user()->alumni;

        abort_if(! $alumnus, 403);

        return view('portal.requests.index', [
            'alumnus' => $alumnus,
            'requests' => $alumnus->requests()->latest()->paginate(10),
            'requestTypes' => RecordRequest::requestTypes(),
            'statusOptions' => RecordRequest::workflowStatuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $alumnus = $request->user()->alumni;

        abort_if(! $alumnus, 403);

        $validated = $request->validate([
            'request_type' => ['required', Rule::in(RecordRequest::requestTypes())],
            'year_requested' => 'required|integer|digits:4|min:1900|max:'.(now()->year + 1),
        ]);

        RecordRequest::create([
            'alumni_id' => $alumnus->id,
            'request_type' => $validated['request_type'],
            'year_requested' => $validated['year_requested'],
            'status' => 'pending',
        ]);

        return redirect()->route('portal.requests.index')->with('success', 'Your request has been submitted for school processing.');
    }
}
