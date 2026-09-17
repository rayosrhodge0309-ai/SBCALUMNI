<?php

use App\Models\Alumni;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\EventRegistrationReplied;
use App\Notifications\EventRegistrationSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function eventRegistrationPayload(Alumni $alumnus, User $user, array $overrides = []): array
{
    return array_merge([
        'event_registration_event_id' => $overrides['event_registration_event_id'] ?? null,
        'attendee_name' => $alumnus->full_name,
        'attendee_email' => $user->email,
        'attendee_student_id' => $alumnus->student_id_display,
        'attendee_course' => $alumnus->course,
        'attendee_year_graduated' => $alumnus->year_graduated,
        'attendee_contact_number' => '09171234567',
        'attendee_note' => 'I will attend with updated alumni details.',
    ], $overrides);
}

test('landing event details show a registration action', function () {
    $event = Event::create([
        'title' => 'SBC Grand Alumni Homecoming',
        'description' => 'A special gathering for alumni students.',
        'event_date' => today()->addDays(10),
        'location' => 'SBC Gymnasium',
        'is_published' => true,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('SBC Grand Alumni Homecoming')
        ->assertSee('Register to Event')
        ->assertSee('Failed to register');
});

test('guest alumni cannot register for an event without logging in first', function () {
    Notification::fake();

    $event = Event::create([
        'title' => 'Career Guidance',
        'description' => 'Career guidance for alumni students.',
        'event_date' => today()->addDays(5),
        'location' => 'College Auditorium',
        'is_published' => true,
    ]);

    $this->from(route('home'))->post(route('portal.events.registrations.store', $event), [
        'event_registration_event_id' => $event->id,
        'attendee_name' => 'Shanelle Panganiban',
        'attendee_email' => 'shanelle.panganiban@gmail.com',
        'attendee_student_id' => '12-7865-367',
        'attendee_course' => 'BS Information Technology',
        'attendee_year_graduated' => 2025,
        'attendee_contact_number' => '09171234567',
        'attendee_note' => 'I want to join this event.',
    ])
        ->assertRedirect(route('home'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('warning', 'Failed to register. Please log in or create an alumni account first.');

    $alumnus = Alumni::query()->where('email', 'shanelle.panganiban@gmail.com')->first();

    expect($alumnus)->toBeNull();

    $this->assertDatabaseCount('event_registrations', 0);
    Notification::assertNothingSent();
});

test('alumni can register for posted events and admins can reply', function () {
    Notification::fake();

    $alumnus = Alumni::create([
        'student_id' => '2020-0815',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2024,
        'email' => 'maria.alumni@gmail.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'maria.alumni@gmail.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $admin = User::factory()->create([
        'name' => 'Events Admin',
        'email' => 'events-admin@example.com',
        'role' => 'admin',
    ]);

    $event = Event::create([
        'title' => 'Alumni Homecoming',
        'description' => 'Annual alumni gathering at the campus.',
        'event_date' => today()->addDays(10),
        'location' => 'SBC Gymnasium',
        'is_published' => true,
    ]);

    $this->actingAs($alumniUser);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->post(route('portal.events.registrations.store', $event), eventRegistrationPayload($alumnus, $alumniUser, [
        'event_registration_event_id' => $event->id,
    ]))
        ->assertRedirect(route('portal.dashboard'));

    $registration = EventRegistration::first();

    expect($registration)->not->toBeNull();

    $this->assertDatabaseHas('event_registrations', [
        'event_id' => $event->id,
        'alumni_id' => $alumnus->id,
        'user_id' => $alumniUser->id,
        'attendee_name' => 'Maria Santos',
        'attendee_email' => 'maria.alumni@gmail.com',
        'attendee_student_id' => '2020-0815',
        'attendee_course' => 'BS Information Technology',
        'attendee_year_graduated' => 2024,
        'attendee_contact_number' => '09171234567',
        'status' => EventRegistration::STATUS_PENDING,
    ]);

    Notification::assertSentTo($admin, EventRegistrationSubmitted::class);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->post(route('portal.events.registrations.store', $event), eventRegistrationPayload($alumnus, $alumniUser, [
        'event_registration_event_id' => $event->id,
    ]))
        ->assertRedirect(route('portal.dashboard'));

    $this->assertDatabaseCount('event_registrations', 1);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('Alumni Homecoming')
        ->assertSee('Registration: Pending');

    $this->actingAs($admin);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee('Event Registrants')
        ->assertDontSee('Maria Santos')
        ->assertDontSee('Send Reply');

    $this->get(route('event-registrations.index'))
        ->assertOk()
        ->assertSee('Event Registrants')
        ->assertSee('Maria Santos')
        ->assertSee('09171234567')
        ->assertSee('BS Information Technology')
        ->assertSee('Send Reply');

    $this->patch(route('event-registrations.reply', $registration), [
        'status' => EventRegistration::STATUS_REGISTERED,
        'admin_reply' => 'You are confirmed for the Alumni Homecoming.',
    ])->assertRedirect(route('event-registrations.index'));

    Notification::assertSentTo($alumniUser, EventRegistrationReplied::class, function (EventRegistrationReplied $notification) use ($alumniUser): bool {
        $mail = $notification->toMail($alumniUser);

        return collect($mail->introLines)
            ->contains(fn (string $line): bool => str_contains($line, "You're already registered to event: Alumni Homecoming."));
    });

    $this->assertDatabaseHas('event_registrations', [
        'id' => $registration->id,
        'status' => EventRegistration::STATUS_REGISTERED,
        'admin_reply' => 'You are confirmed for the Alumni Homecoming.',
        'replied_by' => $admin->id,
    ]);

    $this->actingAs($alumniUser);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('Registration: Registered')
        ->assertSee('You are confirmed for the Alumni Homecoming.');
});

test('event registration requires alumni details before saving', function () {
    $alumnus = Alumni::create([
        'student_id' => '2020-0816',
        'first_name' => 'Ana',
        'last_name' => 'Cruz',
        'education_level' => 'College',
        'course' => 'BS Education',
        'year_graduated' => 2023,
        'email' => 'ana.cruz@gmail.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'ana.cruz@gmail.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $event = Event::create([
        'title' => 'Career Guidance',
        'description' => 'Career guidance for alumni students.',
        'event_date' => today()->addDays(5),
        'location' => 'College Auditorium',
        'is_published' => true,
    ]);

    $this->actingAs($alumniUser);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->post(route('portal.events.registrations.store', $event), [
        'event_registration_event_id' => $event->id,
        'attendee_name' => '',
        'attendee_email' => 'fake@example.com',
    ])->assertSessionHasErrors([
        'attendee_name',
        'attendee_email',
        'attendee_student_id',
        'attendee_course',
        'attendee_year_graduated',
        'attendee_contact_number',
    ]);

    $this->assertDatabaseCount('event_registrations', 0);
});

test('admin reply emails public event registrants without portal accounts', function () {
    Notification::fake();

    $admin = User::factory()->create([
        'name' => 'Events Admin',
        'email' => 'events-admin@example.com',
        'role' => 'admin',
    ]);

    $alumnus = Alumni::create([
        'student_id' => '12-7865-367',
        'first_name' => 'Shanelle',
        'last_name' => 'Panganiban',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2025,
        'email' => 'shanelle.panganiban@gmail.com',
    ]);

    $event = Event::create([
        'title' => 'Career Guidance',
        'description' => 'Career guidance for alumni students.',
        'event_date' => today()->addDays(5),
        'location' => 'College Auditorium',
        'is_published' => true,
    ]);

    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'alumni_id' => $alumnus->id,
        'user_id' => null,
        'attendee_name' => 'Shanelle Panganiban',
        'attendee_email' => 'shanelle.panganiban@gmail.com',
        'attendee_student_id' => '12-7865-367',
        'attendee_course' => 'BS Information Technology',
        'attendee_year_graduated' => 2025,
        'attendee_contact_number' => '09171234567',
        'status' => EventRegistration::STATUS_PENDING,
        'registered_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patch(route('event-registrations.reply', $registration), [
            'status' => EventRegistration::STATUS_REGISTERED,
            'admin_reply' => 'You are confirmed for the Career Guidance event.',
        ])
        ->assertRedirect(route('event-registrations.index'));

    Notification::assertSentOnDemand(EventRegistrationReplied::class, function (EventRegistrationReplied $notification, array $channels, object $notifiable): bool {
        return $channels === ['mail']
            && ($notifiable->routes['mail'] ?? null) === 'shanelle.panganiban@gmail.com';
    });
});
