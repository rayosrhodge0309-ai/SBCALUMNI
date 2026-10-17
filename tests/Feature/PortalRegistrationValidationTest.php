<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ViewErrorBag;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
});

function validatedPortalRegistrationPayload(array $overrides = []): array
{
    return array_replace([
        'student_id' => '12-3456-789',
        'first_name' => 'Ana Maria',
        'middle_name' => 'Santos',
        'last_name' => 'Dela Cruz',
        'maiden_name' => 'Reyes',
        'birthday' => '2000-05-20',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => '2022',
        'email' => 'validation.alumna@gmail.com',
        'contact_number' => '09123456789',
        'address' => 'Batangas City',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

test('registration renders when production omits date limits from the view data', function (array $limits, string $today, int $maximumYear) {
    config(['app.debug' => false]);

    $this->view('portal.auth.register', array_merge([
        'programsByLevel' => ['College' => ['BS Information Technology']],
        'errors' => new ViewErrorBag,
    ], $limits))
        ->assertSee('data-today="'.$today.'" data-maximum-graduation-year="'.$maximumYear.'"', false)
        ->assertSee('id="birthday" type="text"', false)
        ->assertSee('data-maximum-date="'.min($today, '2026-12-31').'"', false)
        ->assertSee('Choose your birthday from the calendar.')
        ->assertSee('max="'.$maximumYear.'"', false)
        ->assertSee('min="1920"', false)
        ->assertSee('Enter a year from 1920 to '.$maximumYear.'.')
        ->assertSee('Submit Account Request');
})->with([
    'both omitted' => [[], '2026-10-15', 2027],
    'today omitted' => [['maximumGraduationYear' => 2030], '2026-10-15', 2030],
    'maximum year omitted' => [['registrationToday' => '2024-02-29'], '2024-02-29', 2027],
    'provided values preserved' => [['registrationToday' => '2024-02-29', 'maximumGraduationYear' => 2025], '2024-02-29', 2025],
]);

test('registration normalizes personal details and stores only reviewed account data', function () {
    $password = '  secure password123  ';
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'student_id' => '  SBC 12-3456-789  ',
        'first_name' => "\u{00A0}Ana\u{2003}  María\u{00A0}",
        'middle_name' => '  De  los Santos  ',
        'last_name' => '  O’Neill-Sy  ',
        'maiden_name' => '  Dela  Cruz  ',
        'email' => '  Validation.Alumna@GMAIL.COM  ',
        'contact_number' => ' 09123456789 ',
        'address' => "  Barangay Uno\r\nBatangas City  ",
        'password' => $password,
        'password_confirmation' => $password,
        'role' => 'admin',
        'account_status' => 'approved',
        'approved_at' => '2026-10-01',
        'portal_otp_verified_at' => '2026-10-01',
        'alumni_id' => 999,
        'registration_details' => ['role' => 'admin'],
    ]))->assertSessionHasNoErrors()->assertRedirect(route('portal.login'));

    $user = User::query()->sole();
    expect($user->name)->toBe('Ana María De los Santos O’Neill-Sy')
        ->and($user->email)->toBe('validation.alumna@gmail.com')
        ->and($user->role)->toBe('alumni')
        ->and($user->account_status)->toBe('pending')
        ->and($user->approved_at)->toBeNull()
        ->and($user->portal_otp_verified_at)->toBeNull()
        ->and($user->alumni_id)->toBeNull()
        ->and(Hash::check($password, $user->password))->toBeTrue()
        ->and(Hash::check(trim($password), $user->password))->toBeFalse()
        ->and($user->registration_details)->toEqual([
            'student_id' => 'SBC 12-3456-789',
            'first_name' => 'Ana María',
            'middle_name' => 'De los Santos',
            'last_name' => 'O’Neill-Sy',
            'maiden_name' => 'Dela Cruz',
            'birthday' => '2000-05-20',
            'education_level' => 'College',
            'course' => 'BS Information Technology',
            'year_graduated' => 2022,
            'contact_number' => '09123456789',
            'address' => "Barangay Uno\nBatangas City",
        ])
        ->and($user->registration_details['year_graduated'])->toBeInt();

    $this->assertDatabaseCount('alumni', 0);
    $this->assertGuest();
});

test('registration accepts real names with Unicode letters and supported punctuation', function (string $name) {
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'first_name' => $name,
    ]))->assertSessionHasNoErrors()->assertRedirect(route('portal.login'));

    expect(User::query()->sole()->registration_details['first_name'])->toBe($name);
})->with([
    'accented letters' => ['José'],
    'combining mark' => ["Jose\u{0301}"],
    'apostrophe and hyphen' => ["D'Arcy-Jane"],
    'initials and periods' => ['Ma. Teresa'],
    'non Latin letters' => ['李美玲'],
]);

