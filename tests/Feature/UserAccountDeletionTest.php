<?php

use App\Models\Alumni;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function alumniRecordForAccountDeletion(): Alumni
{
    return Alumni::create([
        'student_id' => '2020-1234',
        'first_name' => 'Account',
        'last_name' => 'Holder',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2024,
        'email' => 'account.holder@gmail.com',
    ]);
}

test('admins can delete linked and unlinked alumni accounts without deleting school records', function (string $status, bool $linked) {
    $admin = User::factory()->create(['role' => 'admin']);
    $alumnus = alumniRecordForAccountDeletion();
    $recordAttributes = $alumnus->fresh()->getAttributes();
    $target = User::factory()->create([
        'role' => 'alumni',
        'account_status' => $status,
        'email' => $alumnus->email,
        'alumni_id' => $linked ? $alumnus->id : null,
    ]);
    $untouchedUser = User::factory()->create(['role' => 'alumni']);
    $untouchedAttributes = $untouchedUser->fresh()->getAttributes();

    $this->actingAs($admin)->delete(route('users.destroy', $target))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', 'User account deleted successfully. Any linked alumni record has been kept.');

    $this->assertModelMissing($target);
    $this->assertModelExists($admin);
    expect($alumnus->fresh()->getAttributes())->toBe($recordAttributes);
    expect($untouchedUser->fresh()->getAttributes())->toBe($untouchedAttributes);
    expect($alumnus->fresh()->user)->toBeNull();
    $this->get(route('users.index'))->assertOk()->assertDontSee($target->email);
    $this->assertModelMissing($target);
})->with([
    'approved linked' => ['approved', true],
    'approved unlinked' => ['approved', false],
    'pending linked' => ['pending', true],
    'pending unlinked' => ['pending', false],
    'rejected linked' => ['rejected', true],
    'rejected unlinked' => ['rejected', false],
]);

test('deleting a user preserves linked requests event registrations and their history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $alumnus = alumniRecordForAccountDeletion();
    $target = User::factory()->create(['role' => 'alumni', 'alumni_id' => $alumnus->id]);
    $recordRequest = RecordRequest::create([
        'alumni_id' => $alumnus->id,
        'request_type' => 'Alumni ID',
        'year_requested' => 2026,
        'status' => 'pending',
    ]);
    $requestAttributes = $recordRequest->fresh()->getAttributes();
    $historyId = $recordRequest->statusHistories()->firstOrFail()->id;
    $event = Event::create([
        'title' => 'Alumni Reunion',
        'description' => 'Annual gathering',
        'event_date' => '2026-12-01',
        'location' => 'Campus',
    ]);
    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'alumni_id' => $alumnus->id,
        'user_id' => $target->id,
        'status' => 'registered',
        'attendee_name' => $alumnus->full_name,
        'attendee_email' => $alumnus->email,
    ]);

    $this->actingAs($admin)->delete(route('users.destroy', $target))->assertRedirect(route('users.index'));

    $this->assertModelExists($alumnus);
    $this->assertModelExists($event);
    expect($recordRequest->fresh()->getAttributes())->toBe($requestAttributes);
    $this->assertDatabaseHas('request_status_histories', ['id' => $historyId, 'request_id' => $recordRequest->id]);
    $this->assertDatabaseHas('event_registrations', [
        'id' => $registration->id,
        'alumni_id' => $alumnus->id,
        'user_id' => null,
        'status' => 'registered',
        'attendee_name' => $alumnus->full_name,
        'attendee_email' => $alumnus->email,
    ]);
});

test('deleting another administrator preserves records with references to that administrator', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'admin']);
    $alumnus = alumniRecordForAccountDeletion();
    $recordRequest = RecordRequest::create([
        'alumni_id' => $alumnus->id,
        'request_type' => 'Alumni ID',
        'year_requested' => 2026,
        'status' => 'completed',
        'processed_by' => $target->id,
        'admin_notes' => 'Already claimed',
    ]);
    $history = $recordRequest->statusHistories()->create([
        'status' => 'completed',
        'changed_by' => $target->id,
    ]);
    $event = Event::create([
        'title' => 'Alumni Reunion',
        'description' => 'Annual gathering',
        'event_date' => '2026-12-01',
        'location' => 'Campus',
    ]);
    $registration = EventRegistration::create([
        'event_id' => $event->id,
        'alumni_id' => $alumnus->id,
        'replied_by' => $target->id,
        'admin_reply' => 'Welcome',
    ]);

    $this->actingAs($admin)->delete(route('users.destroy', $target))->assertRedirect(route('users.index'));

    $this->assertModelMissing($target);
    $this->assertDatabaseHas('requests', ['id' => $recordRequest->id, 'processed_by' => null, 'admin_notes' => 'Already claimed']);
    $this->assertDatabaseHas('request_status_histories', ['id' => $history->id, 'changed_by' => null]);
    $this->assertDatabaseHas('event_registrations', ['id' => $registration->id, 'replied_by' => null, 'admin_reply' => 'Welcome']);
    $this->assertAuthenticatedAs($admin);
});

