<?php

namespace App\Http\Controllers;

use App\Classes\FirebaseService;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\EventRegistrationReplied;
use App\Notifications\EventRegistrationSubmitted;
use App\Services\LinkedAccountSyncService;
use App\Support\GmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EventRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $statusOptions = EventRegistration::statusOptions();
        $selectedStatus = $request->query('status');
        $selectedEventId = (int) $request->query('event_id', 0);

        if (! is_string($selectedStatus) || ! array_key_exists($selectedStatus, $statusOptions)) {
            $selectedStatus = null;
        }

        $events = Event::query()
            ->withCount('registrations')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();

        $registrations = EventRegistration::query()
            ->with(['event', 'alumni', 'user', 'repliedBy'])
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus))
            ->when($selectedEventId > 0, fn ($query) => $query->where('event_id', $selectedEventId))
            ->latest('registered_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('event-registrations.index', [
            'registrations' => $registrations,
            'events' => $events,
            'statusOptions' => $statusOptions,
            'selectedStatus' => $selectedStatus,
            'selectedEventId' => $selectedEventId,
            'pendingRegistrationCount' => EventRegistration::query()
                ->where('status', EventRegistration::STATUS_PENDING)
                ->count(),
        ]);
    }

    public function store(
        Request $request,
        Event $event,
        FirebaseService $firebase,
        LinkedAccountSyncService $syncService
    ): RedirectResponse {
        abort_unless(
            $event->is_published && $event->event_date && $event->event_date->greaterThanOrEqualTo(today()),
            404
        );

        $user = $request->user();
        abort_if($user?->isAdmin(), 403);

        if (! $user?->isAlumni()) {
            return $this->redirectAfterRegistration($request)
                ->with('warning', 'Failed to register. Please log in or create an alumni account first.');
        }

        $emailRules = [
            'required',
            'string',
            'email',
            'max:255',
            GmailAddress::validationRule(),
        ];

        if ($user?->isAlumni()) {
            $emailRules[] = function (string $attribute, mixed $value, callable $fail) use ($user): void {
                if (GmailAddress::normalize($value) !== GmailAddress::normalize($user->email)) {
                    $fail('Please use the same verified Gmail account connected to your alumni portal account.');
                }
            };
        }

        $validated = $request->validate([
            'event_registration_event_id' => ['required', 'integer', 'in:'.$event->id],
            'attendee_name' => ['required', 'string', 'max:255'],
            'attendee_email' => $emailRules,
            'attendee_student_id' => ['required', 'string', 'max:100'],
            'attendee_course' => ['required', 'string', 'max:255'],
            'attendee_year_graduated' => ['required', 'integer', 'digits:4', 'min:1900', 'max:2100'],
            'attendee_contact_number' => ['required', 'string', 'max:50'],
            'attendee_note' => ['nullable', 'string', 'max:2000'],
        ], [
            'event_registration_event_id.in' => 'Please refresh the page and try registering again.',
            'attendee_name.required' => 'Please enter your full name.',
            'attendee_email.required' => 'Please enter your Gmail account.',
            'attendee_student_id.required' => 'Please enter your student ID.',
            'attendee_course.required' => 'Please enter your course or strand.',
            'attendee_year_graduated.required' => 'Please enter your year graduated.',
            'attendee_contact_number.required' => 'Please enter your contact number.',
        ]);

        unset($validated['event_registration_event_id']);
        $validated['attendee_email'] = GmailAddress::normalize($validated['attendee_email']);
        $validated['attendee_note'] = $validated['attendee_note'] ?? null;

        $alumnus = $syncService->resolveOrCreateAlumniForUser($user);

        abort_if(! $alumnus, 403);

        $registration = EventRegistration::query()->firstOrCreate(
            [
                'event_id' => $event->id,
                'alumni_id' => $alumnus->id,
            ],
            [
                'user_id' => $user?->id,
                'attendee_name' => $validated['attendee_name'],
                'attendee_email' => $validated['attendee_email'],
                'attendee_student_id' => $validated['attendee_student_id'],
                'attendee_course' => $validated['attendee_course'],
                'attendee_year_graduated' => $validated['attendee_year_graduated'],
                'attendee_contact_number' => $validated['attendee_contact_number'],
                'attendee_note' => $validated['attendee_note'],
                'status' => EventRegistration::STATUS_PENDING,
                'registered_at' => now(),
            ]
        );

        if (! $registration->wasRecentlyCreated) {
            return $this->redirectAfterRegistration($request)
                ->with('warning', 'You are already registered or waiting for admin confirmation for this event.');
        }

        $registration->loadMissing(['event', 'alumni', 'user']);

        User::query()
            ->where('role', 'admin')
            ->get()
            ->each(function (User $admin) use ($registration, $firebase): void {
                try {
                    $admin->notify(new EventRegistrationSubmitted($registration));
                    $firebase->sendToUser(
                        $admin,
                        'New event registration',
                        ($registration->attendee_name ?: ($registration->alumni?->full_name ?? 'An alumni student')).' registered for '.$registration->event?->title.'.',
                        route('event-registrations.index'),
                        [
                            'kind' => 'event_registration_submitted',
                            'event_registration_id' => $registration->id,
                            'event_id' => $registration->event_id,
                        ]
                    );
                } catch (Throwable $exception) {
                    Log::error('Failed to notify admin about submitted event registration.', [
                        'event_registration_id' => $registration->id,
                        'admin_user_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'exception' => $exception,
                    ]);
                }
            });

        return $this->redirectAfterRegistration($request)
            ->with('success', 'Your event registration was sent to the administrator.');
    }

    public function reply(Request $request, EventRegistration $eventRegistration, FirebaseService $firebase): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(EventRegistration::statusOptions()))],
            'admin_reply' => 'nullable|string|max:5000',
        ]);

        $status = $validated['status'];
        $adminReply = $validated['admin_reply'] ?? null;

        if (! filled($adminReply) && $status === EventRegistration::STATUS_REGISTERED) {
            $adminReply = "You're already registered to event: ".$eventRegistration->event?->title.'.';
        }

        $eventRegistration->update([
            'status' => $status,
            'admin_reply' => $adminReply,
            'replied_by' => $request->user()->id,
            'replied_at' => now(),
        ]);

        $freshRegistration = $eventRegistration->fresh(['event', 'alumni.user', 'user']);
        $alumniUser = $freshRegistration?->user;
        $attendeeEmail = GmailAddress::normalize($freshRegistration?->attendee_email);

        if ($freshRegistration && ($alumniUser || GmailAddress::isAllowed($attendeeEmail))) {
            try {
                if ($alumniUser) {
                    $alumniUser->notify(new EventRegistrationReplied($freshRegistration));
                    $firebase->sendToUser(
                        $alumniUser,
                        'Event registration update',
                        'Your registration for '.$freshRegistration->event?->title.' is now '.$freshRegistration->status_label.'.',
                        route('portal.dashboard'),
                        [
                            'kind' => 'event_registration_replied',
                            'event_registration_id' => $freshRegistration->id,
                            'event_id' => $freshRegistration->event_id,
                        ]
                    );
                } else {
                    Notification::route('mail', $attendeeEmail)
                        ->notify(new EventRegistrationReplied($freshRegistration));
                }
            } catch (Throwable $exception) {
                Log::error('Failed to notify alumni about event registration reply.', [
                    'event_registration_id' => $eventRegistration->id,
                    'user_id' => $alumniUser?->id,
                    'email' => $alumniUser?->email ?: $attendeeEmail,
                    'exception' => $exception,
                ]);

                return redirect()
                    ->route('event-registrations.index')
                    ->with('warning', 'Registration reply saved, but the alumni email notification could not be sent.');
            }
        }

        return redirect()
            ->route('event-registrations.index')
            ->with('success', 'Registration reply sent to the alumni student.');
    }

    private function redirectAfterRegistration(Request $request): RedirectResponse
    {
        return $request->user()?->isAlumni()
            ? redirect()->route('portal.dashboard')
            : back();
    }
}