test('registration accepts optional blank fields as null', function (mixed $blank) {
    $optional = array_fill_keys([
        'student_id', 'middle_name', 'maiden_name', 'contact_number', 'address',
    ], $blank);

    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload($optional))
        ->assertSessionHasNoErrors()->assertRedirect(route('portal.login'));

    $user = User::query()->sole();
    foreach (array_keys($optional) as $field) {
        expect($user->registration_details[$field] ?? null)->toBeNull();
    }
    expect($user->name)->toBe('Ana Maria Dela Cruz');
})->with([
    'empty strings' => [''],
    'null' => [null],
    'spaces' => ['   '],
]);

test('registration accepts exactly eleven digits for a local mobile number', function (string $phone) {
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'contact_number' => $phone,
    ]))->assertSessionHasNoErrors();

    expect(User::query()->sole()->registration_details['contact_number'])->toBe($phone);
})->with(['09123456789', '09999999999']);

test('registration accepts graduation year 1920', function () {
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'birthday' => '1900-05-20',
        'year_graduated' => '1920',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('portal.login'));

    expect(User::query()->sole()->registration_details['year_graduated'])->toBe(1920);
});

test('registration stores a complete birthday and calculates age without storing submitted age', function (string $birthday, int $age) {
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'birthday' => $birthday,
        'year_graduated' => '2026',
        'age' => 999,
    ]))->assertSessionHasNoErrors()->assertRedirect(route('portal.login'));

    $user = User::query()->sole();
    expect($user->registration_details['birthday'])->toBe($birthday)
        ->and($user->registration_details)->not->toHaveKey('age')
        ->and($user->registration_profile->age)->toBe($age);
})->with([
    'older alumnus' => ['1950-05-20', 76],
    'previous year' => ['2025-12-31', 0],
    'current year before today' => ['2026-01-01', 0],
    'today' => ['2026-10-15', 0],
]);

test('registration keeps the 2026 birth year cap after the current year changes', function () {
    $this->travelTo(Carbon::parse('2028-10-15 12:00:00'));
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'birthday' => '2027-01-01',
        'year_graduated' => '2028',
    ]))->assertSessionHasErrors('birthday');
    $this->assertDatabaseCount('users', 0);
});

test('malformed registration input returns field errors and can render its redirect safely', function (string $field, mixed $value) {
    $this->from(route('portal.register'))
        ->post(route('portal.register.store'), validatedPortalRegistrationPayload([$field => $value]))
        ->assertRedirect(route('portal.register'))
        ->assertSessionHasErrors($field)
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    $this->get(route('portal.register'))->assertOk();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('alumni', 0);
})->with(function () {
    foreach (array_keys(validatedPortalRegistrationPayload()) as $field) {
        yield "$field list" => [$field, ['unexpected']];
        yield "$field object shape" => [$field, ['nested' => 'unexpected']];
    }
    yield 'email boolean' => ['email', true];
    yield 'student ID integer' => ['student_id', 123456789];
    yield 'first name integer' => ['first_name', 123];
    yield 'birthday integer' => ['birthday', 20000520];
    yield 'password integer' => ['password', 123456789];
});

test('JSON objects cannot bypass registration field validation', function () {
    $this->postJson(route('portal.register.store'), validatedPortalRegistrationPayload([
        'email' => (object) ['address' => 'validation.alumna@gmail.com'],
        'student_id' => (object) ['id' => '12-3456-789'],
        'birthday' => (object) ['date' => '2000-05-20'],
        'password' => (object) ['value' => 'password123'],
    ]))->assertUnprocessable()->assertJsonValidationErrors(['email', 'student_id', 'birthday', 'password']);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('alumni', 0);
});

