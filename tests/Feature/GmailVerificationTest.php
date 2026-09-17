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

test('alumni registration creates a pending account request without OTP first', function () {
    $this->post(route('portal.register.store'), alumniRegistrationPayload())
        ->assertRedirect(route('portal.login'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'email' => 'gina.reyes@gmail.com',
        'role' => 'alumni',
        'account_status' => 'pending',
    ]);

    $this->assertDatabaseHas('alumni', [
        'email' => 'gina.reyes@gmail.com',
        'student_id' => '2026-1001',
    ]);

    expect(User::query()->where('email', 'gina.reyes@gmail.com')->first()?->portal_otp_verified_at)
        ->toBeNull();
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
