<?php

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('alumni mobile home dock goes to the landing page', function () {
    $alumnus = Alumni::create([
        'student_id' => '2020-0999',
        'first_name' => 'Alumni',
        'last_name' => 'Student',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2024,
        'email' => 'student@example.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'student@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
        'account_status' => 'approved',
        'approved_at' => now(),
        'portal_otp_verified_at' => now(),
    ]);

    $this->actingAs($alumniUser)
        ->withSession([
            'portal_otp_verified_user_id' => $alumniUser->id,
        ])
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('home').'" class="mobile-portal-dock-item', false)
        ->assertDontSee('href="'.route('portal.dashboard').'" class="mobile-portal-dock-item', false);
});
