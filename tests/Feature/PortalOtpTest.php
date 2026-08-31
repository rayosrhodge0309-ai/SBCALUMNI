<?php

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('unverified alumni are redirected to otp verification before dashboard access', function () {
    $alumnus = Alumni::create([
        'student_id' => '2020-0009',
        'first_name' => 'Kyla',
        'last_name' => 'Buenafe',
        'education_level' => 'College',
        'course' => 'BSIT',
        'year_graduated' => 2027,
        'email' => 'kyla@example.com',
    ]);

    $user = User::factory()->create([
        'email' => 'kyla@example.com',
        'role' => 'alumni',
        'account_status' => 'pending',
        'alumni_id' => $alumnus->id,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get('/portal/dashboard')
        ->assertRedirectToRoute('portal.otp.create');
});

test('verified alumni can open the dashboard directly', function () {
    $alumnus = Alumni::create([
        'student_id' => '2020-0010',
        'first_name' => 'Lia',
        'last_name' => 'Garcia',
        'education_level' => 'College',
        'course' => 'BSBA',
        'year_graduated' => 2024,
        'email' => 'lia@example.com',
    ]);

    $user = User::factory()->create([
        'email' => 'lia@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
        'email_verified_at' => now(),
        'portal_otp_verified_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get('/portal/dashboard')
        ->assertOk();
});

test('approved alumni without a linked record can open the dashboard', function () {
    $user = User::factory()->create([
        'email' => 'unlinked-alumni@example.com',
        'name' => 'Unlinked Alumni',
        'role' => 'alumni',
        'account_status' => 'approved',
        'approved_at' => now(),
        'alumni_id' => null,
        'email_verified_at' => now(),
        'portal_otp_verified_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get('/portal/dashboard')
        ->assertOk()
        ->assertSee('Unlinked Alumni');

    $user->refresh();

    expect($user->alumni_id)->not->toBeNull()
        ->and($user->alumni?->student_id)->toBe('UNLINKED-'.$user->id);
});

test('approved alumni without a linked record can open requests', function () {
    $user = User::factory()->create([
        'email' => 'unlinked-requests@example.com',
        'name' => 'Request Alumni',
        'role' => 'alumni',
        'account_status' => 'approved',
        'approved_at' => now(),
        'alumni_id' => null,
        'email_verified_at' => now(),
        'portal_otp_verified_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get('/portal/requests')
        ->assertOk();

    expect($user->fresh()->alumni_id)->not->toBeNull();
});

test('alumni otp verification stores permanent verification and allows dashboard access', function () {
    $alumnus = Alumni::create([
        'student_id' => '2020-0011',
        'first_name' => 'Mia',
        'last_name' => 'Santos',
        'education_level' => 'College',
        'course' => 'BSEd',
        'year_graduated' => 2025,
        'email' => 'mia@example.com',
    ]);

    $user = User::factory()->create([
        'email' => 'mia@example.com',
        'role' => 'alumni',
        'account_status' => 'pending',
        'alumni_id' => $alumnus->id,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    $this->withSession([
        'portal_otp_code_hash' => Hash::make('123456'),
        'portal_otp_code_expires_at' => now()->addMinutes(5)->timestamp,
        'portal_otp_attempts' => 0,
    ])->post('/portal/verify-otp', [
        'otp' => '123456',
    ])->assertRedirectToRoute('portal.dashboard');

    expect($user->fresh()->portal_otp_verified_at)->not->toBeNull();

    $this->get('/portal/dashboard')->assertOk();
});

test('approved alumni login skips otp and marks account verified', function () {
    $alumnus = Alumni::create([
        'student_id' => '2020-0012',
        'first_name' => 'Nora',
        'last_name' => 'Diaz',
        'education_level' => 'College',
        'course' => 'BSN',
        'year_graduated' => 2026,
        'email' => 'nora@example.com',
    ]);

    $user = User::factory()->create([
        'email' => 'nora@example.com',
        'password' => 'password12',
        'role' => 'alumni',
        'account_status' => 'approved',
        'approved_at' => now(),
        'portal_otp_verified_at' => null,
        'alumni_id' => $alumnus->id,
        'email_verified_at' => now(),
    ]);

    $this->post('/portal/login', [
        'email' => 'nora@example.com',
        'password' => 'password12',
    ])->assertRedirectToRoute('portal.dashboard');

    expect($user->fresh()->portal_otp_verified_at)->not->toBeNull();
});

test('alumni login skips otp after first successful otp verification', function () {
    $alumnus = Alumni::create([
        'student_id' => '2020-0013',
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'education_level' => 'College',
        'course' => 'BSCS',
        'year_graduated' => 2025,
        'email' => 'ana@example.com',
    ]);

    $user = User::factory()->create([
        'email' => 'ana@example.com',
        'password' => 'password12',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
        'email_verified_at' => now(),
        'portal_otp_verified_at' => now(),
    ]);

    $this->post('/portal/login', [
        'email' => $user->email,
        'password' => 'password12',
    ])->assertRedirectToRoute('portal.dashboard');
});
