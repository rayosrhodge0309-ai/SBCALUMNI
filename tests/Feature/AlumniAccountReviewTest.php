<?php

use App\Models\Alumni;
use App\Models\Event;
use App\Models\User;
use App\Notifications\AlumniAccountApproved;
use App\Services\AlumniAccountReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function reviewRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'student_id' => '123456789',
        'first_name' => 'Ana Maria',
        'last_name' => 'Dela Cruz',
        'maiden_name' => 'Reyes',
        'birthday' => '2000-05-20',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2022,
        'contact_number' => '09123456789',
        'address' => 'Batangas City',
        'email' => 'ana.maria@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

function reviewSchoolRecord(array $overrides = []): Alumni
{
    return Alumni::create(array_merge(
        collect(reviewRegistrationPayload())->except(['password', 'password_confirmation'])->all(),
        ['student_id' => '12-3456-789', 'email' => null],
        $overrides,
    ));
}

test('registration matching a school record stays pending without changing school details', function () {
    $record = reviewSchoolRecord();
    $original = $record->getAttributes();

    $this->post(route('portal.register.store'), reviewRegistrationPayload())
        ->assertSessionHasNoErrors()->assertRedirect(route('portal.login'));

    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    expect($user->isPendingApproval())->toBeTrue()
        ->and($user->alumni_id)->toBeNull()
        ->and($user->registration_details['first_name'])->toBe('Ana Maria')
        ->and($user->registration_details['birthday'])->toBe('2000-05-20')
        ->and($user->registration_details)->not->toHaveKey('password')
        ->and($record->fresh()->getAttributes())->toEqual($original);
    $this->assertDatabaseCount('alumni', 1);

    $this->post(route('portal.login.attempt'), ['email' => $user->email, 'password' => 'password123'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('admin review shows submitted details and matching school records without Gmail', function () {
    $record = reviewSchoolRecord();
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('users.edit', $user))->assertOk()
        ->assertViewHas('recordSearch', '')
        ->assertViewHas('recordMatchError', null)
        ->assertViewHas('matchingRecords', fn ($records) => $records->contains('id', $record->id))
        ->assertSee('Review Submitted Details')->assertSee('School Record')
        ->assertSee('Ana Maria')->assertSee('Dela Cruz')->assertSee('Reyes')
        ->assertSee('May 20, 2000')->assertSee('09123456789')->assertSee('Batangas City')
        ->assertSee('12-3456-789')->assertSee('Not recorded');
    $this->get(route('users.pending'))->assertOk()->assertSee('Submitted Details')->assertSee('Reyes');
    $this->get(route('users.pending.notifications'))->assertOk()
        ->assertJsonPath('new.0.student_name', 'Ana Maria Dela Cruz')
        ->assertJsonPath('new.0.maiden_name', 'Reyes');
    expect($record->fresh()->email)->toBeNull();
});

test('review partial preserves school matches when the search variable is not supplied', function () {
    $record = reviewSchoolRecord();
    $user = User::factory()->create([
        'role' => 'alumni',
        'account_status' => 'approved',
        'registration_details' => collect(reviewRegistrationPayload())
            ->except(['email', 'password', 'password_confirmation'])->all(),
    ]);
    $this->app['request']->query->set('record_search', '  Ana Maria  ');

    $this->view('users._alumni-review', [
        'managedUser' => $user,
        'matchingRecords' => collect([$record]),
        'recordMatchError' => null,
    ])->assertSee('value="Ana Maria"', false)
        ->assertSee('12-3456-789')
        ->assertSee('action="'.route('users.alumni-record', $user).'"', false)
        ->assertDontSee('School-record matching is temporarily unavailable.');
});

test('review partial does not offer record creation when matching data is missing', function () {
    $user = User::factory()->create([
        'role' => 'alumni',
        'account_status' => 'approved',
    ]);

    $this->view('users._alumni-review', ['managedUser' => $user])
        ->assertSee('Review Submitted Details')
        ->assertSee('School-record matching is temporarily unavailable.')
        ->assertDontSee('No matching school record found.')
        ->assertDontSee('action="'.route('users.alumni-record', $user).'"', false);
});

test('admin review remains available when school record matching fails', function () {
    $user = User::factory()->create([
        'name' => 'Camela Dolor Aguila',
        'email' => 'camela.aguila@gmail.com',
        'role' => 'alumni',
        'account_status' => 'pending',
        'registration_details' => [
            'student_id' => '22-3435-35353',
            'first_name' => 'Camela Dolor',
            'last_name' => 'Aguila',
            'education_level' => 'College',
            'course' => 'Bachelor of Science in Social Work',
            'year_graduated' => 2018,
        ],
    ]);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->mock(AlumniAccountReviewService::class)
        ->shouldReceive('matchingRecords')
        ->once()
        ->andThrow(new RuntimeException('School record lookup failed.'));

    $this->actingAs($admin)
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertSee('Review Submitted Details')
        ->assertSee('Camela Dolor Aguila')
        ->assertSee('School-record matching is temporarily unavailable.');
});

test('approval permits login and dashboard access without automatically creating or linking alumni records', function () {
    Notification::fake();
    $record = reviewSchoolRecord();
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->patch(route('users.approve', $user))->assertRedirect();
    Notification::assertSentTo($user, AlumniAccountApproved::class);
    expect($user->fresh()->isApproved())->toBeTrue()
        ->and($user->fresh()->alumni_id)->toBeNull()
        ->and($record->fresh()->email)->toBeNull();
    $this->get(route('users.index'))->assertOk()->assertSee('Add to Alumni Records');
    $this->post(route('logout'));

    $this->post(route('portal.login.attempt'), ['email' => 'ANA.MARIA@GMAIL.COM', 'password' => 'password123'])
        ->assertRedirect(route('portal.otp.create'));
    $this->withSession([
        'portal_otp_code_hash' => Hash::make('123456'),
        'portal_otp_code_expires_at' => now()->addMinutes(10)->timestamp,
        'portal_otp_attempts' => 0,
    ])->post(route('portal.otp.store'), ['otp' => '123456'])->assertRedirect(route('portal.dashboard'));
    $this->get(route('portal.dashboard'))->assertOk()->assertSee('My Account Details');
    $this->get(route('portal.requests.index'))->assertOk()->assertDontSee('name="request_type"', false);
    $this->get(route('portal.requests.notifications'))->assertJson(['new' => []]);
    $this->post(route('portal.requests.store'), ['request_type' => 'Alumni ID', 'year_requested' => 2022])
        ->assertRedirect()->assertSessionHas('warning');
    $this->assertDatabaseCount('requests', 0);
    $this->assertDatabaseCount('alumni', 1);
    expect($user->fresh()->alumni_id)->toBeNull();
});

test('adding an approved account links the selected school record and keeps the login account', function () {
    $record = reviewSchoolRecord(['address' => 'School archive address']);
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $user->forceFill(['account_status' => 'approved', 'approved_at' => now(), 'portal_otp_verified_at' => now()])->save();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('users.alumni-record', $user), ['record_choice' => 'existing', 'alumni_record_id' => $record->id])
        ->assertSessionHasNoErrors()->assertRedirect(route('users.edit', $user));
    $this->assertDatabaseCount('alumni', 1);
    expect($user->fresh()->alumni_id)->toBe($record->id)
        ->and($record->fresh()->email)->toBe('ana.maria@gmail.com')
        ->and($record->fresh()->first_name)->toBe('Ana Maria')
        ->and($record->fresh()->last_name)->toBe('Dela Cruz')
        ->and($record->fresh()->address)->toBe('School archive address');
    $this->get(route('users.index'))->assertOk()->assertSee('ana.maria@gmail.com');
    $this->get(route('users.edit', $user))->assertOk()->assertSee('remains in User Accounts');

    $this->post(route('users.alumni-record', $user), ['record_choice' => 'existing', 'alumni_record_id' => $record->id])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseCount('alumni', 1);
    $this->actingAs($user->fresh())->get(route('portal.dashboard'))->assertOk()->assertSee('My Alumni Record');
});

test('add creates a missing alumni record only when explicitly requested after approval', function () {
    $this->post(route('portal.register.store'), reviewRegistrationPayload(['student_id' => null]))->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $this->assertDatabaseCount('alumni', 0);
    $user->forceFill(['account_status' => 'approved', 'approved_at' => now()])->save();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('users.alumni-record', $user), ['record_choice' => 'new'])
        ->assertSessionHasNoErrors()->assertRedirect(route('users.edit', $user));
    $record = $user->fresh()->alumni;
    expect($record->student_id)->toStartWith('TEMP-')
        ->and($record->first_name)->toBe('Ana Maria')
        ->and($record->last_name)->toBe('Dela Cruz')
        ->and($record->maiden_name)->toBe('Reyes')
        ->and($record->birthday->toDateString())->toBe('2000-05-20')
        ->and($record->address)->toBe('Batangas City');
    $this->assertDatabaseCount('alumni', 1);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'ana.maria@gmail.com', 'account_status' => 'approved']);
});