test('invalid registration values cannot create an account or alumni record', function (string $field, mixed $value) {
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([$field => $value]))
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('alumni', 0);
})->with([
    'first name required' => ['first_name', '   '],
    'last name required' => ['last_name', ''],
    'first name numbers' => ['first_name', 'Ana123'],
    'middle name script' => ['middle_name', '<script>alert(1)</script>'],
    'last name punctuation only' => ['last_name', "'. -"],
    'maiden name invalid symbols' => ['maiden_name', 'Cruz@'],
    'first name too long' => ['first_name', str_repeat('A', 101)],
    'student ID punctuation only' => ['student_id', '-- --'],
    'student ID markup' => ['student_id', '<b>123</b>'],
    'student ID too long' => ['student_id', str_repeat('1', 51)],
    'invalid calendar day' => ['birthday', '2001-02-29'],
    'birthday required' => ['birthday', ''],
    'birthday beyond 2026' => ['birthday', '2027-01-01'],
    'relative date' => ['birthday', 'yesterday'],
    'wrong date order' => ['birthday', '20/05/2000'],
    'un-padded date' => ['birthday', '2000-5-2'],
    'future birthday' => ['birthday', '2026-10-16'],
    'unknown school level' => ['education_level', 'Unknown'],
    'unknown program' => ['course', 'Invented Degree'],
    'program belongs to different level' => ['course', 'Grade 12 - STEM'],
    'graduation year required' => ['year_graduated', ''],
    'graduation year too early' => ['year_graduated', '1919'],
    'graduation year too late' => ['year_graduated', '2028'],
    'graduation year before birthday' => ['year_graduated', '1999'],
    'graduation year decimal' => ['year_graduated', '2022.5'],
    'graduation year exponent' => ['year_graduated', '2e3'],
    'non Gmail domain' => ['email', 'alumna@example.com'],
    'Gmail suffix spoof' => ['email', 'alumna@gmail.com.example.com'],
    'malformed email' => ['email', 'alumna@@gmail.com'],
    'email required' => ['email', ''],
    'mobile too short' => ['contact_number', '0912345678'],
    'mobile too long' => ['contact_number', '091234567890'],
    'mobile with country code' => ['contact_number', '639123456789'],
    'mobile with plus country code' => ['contact_number', '+639123456789'],
    'mobile with formatting' => ['contact_number', '0912-345-6789'],
    'mobile with spaces' => ['contact_number', '(0912) 345 6789'],
    'mobile wrong prefix' => ['contact_number', '08123456789'],
    'mobile contains letters' => ['contact_number', '0912abc6789'],
    'mobile unsupported country' => ['contact_number', '+1 9123456789'],
    'address too long' => ['address', str_repeat('A', 1001)],
]);

test('registration checks password contents and bcrypt byte limits before hashing', function (string $password) {
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'password' => $password,
        'password_confirmation' => $password,
    ]))->assertSessionHasErrors('password')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    $this->assertDatabaseCount('users', 0);
})->with([
    'too short' => ['abc1234'],
    'missing numbers' => ['passwordonly'],
    'missing letters' => ['123456789'],
    'null byte' => ["password12\0extra"],
    'over 72 ASCII bytes' => [str_repeat('a', 71).'12'],
    'over 72 UTF8 bytes' => [str_repeat('é', 36).'1'],
]);

test('registration accepts a password at the bcrypt byte limit', function () {
    $password = str_repeat('é', 35).'A1';
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'password' => $password,
        'password_confirmation' => $password,
    ]))->assertSessionHasNoErrors();

    expect(Hash::check($password, User::query()->sole()->password))->toBeTrue();
});

test('registration checks the complete display name before persistence', function () {
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'first_name' => str_repeat('A', 100),
        'middle_name' => str_repeat('B', 100),
        'last_name' => str_repeat('C', 100),
    ]))->assertSessionHasErrors('first_name');

    $this->assertDatabaseCount('users', 0);
});

test('duplicate normalized Gmail submissions do not overwrite the existing request', function () {
    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload())
        ->assertSessionHasNoErrors();
    $original = User::query()->sole()->getAttributes();

    $this->post(route('portal.register.store'), validatedPortalRegistrationPayload([
        'email' => '  VALIDATION.ALUMNA@GMAIL.COM  ',
        'first_name' => 'Another',
        'password' => 'different123',
        'password_confirmation' => 'different123',
    ]))->assertSessionHasErrors('email');

    expect(User::query()->sole()->getAttributes())->toEqual($original);
    $this->assertDatabaseCount('alumni', 0);
});

test('registration errors are linked to fields and retain escaped input without passwords', function () {
    $unsafeName = '<img src=x onerror=alert(1)>';
    $this->from(route('portal.register'))
        ->post(route('portal.register.store'), validatedPortalRegistrationPayload([
            'first_name' => $unsafeName,
            'password_confirmation' => 'different123',
        ]))->assertRedirect(route('portal.register'))
        ->assertSessionHasErrors(['first_name', 'password'])
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    $page = $this->get(route('portal.register'))->assertOk()
        ->assertSee($unsafeName)
        ->assertDontSee($unsafeName, false)
        ->assertDontSee('value="password123"', false)
        ->assertDontSee('value="different123"', false);

    $document = new DOMDocument;
    @$document->loadHTML($page->getContent());
    $field = $document->getElementById('first_name');
    expect($field)->not->toBeNull()
        ->and($field->getAttribute('aria-invalid'))->toBe('true');

    $descriptionIds = preg_split('/\s+/', trim($field->getAttribute('aria-describedby')));
    $linkedError = false;
    foreach ($descriptionIds as $id) {
        $description = $document->getElementById($id);
        if ($description && str_contains($description->getAttribute('class'), 'invalid-feedback')) {
            $linkedError = trim($description->textContent) !== '';
        }
    }
    expect($linkedError)->toBeTrue();
});
