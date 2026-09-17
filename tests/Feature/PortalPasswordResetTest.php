<?php

use App\Models\Alumni;
use App\Models\User;
use App\Notifications\PortalPasswordResetRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('portal login page has show password and forgot password controls', function () {
    $this->get(route('portal.login'))
        ->assertOk()
        ->assertSee('Forgot password?')
        ->assertSee('data-password-toggle="password"', false);
});

test('approved alumni can reset password from emailed code', function () {
    Notification::fake();

    $alumnus = Alumni::create([
        'student_id' => '2026-0910',
        'first_name' => 'Shanelle',
        'last_name' => 'Panganiban',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2026,
        'email' => 'shanelle.portal@gmail.com',
    ]);

    $user = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'shanelle.portal@gmail.com',
        'password' => Hash::make('oldpass12'),
        'role' => 'alumni',
        'account_status' => 'approved',
        'approved_at' => now(),
        'portal_otp_verified_at' => now(),
        'alumni_id' => $alumnus->id,
    ]);

    $this->post(route('portal.password.email'), [
        'email' => 'shanelle.portal@gmail.com',
    ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $resetToken = null;

    Notification::assertSentTo($user, PortalPasswordResetRequested::class, function (PortalPasswordResetRequested $notification) use (&$resetToken): bool {
        $resetToken = $notification->token;

        return $resetToken !== '' && str_contains($notification->resetUrl, '/portal/reset-password/');
    });

    expect($resetToken)->not->toBeNull();

    $this->get(route('portal.password.reset', [
        'token' => $resetToken,
        'email' => $user->email,
    ]))
        ->assertOk()
        ->assertSee('Create your new password');

    $this->post(route('portal.password.update'), [
        'reset_code' => $resetToken,
        'email' => $user->email,
        'password' => 'newpass12',
        'password_confirmation' => 'newpass12',
    ])
        ->assertRedirect(route('portal.login'))
        ->assertSessionHas('success');

    $this->assertCredentials([
        'email' => 'shanelle.portal@gmail.com',
        'password' => 'newpass12',
    ]);

    expect(Hash::check('oldpass12', $user->fresh()->password))->toBeFalse();
});

test('password reset email includes a code when the link cannot be opened', function () {
    Notification::fake();

    $alumnus = Alumni::create([
        'student_id' => '2026-0912',
        'first_name' => 'Rica',
        'last_name' => 'Dizon',
        'education_level' => 'College',
        'course' => 'BS Accountancy',
        'year_graduated' => 2025,
        'email' => 'rica.dizon@gmail.com',
    ]);

    $user = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'rica.dizon@gmail.com',
        'role' => 'alumni',
        'account_status' => 'approved',
        'approved_at' => now(),
        'portal_otp_verified_at' => now(),
        'alumni_id' => $alumnus->id,
    ]);

    $this->post(route('portal.password.email'), [
        'email' => 'rica.dizon@gmail.com',
    ])->assertRedirect(route('portal.password.code', ['email' => 'rica.dizon@gmail.com']));

    Notification::assertSentTo($user, PortalPasswordResetRequested::class, function (PortalPasswordResetRequested $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        return strlen($notification->token) === 6
            && collect($mail->introLines)->contains('Your 6-digit reset code is: '.$notification->token);
    });
});

test('portal password reset does not send links to non alumni accounts', function () {
    Notification::fake();

    User::factory()->create([
        'name' => 'System Admin',
        'email' => 'admin.portal@gmail.com',
        'password' => Hash::make('password12'),
        'role' => 'admin',
        'account_status' => 'approved',
        'approved_at' => now(),
    ]);

    $this->post(route('portal.password.email'), [
        'email' => 'admin.portal@gmail.com',
    ])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertNothingSent();
});

test('portal password reset requires gmail accounts', function () {
    $this->post(route('portal.password.email'), [
        'email' => 'student@example.com',
    ])->assertSessionHasErrors('email');
});

test('portal password reset link uses the request host with the configured app path', function () {
    Notification::fake();
    config(['app.url' => 'http://192.168.1.15/alumni-link/public']);

    $alumnus = Alumni::create([
        'student_id' => '2026-0911',
        'first_name' => 'Lia',
        'last_name' => 'Reyes',
        'education_level' => 'College',
        'course' => 'BS Information Systems',
        'year_graduated' => 2026,
        'email' => 'lia.reyes@gmail.com',
    ]);

    $user = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'lia.reyes@gmail.com',
        'role' => 'alumni',
        'account_status' => 'approved',
        'approved_at' => now(),
        'portal_otp_verified_at' => now(),
        'alumni_id' => $alumnus->id,
    ]);

    $this->post('http://192.168.1.18/portal/forgot-password', [
        'email' => 'lia.reyes@gmail.com',
    ])->assertSessionHas('status');

    $notification = Notification::sent($user, PortalPasswordResetRequested::class)->first();

    expect($notification)->not->toBeNull()
        ->and($notification->resetUrl)->toStartWith('http://192.168.1.18/alumni-link/public/portal/reset-password/');
});
