<?php

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function alumniRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'student_id' => '2026-1001',
        'first_name' => 'Gina',
        'last_name' => 'Reyes',
        'birthday' => '2004-05-20',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2026,
        'email' => 'gina.reyes@gmail.com',
        'contact_number' => '09123456789',
        'address' => 'Batangas City',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ], $overrides);
}

test('alumni registration rejects non Gmail addresses', function () {
    $this->post(route('portal.register.store'), alumniRegistrationPayload([
        'email' => 'gina@example.com',
    ]))->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('users', ['email' => 'gina@example.com']);
    $this->assertDatabaseMissing('alumni', ['email' => 'gina@example.com']);
});

test('registration lists the SBC programs for each school level', function () {
    $this->get(route('portal.register'))
        ->assertOk()
        ->assertSee('Grade 6')
        ->assertSee('Grade 10')
        ->assertSee('Grade 12 - HUMSS')
        ->assertSee('Bachelor of Secondary Education - Mathematics')
        ->assertSee('BS Information Technology');
});

test('registration accepts an SBC strand and rejects a course from another level', function () {
    $this->post(route('portal.register.store'), alumniRegistrationPayload([
        'education_level' => 'Senior High School',
        'course' => 'Grade 12 - STEM',
    ]))->assertRedirect(route('portal.login'));

    $details = User::query()->where('email', 'gina.reyes@gmail.com')->firstOrFail()->registration_details;
    expect($details['education_level'])->toBe('Senior High School')
        ->and($details['course'])->toBe('Grade 12 - STEM');
    $this->assertDatabaseCount('alumni', 0);

    $this->post(route('portal.register.store'), alumniRegistrationPayload([
        'student_id' => '2026-1002',
        'email' => 'another.alumnus@gmail.com',
        'course' => 'Grade 12 - STEM',
    ]))->assertSessionHasErrors('course');
});

test('alumni registration creates a pending account request without OTP first', function () {
    $this->post(route('portal.register.store'), alumniRegistrationPayload())
        ->assertRedirect(route('portal.login'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'email' => 'gina.reyes@gmail.com',
        'role' => 'alumni',
        'account_status' => 'pending',
    ]);

    $this->assertDatabaseCount('alumni', 0);
    expect(User::query()->where('email', 'gina.reyes@gmail.com')->firstOrFail()->registration_details['student_id'])->toBe('2026-1001');

    expect(User::query()->where('email', 'gina.reyes@gmail.com')->first()?->portal_otp_verified_at)
        ->toBeNull();
});

test('alumni can register without a student ID', function () {
    $this->get(route('portal.register'))
        ->assertOk()
        ->assertSee('Student ID')
        ->assertSee('(Optional)')
        ->assertDontSee('data-student-id-format required', false);

    $this->post(route('portal.register.store'), alumniRegistrationPayload([
        'student_id' => '',
        'email' => 'forgot.id@gmail.com',
    ]))->assertRedirect(route('portal.login'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $user = User::query()->where('email', 'forgot.id@gmail.com')->firstOrFail();
    expect($user->registration_details['student_id'])->toBeNull()
        ->and($user->alumni_id)->toBeNull();
    $this->assertDatabaseCount('alumni', 0);

    $this->assertDatabaseHas('users', [
        'email' => 'forgot.id@gmail.com',
        'role' => 'alumni',
        'account_status' => 'pending',
    ]);
});

test('alumni registration stores an optional maiden name', function () {
    $this->get(route('portal.register'))
        ->assertOk()
        ->assertSee('Maiden Name')
        ->assertSee('name="maiden_name"', false);

    $this->post(route('portal.register.store'), alumniRegistrationPayload([
        'student_id' => '2026-1003',
        'email' => 'married.alumna@gmail.com',
        'maiden_name' => 'Santos',
    ]))->assertRedirect(route('portal.login'));

    expect(User::query()->where('email', 'married.alumna@gmail.com')->firstOrFail()->registration_details['maiden_name'])->toBe('Santos');
    $this->assertDatabaseCount('alumni', 0);
});

test('alumni login rejects non Gmail accounts', function () {
    $alumnus = Alumni::create([
        'student_id' => '2026-1002',
        'first_name' => 'Nico',
        'last_name' => 'Cruz',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2026,
        'email' => 'nico@example.com',
    ]);

    User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'nico@example.com',
        'password' => 'password12',
        'role' => 'alumni',
        'account_status' => 'approved',
        'approved_at' => now(),
        'portal_otp_verified_at' => now(),
        'alumni_id' => $alumnus->id,
    ]);

    $this->post(route('portal.login.attempt'), [
        'email' => 'nico@example.com',
        'password' => 'password12',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