test('pending and rejected accounts cannot be added to alumni records', function (string $status) {
    $record = reviewSchoolRecord();
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $user->update(['account_status' => $status]);
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->post(route('users.alumni-record', $user), ['record_choice' => 'existing', 'alumni_record_id' => $record->id])
        ->assertSessionHasErrors('alumni_record_id');
    expect($user->fresh()->alumni_id)->toBeNull()->and($record->fresh()->email)->toBeNull();
})->with(['pending', 'rejected']);

test('forgotten student IDs and maiden names can match school records without duplicating them', function () {
    $record = reviewSchoolRecord(['last_name' => 'Reyes', 'maiden_name' => null]);
    $this->post(route('portal.register.store'), reviewRegistrationPayload(['student_id' => null]))->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $user->update(['account_status' => 'approved']);
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get(route('users.edit', $user))->assertOk()->assertSee('Ana Maria Reyes');
    $this->post(route('users.alumni-record', $user), ['record_choice' => 'new'])->assertSessionHasErrors('alumni_record_id');
    $this->assertDatabaseCount('alumni', 1);
    expect($user->fresh()->alumni_id)->toBeNull();
});

test('an alumni record already linked to another user cannot be claimed by an approved account', function () {
    $record = reviewSchoolRecord();
    $owner = User::factory()->create(['role' => 'alumni', 'email' => 'owner@gmail.com', 'alumni_id' => $record->id]);
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $user->update(['account_status' => 'approved']);
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->post(route('users.alumni-record', $user), ['record_choice' => 'existing', 'alumni_record_id' => $record->id])
        ->assertSessionHasErrors('alumni_record_id');
    expect($owner->fresh()->alumni_id)->toBe($record->id)->and($user->fresh()->alumni_id)->toBeNull();
});

