<?php

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('stored birthday stays unchanged while age advances on each birthday', function () {
    $this->travelTo(Carbon::parse('2026-10-16 12:00:00'));
    $record = Alumni::create([
        'student_id' => '1968-1950',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'birthday' => '1950-10-17',
        'education_level' => 'College',
        'course' => 'BA Communication',
        'year_graduated' => 1972,
    ]);
    expect($record->age)->toBe(75);

    $this->travelTo(Carbon::parse('2026-10-17 00:00:00'));
    expect($record->age)->toBe(76);
    $this->travelTo(Carbon::parse('2027-10-17 00:00:00'));
    expect($record->age)->toBe(77)
        ->and($record->fresh()->birthday->toDateString())->toBe('1950-10-17')
        ->and($record->getAttributes())->not->toHaveKey('age');
});

test('age handles leap birthdays, missing dates, and future dates', function (?string $birthday, string $today, ?int $age) {
    $this->travelTo(Carbon::parse($today.' 12:00:00'));
    expect((new Alumni(['birthday' => $birthday]))->age)->toBe($age);
})->with([
    'before leap anniversary' => ['2000-02-29', '2025-02-28', 24],
    'after leap anniversary' => ['2000-02-29', '2025-03-01', 25],
    'on leap birthday' => ['2000-02-29', '2024-02-29', 24],
    'today' => ['2026-10-17', '2026-10-17', 0],
    'missing' => [null, '2026-10-17', null],
    'future' => ['2026-10-18', '2026-10-17', null],
]);

test('alumni dashboard displays the age calculated from the saved registration birthday', function () {
    $this->travelTo(Carbon::parse('2026-10-17 12:00:00'));
    $user = User::factory()->create([
        'role' => 'alumni',
        'account_status' => 'approved',
        'portal_otp_verified_at' => now(),
        'registration_details' => [
            'first_name' => 'Maria', 'last_name' => 'Santos', 'birthday' => '1950-10-17',
        ],
    ]);

    $this->actingAs($user)->get(route('portal.dashboard'))
        ->assertOk()->assertSee('76 years old')->assertSee('October 17, 1950');
});
