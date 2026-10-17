<?php

use App\Models\Alumni;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('admin alumni records accept 1920 and reject earlier graduation years', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $details = [
        'student_id' => '1920-0001',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
    ];

    $this->post(route('alumni.store'), $details + ['year_graduated' => 1919])
        ->assertSessionHasErrors('year_graduated');

    $this->post(route('alumni.store'), $details + ['year_graduated' => 1920])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('alumni.index'));

    expect(Alumni::query()->sole()->year_graduated)->toBe(1920);
});

test('record requests accept 1920 for both year fields and reject 1919', function () {
    Notification::fake();

    $alumnus = Alumni::create([
        'student_id' => '1920-0002',
        'first_name' => 'Nina',
        'last_name' => 'Reyes',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 1920,
        'email' => 'nina.year@gmail.com',
    ]);
    $user = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => $alumnus->email,
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);
    $this->actingAs($user)->withSession(['portal_otp_verified_user_id' => $user->id]);

    $details = [
        'request_type' => 'Alumni ID',
        'year_requested' => 1920,
        'requester_name' => $alumnus->full_name,
        'requester_course' => $alumnus->course,
        'requester_year_graduate' => 1920,
    ];

    $this->post(route('portal.requests.store'), array_replace($details, ['year_requested' => 1919]))
        ->assertSessionHasErrors('year_requested');
    $this->post(route('portal.requests.store'), array_replace($details, ['requester_year_graduate' => 1919]))
        ->assertSessionHasErrors('requester_year_graduate');
    $this->post(route('portal.requests.store'), $details)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('portal.requests.index'));

    expect(RecordRequest::query()->sole()->year_requested)->toBe(1920);
});

test('event registration accepts 1920 and rejects earlier or future out of range years', function () {
    Notification::fake();

    $alumnus = Alumni::create([
        'student_id' => '1920-0003',
        'first_name' => 'Ana',
        'last_name' => 'Cruz',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 1920,
        'email' => 'ana.year@gmail.com',
    ]);
    $user = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => $alumnus->email,
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);
    $event = Event::create([
        'title' => 'Alumni Homecoming',
        'description' => 'Annual gathering',
        'event_date' => today()->addDays(10),
        'is_published' => true,
    ]);
    $this->actingAs($user)->withSession(['portal_otp_verified_user_id' => $user->id]);

    $details = [
        'event_registration_event_id' => $event->id,
        'attendee_name' => $alumnus->full_name,
        'attendee_email' => $user->email,
        'attendee_student_id' => $alumnus->student_id,
        'attendee_course' => $alumnus->course,
        'attendee_year_graduated' => 1920,
        'attendee_contact_number' => '09171234567',
    ];

    foreach ([1919, now()->year + 2] as $invalidYear) {
        $this->post(route('portal.events.registrations.store', $event), array_replace($details, ['attendee_year_graduated' => $invalidYear]))
            ->assertSessionHasErrors('attendee_year_graduated');
    }

    $this->post(route('portal.events.registrations.store', $event), $details)
        ->assertSessionHasNoErrors();

    expect(EventRegistration::query()->sole()->attendee_year_graduated)->toBe(1920);
});

test('alumni imports keep 1920 graduation years but clear earlier year values', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $file = UploadedFile::fake()->createWithContent(
        'alumni-years.csv',
        "student_id,first_name,last_name,education_level,course,year_graduated\n".
        "1920-0004,Maria,Santos,College,BS Information Technology,1920\n".
        "1919-0005,Juan,Cruz,College,BS Information Technology,1919\n"
    );

    $this->post(route('alumni.import'), ['file' => $file])
        ->assertRedirect(route('alumni.index'));

    expect(Alumni::query()->where('student_id', '1920-0004')->sole()->year_graduated)->toBe(1920)
        ->and(Alumni::query()->where('student_id', '1919-0005')->sole()->year_graduated)->toBe(0);
});
