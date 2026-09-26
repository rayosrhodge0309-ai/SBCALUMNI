<?php

use App\Models\AcademicProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins can open school level and program management from the workspace', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.academic-programs.index'))
        ->assertOk()
        ->assertSee('School Levels &amp; Programs', false)
        ->assertSee('Senior High School')
        ->assertSee('BS Information Technology');
});

test('admin program changes are applied to alumni account registration', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.academic-programs.store'), [
            'education_level' => 'College',
            'name' => 'Bachelor of Science in Nursing',
        ])
        ->assertRedirect(route('admin.academic-programs.index'));

    $program = AcademicProgram::query()
        ->where('education_level', 'College')
        ->where('name', 'Bachelor of Science in Nursing')
        ->firstOrFail();

    $this->put(route('admin.academic-programs.update', $program), [
        'education_level' => 'College',
        'name' => 'BS Nursing',
    ])->assertRedirect(route('admin.academic-programs.index'));

    $this->post(route('logout'));

    $this->get(route('portal.register'))
        ->assertOk()
        ->assertSee('BS Nursing')
        ->assertDontSee('Bachelor of Science in Nursing');

    $this->post(route('portal.register.store'), [
        'student_id' => '2026-2001',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'birthday' => '2002-05-20',
        'education_level' => 'College',
        'course' => 'BS Nursing',
        'year_graduated' => 2026,
        'email' => 'maria.santos@gmail.com',
        'contact_number' => '09123456789',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ])->assertRedirect(route('portal.login'));

    $this->assertDatabaseHas('alumni', [
        'student_id' => '2026-2001',
        'course' => 'BS Nursing',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.academic-programs.destroy', $program->fresh()))
        ->assertRedirect(route('admin.academic-programs.index'));

    $this->post(route('logout'));

    $this->get(route('portal.register'))
        ->assertOk()
        ->assertDontSee('BS Nursing');
});

test('alumni cannot manage registration program choices', function () {
    $alumniUser = User::factory()->create(['role' => 'alumni']);

    $this->actingAs($alumniUser)
        ->get(route('admin.academic-programs.index'))
        ->assertForbidden();
});