test('registration cannot overwrite the password or details of an existing pending account', function () {
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $this->post(route('portal.register.store'), reviewRegistrationPayload([
        'email' => 'ANA.MARIA@GMAIL.COM', 'first_name' => 'Changed',
        'password' => 'changed123', 'password_confirmation' => 'changed123',
    ]))->assertSessionHasErrors('email');
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    expect(Hash::check('password123', $user->password))->toBeTrue()
        ->and($user->registration_details['first_name'])->toBe('Ana Maria');
    $this->assertDatabaseCount('users', 1);
});

test('school imports do not automatically create or approve login accounts', function () {
    $record = reviewSchoolRecord(['email' => 'ana.maria@gmail.com']);
    $user = User::factory()->create(['role' => 'alumni', 'account_status' => 'pending', 'email' => $record->email, 'alumni_id' => $record->id]);
    $admin = User::factory()->create(['role' => 'admin']);
    $file = UploadedFile::fake()->createWithContent('school.csv', "student_id,first_name,last_name,course,year_graduated,email\n12-3456-789,Ana Maria,Dela Cruz,BS Information Technology,2022,ana.maria@gmail.com\n98765,Lara,Ramos,BS Information Technology,2022,lara@gmail.com\n");
    $this->actingAs($admin)->post(route('alumni.import'), ['file' => $file])->assertSessionHasNoErrors()->assertRedirect(route('alumni.index'));
    expect($user->fresh()->isPendingApproval())->toBeTrue();
    $this->assertDatabaseMissing('users', ['email' => 'lara@gmail.com']);
});

test('alumni users cannot approve accounts or add accounts to alumni records', function () {
    $user = User::factory()->create(['role' => 'alumni', 'email' => 'alumnus@gmail.com']);
    $this->actingAs($user)->post(route('users.alumni-record', $user), ['record_choice' => 'new'])->assertForbidden();
    $this->patch(route('users.approve', $user))->assertForbidden();
    $this->get(route('users.edit', $user))->assertForbidden();
    $this->assertDatabaseCount('alumni', 0);
});

test('approved unlinked accounts cannot create alumni records by registering for events', function () {
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $user->update(['account_status' => 'approved', 'portal_otp_verified_at' => now()]);
    $event = Event::create([
        'title' => 'Alumni Homecoming', 'description' => 'School reunion',
        'event_date' => now()->addWeek(), 'location' => 'St. Bridget College', 'is_published' => true,
    ]);

    $this->actingAs($user)->post(route('portal.events.registrations.store', $event), [
        'event_registration_event_id' => $event->id,
        'attendee_name' => $user->name, 'attendee_email' => $user->email,
        'attendee_student_id' => '123456789', 'attendee_course' => 'BS Information Technology',
        'attendee_year_graduated' => 2022, 'attendee_contact_number' => '09123456789',
    ])->assertRedirect()->assertSessionHas('warning');
    $this->assertDatabaseCount('alumni', 0);
    $this->assertDatabaseCount('event_registrations', 0);
});

test('adding an existing record rejects Gmail conflicts without changing either record', function () {
    $selected = reviewSchoolRecord();
    $other = reviewSchoolRecord(['student_id' => 'OTHER-001', 'first_name' => 'Another', 'email' => 'ana.maria@gmail.com']);
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $user->update(['account_status' => 'approved']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('users.alumni-record', $user), ['record_choice' => 'existing', 'alumni_record_id' => $selected->id])
        ->assertSessionHasErrors('email');
    expect($user->fresh()->alumni_id)->toBeNull()
        ->and($selected->fresh()->email)->toBeNull()
        ->and($other->fresh()->email)->toBe('ana.maria@gmail.com');
});

test('admin review can find school records by full name or unformatted student ID', function (string $search) {
    $record = reviewSchoolRecord(['student_id' => '98-7654-321', 'first_name' => 'Maria', 'last_name' => 'Santos']);
    $this->post(route('portal.register.store'), reviewRegistrationPayload())->assertSessionHasNoErrors();
    $user = User::query()->where('email', 'ana.maria@gmail.com')->firstOrFail();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('users.edit', ['user' => $user, 'record_search' => $search]))
        ->assertOk()->assertSee('Maria Santos')->assertSee($record->student_id);
})->with(['Maria Santos', '987654321']);
