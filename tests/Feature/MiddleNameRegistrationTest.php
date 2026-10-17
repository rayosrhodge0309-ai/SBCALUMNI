<?php

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function middleNameRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'student_id' => null,
        'first_name' => 'Ana Maria',
        'last_name' => 'Dela Cruz',
        'birthday' => '2000-05-20',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2022,
        'email' => 'ana.middle@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

test('account creation shows an optional middle name and retains the previous input', function () {
    $this->withSession(['_old_input' => ['middle_name' => 'Santos']])
        ->get(route('portal.register'))->assertOk()
        ->assertSee('Middle Name')
        ->assertSee('name="middle_name" value="Santos" maxlength="100" autocomplete="additional-name"', false);
});

test('middle names are retained through account registration review and alumni record creation', function () {
    $this->post(route('portal.register.store'), middleNameRegistrationPayload(['middle_name' => '  Santos  ']))
        ->assertSessionHasNoErrors()->assertRedirect(route('portal.login'));

    $user = User::query()->where('email', 'ana.middle@gmail.com')->firstOrFail();
    expect($user->name)->toBe('Ana Maria Santos Dela Cruz')
        ->and($user->registration_details['first_name'])->toBe('Ana Maria')
        ->and($user->registration_details['middle_name'])->toBe('Santos')
        ->and($user->registration_details['last_name'])->toBe('Dela Cruz')
        ->and($user->registration_profile->full_name)->toBe($user->name)
        ->and($user->account_status)->toBe('pending');
    $this->assertDatabaseCount('alumni', 0);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get(route('users.edit', $user))->assertOk()
        ->assertSeeInOrder(['First Name', 'Ana Maria', 'Middle Name', 'Santos', 'Last Name', 'Dela Cruz']);
    $this->get(route('users.pending'))->assertOk()->assertSee($user->name);
    $this->get(route('users.pending.notifications'))->assertOk()
        ->assertJsonPath('new.0.student_name', $user->name);

    $user->update(['account_status' => 'approved', 'approved_at' => now()]);
    $this->post(route('users.alumni-record', $user), ['record_choice' => 'new'])
        ->assertSessionHasNoErrors()->assertRedirect(route('users.edit', $user));
    $record = $user->fresh()->alumni;
    expect($record->first_name)->toBe('Ana Maria Santos')
        ->and($record->last_name)->toBe('Dela Cruz')
        ->and($record->full_name)->toBe($user->name)
        ->and($user->fresh()->registration_details['middle_name'])->toBe('Santos');
    $this->get(route('users.edit', $user))->assertOk();
});

test('middle names are optional without changing existing name formatting', function (array $overrides) {
    $this->post(route('portal.register.store'), middleNameRegistrationPayload($overrides))
        ->assertSessionHasNoErrors()->assertRedirect(route('portal.login'));

    $user = User::query()->where('email', 'ana.middle@gmail.com')->firstOrFail();
    expect($user->name)->toBe('Ana Maria Dela Cruz')
        ->and($user->registration_profile->full_name)->toBe('Ana Maria Dela Cruz');
})->with([
    'omitted' => [[]],
    'empty' => [['middle_name' => '']],
    'null' => [['middle_name' => null]],
    'whitespace' => [['middle_name' => '   ']],
]);

test('middle name validation rejects invalid values without creating an account', function (mixed $middleName) {
    $this->from(route('portal.register'))
        ->post(route('portal.register.store'), middleNameRegistrationPayload(['middle_name' => $middleName]))
        ->assertRedirect(route('portal.register'))->assertSessionHasErrors('middle_name');

    $this->assertDatabaseMissing('users', ['email' => 'ana.middle@gmail.com']);
})->with([
    'too long' => [str_repeat('A', 101)],
    'not text' => [['Santos']],
]);

test('registration validates the combined name length before saving', function () {
    $this->post(route('portal.register.store'), middleNameRegistrationPayload([
        'first_name' => str_repeat('A', 100),
        'middle_name' => str_repeat('B', 100),
        'last_name' => str_repeat('C', 100),
    ]))->assertSessionHasErrors('first_name');

    $this->assertDatabaseMissing('users', ['email' => 'ana.middle@gmail.com']);
});

test('middle names still match school records without a student ID and do not create duplicates', function (string $schoolFirstName) {
    $record = Alumni::create([
        'student_id' => 'SCHOOL-123',
        'first_name' => $schoolFirstName,
        'last_name' => 'Dela Cruz',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2022,
    ]);
    $original = $record->fresh()->getAttributes();
    $this->post(route('portal.register.store'), middleNameRegistrationPayload(['middle_name' => 'Santos']))
        ->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.middle@gmail.com')->firstOrFail();
    $user->update(['account_status' => 'approved']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('users.edit', $user))->assertOk()
        ->assertViewHas('matchingRecords', fn ($records) => $records->contains('id', $record->id));
    $this->post(route('users.alumni-record', $user), ['record_choice' => 'new'])
        ->assertSessionHasErrors('alumni_record_id');
    $this->assertDatabaseCount('alumni', 1);
    expect($record->fresh()->getAttributes())->toEqual($original);

    $this->post(route('users.alumni-record', $user), ['record_choice' => 'existing', 'alumni_record_id' => $record->id])
        ->assertSessionHasNoErrors();
    expect($user->fresh()->alumni_id)->toBe($record->id)
        ->and($user->fresh()->name)->toBe('Ana Maria Santos Dela Cruz')
        ->and($record->fresh()->first_name)->toBe($schoolFirstName);
})->with(['Ana Maria Santos', 'Ana Maria']);