test('deleting an account removes only its own profile photo and password reset token', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin', 'profile_photo_path' => 'profile-photos/admin.png']);
    $target = User::factory()->create(['role' => 'alumni', 'profile_photo_path' => 'profile-photos/deleted.png']);
    Storage::disk('public')->put($admin->profile_photo_path, 'admin photo');
    Storage::disk('public')->put($target->profile_photo_path, 'target photo');
    $adminToken = Password::broker()->createToken($admin);
    $targetToken = Password::broker()->createToken($target);

    $this->actingAs($admin)->delete(route('users.destroy', $target))->assertRedirect(route('users.index'));

    Storage::disk('public')->assertMissing($target->profile_photo_path);
    Storage::disk('public')->assertExists($admin->profile_photo_path);
    expect(Password::broker()->tokenExists($target, $targetToken))->toBeFalse();
    expect(Password::broker()->tokenExists($admin, $adminToken))->toBeTrue();
});

test('photo cleanup failures do not turn completed account deletion into a server error', function (bool $throws) {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'alumni', 'profile_photo_path' => 'profile-photos/deleted.png']);
    $disk = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
    $deletion = $disk->shouldReceive('delete')->once()->with($target->profile_photo_path);
    if ($throws) {
        $deletion->andThrow(new RuntimeException('Storage unavailable'));
    } else {
        $deletion->andReturn(false);
    }
    Storage::shouldReceive('disk')->once()->with('public')->andReturn($disk);

    $this->actingAs($admin)->delete(route('users.destroy', $target))
        ->assertRedirect(route('users.index'))->assertSessionHas('success')->assertSessionHas('warning');

    $this->assertModelMissing($target);
    $this->assertModelExists($admin);
})->with(['returns false' => [false], 'throws' => [true]]);

test('administrators cannot delete their own signed in account', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'admin', 'profile_photo_path' => 'profile-photos/admin.png']);
    Storage::disk('public')->put($admin->profile_photo_path, 'admin photo');
    $resetToken = Password::broker()->createToken($admin);

    $this->actingAs($admin)->delete(route('users.destroy', $admin))
        ->assertRedirect(route('users.index'))->assertSessionHas('warning');

    $this->assertModelExists($admin);
    $this->assertAuthenticatedAs($admin);
    Storage::disk('public')->assertExists($admin->profile_photo_path);
    expect(Password::broker()->tokenExists($admin, $resetToken))->toBeTrue();
});

test('guests and alumni cannot delete user accounts', function () {
    $target = User::factory()->create(['role' => 'alumni']);
    $alumniUser = User::factory()->create(['role' => 'alumni', 'account_status' => 'approved']);

    $this->delete(route('users.destroy', $target))->assertRedirect(route('portal.login'));
    $this->assertModelExists($target);

    $this->actingAs($alumniUser)->delete(route('users.destroy', $target))->assertForbidden();
    $this->assertModelExists($target);
});

test('deleted account credentials cannot be used to log in', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create([
        'role' => 'alumni',
        'account_status' => 'approved',
        'email' => 'deleted.account@gmail.com',
        'password' => 'password123',
    ]);
    $this->actingAs($admin)->delete(route('users.destroy', $target))->assertRedirect(route('users.index'));
    Auth::logout();

    $this->from(route('portal.login'))->post(route('portal.login.attempt'), [
        'email' => $target->email,
        'password' => 'password123',
    ])->assertRedirect(route('portal.login'))->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertModelMissing($target);
});

test('delete buttons appear only on the user accounts list and exclude the current administrator', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'alumni', 'account_status' => 'pending']);
    $this->actingAs($admin)->get(route('users.index'))->assertOk()
        ->assertSee('action="'.route('users.destroy', $target).'"', false)
        ->assertDontSee('action="'.route('users.destroy', $admin).'"', false)
        ->assertSee('name="_method" value="DELETE"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('onsubmit=', false);

    $this->get(route('users.pending'))->assertOk()->assertDontSee('name="_method" value="DELETE"', false);
    $this->get(route('users.edit', $target))->assertOk()->assertDontSee('name="_method" value="DELETE"', false);
});

test('account deletion enforces csrf tokens and accepts the confirmation forms delete method', function () {
    $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
    {
        protected function runningUnitTests()
        {
            return false;
        }
    });
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'alumni']);

    $this->actingAs($admin)->withSession(['_token' => 'valid-account-delete-token'])
        ->post(route('users.destroy', $target), ['_method' => 'DELETE', '_token' => 'expired-token'])
        ->assertStatus(419);
    $this->assertModelExists($target);

    $this->post(route('users.destroy', $target), ['_method' => 'DELETE', '_token' => 'valid-account-delete-token'])
        ->assertRedirect(route('users.index'));
    $this->assertModelMissing($target);
});
