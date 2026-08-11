<?php

use App\Models\Activity;
use App\Models\Alumni;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\SiteSetting;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('guests are redirected to the correct admin or alumni login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('portal.login'));
    $this->get(route('alumni.index'))->assertRedirect(route('portal.login'));
    $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
    $this->get(route('portal.requests.index'))->assertRedirect(route('portal.login'));
});

test('the landing page shows the new alumni public content', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('St. Bridget College Batangas')
        ->assertSee('Board of Trustees')
        ->assertSee('Mr. John Jeffry M. Mendoza')
        ->assertSee('St. Bridget College, M.H. Del Pilar St., Batangas City')
        ->assertSee('https://www.facebook.com/stbridgetcollege', false);
});

test('the alumni feed stays empty until admins publish posts', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('No alumni posts yet.');

    $alumnus = Alumni::create([
        'student_id' => '2017-2001',
        'first_name' => 'Lina',
        'last_name' => 'Garcia',
        'education_level' => 'College',
        'course' => 'BS Accountancy',
        'year_graduated' => 2021,
        'email' => 'lina@example.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'lina@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $this->actingAs($alumniUser);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('No announcements have been posted by the administrator yet.')
        ->assertSee('No featured activities have been posted by the administrator yet.');
});

test('a predefined administrator can sign in via the portal login endpoint and access the admin dashboard', function () {
    $admin = User::factory()->create([
        'name' => env('ADMIN_NAME', 'System Administrator'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD', 'password123'),
        'role' => 'admin',
    ]);

    $response = $this->post(route('portal.login.attempt'), [
        'email' => $admin->email,
        'password' => env('ADMIN_PASSWORD', 'password123'),
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    $this->assertTrue(auth()->user()->isAdmin());
    $this->get(route('dashboard'))->assertOk()->assertSee('Request lifecycle');
});

test('an administrator can import alumni records from an excel exported csv file', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = UploadedFile::fake()->createWithContent(
        'alumni-import.csv',
        "student_id,first_name,last_name,birthday,education_level,course,year_graduated,email,contact_number,address\n".
        "2015211,Juan,Dela Cruz,2001-03-05,College,BS Information Technology,2024,juan@example.com,09123456789,Manila\n".
        "201425480,Maria,Santos,2002-11-19,Senior High School,STEM,2022,maria@example.com,09998887777,Batangas\n"
    );

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'student_id' => '2015211',
        'birthday' => '2001-03-05 00:00:00',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
    ]);

    $this->assertDatabaseHas('alumni', [
        'student_id' => '201425480',
        'birthday' => '2002-11-19 00:00:00',
        'education_level' => 'Senior High School',
        'course' => 'STEM',
    ]);
});

test('an administrator can import alumni records from an xlsx workbook', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['Name', 'Student ID', 'Birthday', 'Level', 'Program / Grade', 'Year', 'Email / Portal Account'],
        ['Jessa Ramos', '2015211', '2001-03-05', 'College', 'BS Nursing', '2024', 'jessa@example.com'],
        ['Mark Villanueva', '201425480', '2002-11-19', 'Senior High School', 'STEM', '2022', 'mark@example.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'student_id' => '2015211',
        'birthday' => '2001-03-05 00:00:00',
        'education_level' => 'College',
        'course' => 'BS Nursing',
    ]);

    $this->assertDatabaseHas('alumni', [
        'student_id' => '201425480',
        'birthday' => '2002-11-19 00:00:00',
        'education_level' => 'Senior High School',
        'course' => 'STEM',
    ]);

    $this->get(route('alumni.index'))
        ->assertOk()
        ->assertSee('Jessa Ramos')
        ->assertSee('Mark Villanueva')
        ->assertSee('2015211')
        ->assertSee('201425480');
});

test('an administrator can import from the active worksheet in a multi-sheet workbook', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile(
        [
            ['ID NUMBER', 'NAME'],
            ['14965', 'ABAYA, Kim Shen M.'],
        ],
        [
            [
                ['ID NUMBER', 'NAME'],
                ['2015211', 'BANLASAN, John Rey Villa'],
            ],
        ],
        1
    );

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseMissing('alumni', [
        'student_id' => '14965',
    ]);

    $this->assertDatabaseHas('alumni', [
        'student_id' => '2015211',
        'first_name' => 'John Rey Villa',
        'last_name' => 'BANLASAN',
    ]);
});

test('an administrator can import alumni records even when the workbook name header is slightly different', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['Students Name', 'Student ID', 'Birthday', 'Level', 'Program / Grade', 'Year', 'Email / Portal Account'],
        ['Rhodge Rayos', '24-05-79-240', '1985-03-09', 'College', 'BSIT', '2005', 'rayosrhodge0309@gmail.com'],
        ['Jaymar Mandigma', '22-368-1967', '1999-06-01', 'College', 'BSIT', '2027', 'jaymarmandigma06@gmail.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'student_id' => '24-05-79-240',
        'first_name' => 'Rhodge',
        'last_name' => 'Rayos',
    ]);

    $this->assertDatabaseHas('alumni', [
        'student_id' => '22-368-1967',
        'first_name' => 'Jaymar',
        'last_name' => 'Mandigma',
    ]);
});

test('an administrator can import an xlsx workbook with a title row before the headers', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['St. Bridget College Alumni Export'],
        ['Name', 'Student ID', 'Birthday', 'Level', 'Program / Grade', 'Year Graduated', 'Email / Portal Account'],
        ['Jessa Ramos', '2015211', '2001-03-05', 'College', 'BS Nursing', '2024', 'jessa@example.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'student_id' => '2015211',
        'first_name' => 'Jessa',
        'last_name' => 'Ramos',
        'birthday' => '2001-03-05 00:00:00',
        'course' => 'BS Nursing',
        'year_graduated' => 2024,
    ]);
});

test('an administrator can import a workbook that keeps the course in the title row and the address in the sheet columns', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['Bachelor of Elementary Education Major in Content Course'],
        ['ID NUMBER', 'NAME', 'Email Address', 'Residential Address'],
        ['2015211', 'Lina Cruz', 'lina@example.com', 'Quezon City'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'student_id' => '2015211',
        'first_name' => 'Lina',
        'last_name' => 'Cruz',
        'course' => 'Bachelor of Elementary Education Major in Content Course',
        'email' => 'lina@example.com',
        'address' => 'Quezon City',
    ]);
});

test('an administrator can import numeric student ids without changing the imported number', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['Name', 'Student ID', 'Birthday', 'Level', 'Program / Grade', 'Year', 'Email / Portal Account'],
        ['Anna Lopez', '14965', '2000-02-14', 'College', 'BS Education', '2023', 'anna@example.com'],
        ['Lina Cruz', '2015211', '2001-03-05', 'College', 'BS Nursing', '2024', 'lina@example.com'],
        ['Mark Santos', '201425480', '2002-11-19', 'College', 'BS Information Technology', '2024', 'mark@example.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $anna = Alumni::query()->where('email', 'anna@example.com')->firstOrFail();
    $lina = Alumni::query()->where('email', 'lina@example.com')->firstOrFail();
    $mark = Alumni::query()->where('email', 'mark@example.com')->firstOrFail();

    expect($anna->student_id)->toBe('14965');
    expect($anna->student_id_display)->toBe('14965');
    expect($lina->student_id)->toBe('2015211');
    expect($lina->student_id_display)->toBe('2015211');
    expect($mark->student_id)->toBe('201425480');
    expect($mark->student_id_display)->toBe('201425480');

    $this->get(route('alumni.index'))
        ->assertOk()
        ->assertSee('14965')
        ->assertSee('2015211')
        ->assertSee('201425480');
});

test('an administrator can save a five digit legacy id without changing the imported number', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $this->post(route('alumni.store'), [
        'student_id' => '14965',
        'first_name' => 'Rafael',
        'last_name' => 'Santos',
        'birthday' => null,
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2024,
        'email' => 'rafael@example.com',
        'contact_number' => null,
        'address' => null,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'student_id' => '14965',
        'first_name' => 'Rafael',
        'last_name' => 'Santos',
    ]);

    $this->get(route('alumni.index'))
        ->assertOk()
        ->assertSee('14965');
});

test('an administrator can import xlsx student ids with Excel number formatting exactly as displayed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['Name', 'Student ID', 'Birthday', 'Level', 'Program / Grade', 'Year', 'Email / Portal Account'],
        ['Olive Santos', ['raw' => '14965', 'style' => 1], '2000-02-14', 'College', 'BS Education', '2023', 'olive@example.com'],
        ['Marco Reyes', ['raw' => '201425480', 'style' => 2], '2002-11-19', 'College', 'BS Information Technology', '2024', 'marco@example.com'],
        ['Kim Abaya', ['raw' => '14965', 'style' => 3], '2001-01-01', 'College', 'BS Education', '2024', 'kim@example.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'student_id' => '014965',
        'email' => 'olive@example.com',
    ]);

    $this->assertDatabaseHas('alumni', [
        'student_id' => '20-1425-480',
        'email' => 'marco@example.com',
    ]);

    $this->assertDatabaseHas('alumni', [
        'student_id' => '2014965',
        'email' => 'kim@example.com',
    ]);

    $this->get(route('alumni.index'))
        ->assertOk()
        ->assertSee('014965')
        ->assertSee('20-1425-480')
        ->assertSee('2014965');
});

test('an administrator can search alumni by name and student id formats', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Alumni::create([
        'student_id' => '201425480',
        'first_name' => 'Mirko',
        'last_name' => 'Santos',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2024,
        'email' => 'mirko@example.com',
    ]);

    $this->get(route('alumni.index', ['search' => 'Mirko']))
        ->assertOk()
        ->assertSee('Mirko Santos');

    $this->get(route('alumni.index', ['search' => '20-1425-480']))
        ->assertOk()
        ->assertSee('Mirko Santos');
});

test('an administrator can import a partial xlsx alumni row without a student id or graduation details', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['Name', 'Email'],
        ['Mika Lopez', 'mika@example.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'first_name' => 'Mika',
        'last_name' => 'Lopez',
        'email' => 'mika@example.com',
        'course' => 'Pending',
        'year_graduated' => 0,
    ]);

    $alumnus = Alumni::query()->where('email', 'mika@example.com')->firstOrFail();

    expect($alumnus->student_id)->toStartWith('TEMP-');
    expect($alumnus->student_id_display)->toBe('-');
    expect($alumnus->course)->toBe('Pending');
    expect($alumnus->year_graduated)->toBe(0);
});

test('an administrator can import an xlsx workbook with student name and degree program columns', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['Student Name', 'Degree Program', 'Email'],
        ['Mika Lopez', 'BS Information Technology', 'mika@example.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $alumnus = Alumni::query()->where('email', 'mika@example.com')->firstOrFail();

    expect($alumnus->first_name)->toBe('Mika');
    expect($alumnus->last_name)->toBe('Lopez');
    expect($alumnus->course)->toBe('BS Information Technology');
});

test('an administrator can import an xlsx workbook with grade-based year values and see the combined academic label', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $file = buildXlsxImportFile([
        ['Name', 'Student ID', 'Level', 'Program / Grade', 'Year', 'Email / Portal Account'],
        ['Novelle Dela Rosa', '2018-2002', 'Elementary', 'Bachelor of Secondary Education Major in Values Education', 'Grade 6', 'novelle@example.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $alumnus = Alumni::query()->where('student_id', '2018-2002')->firstOrFail();

    expect($alumnus->education_level)->toBe('Elementary');
    expect($alumnus->course)->toBe('Bachelor of Secondary Education Major in Values Education');
    expect($alumnus->year_graduated)->toBe(6);

    $this->get(route('alumni.index'))
        ->assertOk()
        ->assertSee('Bachelor of Secondary Education Major in Values Education - Elementary - Grade 6');
});

test('an administrator can import an xlsx workbook and update an existing alumni record by student id', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Alumni::create([
        'student_id' => '2015211',
        'first_name' => 'Jessa',
        'last_name' => 'Ramos',
        'education_level' => 'College',
        'course' => 'Old Program',
        'year_graduated' => 2023,
        'email' => 'old-jessa@example.com',
        'contact_number' => '09111111111',
        'address' => 'Old Address',
    ]);

    $file = buildXlsxImportFile([
        ['Name', 'Student ID', 'Level', 'Program / Grade', 'Year', 'Email / Portal Account', 'Contact Number', 'Address'],
        ['Jessa Ramos', '2015211', 'College', 'BS Nursing', '2024', 'jessa@example.com', '09991234567', 'New Address'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseCount('alumni', 1);
    $this->assertDatabaseHas('alumni', [
        'student_id' => '2015211',
        'education_level' => 'College',
        'course' => 'BS Nursing',
        'year_graduated' => 2024,
        'email' => 'jessa@example.com',
        'contact_number' => '09991234567',
        'address' => 'New Address',
    ]);
});

test('an administrator can reimport an xlsx workbook and correct an old formatted student id', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Alumni::create([
        'student_id' => '20-1425-480',
        'first_name' => 'Marco',
        'last_name' => 'Reyes',
        'education_level' => 'College',
        'course' => 'Old Program',
        'year_graduated' => 2023,
        'email' => 'old-marco@example.com',
    ]);

    $file = buildXlsxImportFile([
        ['Name', 'Student ID', 'Level', 'Program / Grade', 'Year', 'Email / Portal Account'],
        ['Marco Reyes', '201425480', 'College', 'BS Information Technology', '2024', 'marco@example.com'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseCount('alumni', 1);
    $this->assertDatabaseHas('alumni', [
        'student_id' => '201425480',
        'email' => 'marco@example.com',
    ]);
    $this->assertDatabaseMissing('alumni', [
        'student_id' => '20-1425-480',
    ]);
});

test('an administrator can clear nullable alumni fields from an xlsx workbook row when the cells are blank', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Alumni::create([
        'student_id' => '2015211',
        'first_name' => 'Jessa',
        'last_name' => 'Ramos',
        'education_level' => 'College',
        'course' => 'Old Program',
        'year_graduated' => 2023,
        'email' => 'old-jessa@example.com',
        'contact_number' => '09111111111',
        'address' => 'Old Address',
    ]);

    $file = buildXlsxImportFile([
        ['Name', 'Student ID', 'Level', 'Program / Grade', 'Year', 'Email / Portal Account', 'Contact Number', 'Address'],
        ['Jessa Ramos', '2015211', 'College', 'BS Nursing', '2024', '', '', ''],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
    ])->assertRedirect(route('alumni.index'));

    $alumnus = Alumni::query()->where('student_id', '2015211')->firstOrFail();

    expect($alumnus->course)->toBe('BS Nursing');
    expect($alumnus->year_graduated)->toBe(2024);
    expect($alumnus->email)->toBeNull();
    expect($alumnus->contact_number)->toBeNull();
    expect($alumnus->address)->toBeNull();
});

test('an administrator can replace the existing alumni list with the rows in an xlsx workbook', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Alumni::create([
        'student_id' => 'OLD-1001',
        'first_name' => 'Old',
        'last_name' => 'Record',
        'education_level' => 'College',
        'course' => 'Old Program',
        'year_graduated' => 2020,
    ]);

    $file = buildXlsxImportFile([
        ['ID No.', 'First Name', 'Middle Name', 'Last Name', 'Level', 'Program', 'Year'],
        ['14965', 'Kim', 'Shen M.', 'Abaya', 'Elementary', 'GRADE 6', '2024'],
    ]);

    $this->post(route('alumni.import'), [
        'file' => $file,
        'replace_existing' => '1',
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseMissing('alumni', ['student_id' => 'OLD-1001']);
    $this->assertDatabaseHas('alumni', [
        'student_id' => '14965',
        'first_name' => 'Kim Shen M.',
        'last_name' => 'Abaya',
        'education_level' => 'Elementary',
        'course' => 'GRADE 6',
        'year_graduated' => 2024,
    ]);
});

test('admins can select all alumni records on the current page and bulk delete them', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $firstAlumnus = Alumni::create([
        'student_id' => '2012-1001',
        'first_name' => 'Alyssa',
        'last_name' => 'Reyes',
        'education_level' => 'College',
        'course' => 'BS Nursing',
        'year_graduated' => 2016,
        'email' => 'alyssa@example.com',
    ]);

    $linkedUser = User::factory()->create([
        'name' => $firstAlumnus->full_name,
        'email' => 'alyssa@example.com',
        'role' => 'alumni',
        'alumni_id' => $firstAlumnus->id,
    ]);

    $profilePhotoPath = 'profile-photos/alyssa.jpg';
    Storage::disk('public')->put($profilePhotoPath, 'photo');
    $linkedUser->forceFill(['profile_photo_path' => $profilePhotoPath])->save();

    $secondAlumnus = Alumni::create([
        'student_id' => '2012-1002',
        'first_name' => 'Marco',
        'last_name' => 'Santos',
        'education_level' => 'Senior High School',
        'course' => 'STEM',
        'year_graduated' => 2015,
        'email' => 'marco@example.com',
    ]);

    $thirdAlumnus = Alumni::create([
        'student_id' => '2012-1003',
        'first_name' => 'Nina',
        'last_name' => 'Lopez',
        'education_level' => 'Elementary',
        'course' => 'Grade 6',
        'year_graduated' => 2010,
        'email' => 'nina@example.com',
    ]);

    $this->get(route('alumni.index'))
        ->assertOk()
        ->assertSee('Select one or more records from this page')
        ->assertSee('Delete Selected');

    $this->delete(route('alumni.bulk-destroy'), [
        'alumni_ids' => [$firstAlumnus->id, $secondAlumnus->id],
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseMissing('alumni', ['id' => $firstAlumnus->id]);
    $this->assertDatabaseMissing('alumni', ['id' => $secondAlumnus->id]);
    $this->assertDatabaseMissing('users', ['id' => $linkedUser->id]);
    $this->assertDatabaseHas('alumni', ['id' => $thirdAlumnus->id]);
    Storage::disk('public')->assertMissing($profilePhotoPath);
});

test('admins can view alumni grouped by course on the index page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Alumni::create([
        'student_id' => '2018-2001',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'education_level' => 'College',
        'course' => 'Bachelor of Elementary Education Major in Content Course',
        'year_graduated' => 2022,
        'email' => 'maria@example.com',
    ]);

    Alumni::create([
        'student_id' => '2018-2002',
        'first_name' => 'Novelle',
        'last_name' => 'Dela Rosa',
        'education_level' => 'College',
        'course' => 'Bachelor of Secondary Education Major in Values Education',
        'year_graduated' => 2022,
        'email' => 'novelle@example.com',
    ]);

    $this->get(route('alumni.index'))
        ->assertOk()
        ->assertSee('Bachelor of Elementary Education Major in Content Course')
        ->assertSee('Bachelor of Secondary Education Major in Values Education')
        ->assertSee('Bachelor of Elementary Education Major in Content Course - College - Year 2022')
        ->assertSee('Bachelor of Secondary Education Major in Values Education - College - Year 2022')
        ->assertSee('Email')
        ->assertSee('ID Number')
        ->assertSee('Name')
        ->assertSee('Maria Santos')
        ->assertSee('maria@example.com')
        ->assertSee('Novelle Dela Rosa');
});

test('admins can load 100 alumni per page and bulk delete 100 selected records in one request', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $alumniIds = [];

    for ($index = 1; $index <= 101; $index++) {
        $alumnus = Alumni::create([
            'student_id' => '2024-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            'first_name' => 'Record',
            'last_name' => (string) $index,
            'education_level' => 'College',
            'course' => 'BS Information Technology',
            'year_graduated' => 2024,
            'email' => "record{$index}@example.com",
        ]);

        if ($index <= 100) {
            $alumniIds[] = $alumnus->id;
        }
    }

    $this->get(route('alumni.index'))
        ->assertOk()
        ->assertViewHas('alumni', function ($alumni) {
            return $alumni->count() === 100;
        });

    $this->delete(route('alumni.bulk-destroy'), [
        'alumni_ids' => $alumniIds,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseCount('alumni', 1);
});

test('admins can access the activity post composer', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    $this->get(route('activities.create'))
        ->assertOk()
        ->assertSee('Live Post Preview')
        ->assertSee("What's happening for alumni?", false)
        ->assertSee('Post Headline');
});

test('an alumnus can claim an imported record and access the alumni portal', function () {
    $alumnus = Alumni::create([
        'student_id' => '2015211',
        'first_name' => 'Liza',
        'last_name' => 'Cruz',
        'education_level' => 'College',
        'course' => 'BSEd',
        'year_graduated' => 2023,
        'email' => 'liza@example.com',
    ]);

    $response = $this->post(route('portal.register.store'), [
        'student_id' => '2015211',
        'first_name' => 'Liza',
        'last_name' => 'Cruz',
        'year_graduated' => 2023,
        'email' => 'liza@example.com',
        'password' => 'password12',
        'password_confirmation' => 'password12',
    ]);

    $response->assertRedirect(route('portal.otp.create'));
    $this->assertDatabaseHas('alumni', [
        'id' => $alumnus->id,
        'student_id' => '2015211',
    ]);
    $this->assertAuthenticated();
    $this->assertTrue(auth()->user()->isAlumni());
    $this->assertSame($alumnus->id, auth()->user()->alumni_id);

    $this->withSession([
        'portal_otp_verified_user_id' => auth()->id(),
    ])->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('My Alumni Record');
});

test('admins can edit student ids without automatic formatting', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $alumnus = Alumni::create([
        'student_id' => '201425480',
        'first_name' => 'Mark',
        'last_name' => 'Santos',
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2024,
        'email' => 'mark@example.com',
    ]);

    $this->get(route('alumni.edit', $alumnus))
        ->assertOk()
        ->assertSee('201425480', false);

    $this->put(route('alumni.update', $alumnus), [
        'student_id' => '20-1425-480',
        'first_name' => 'Mark',
        'last_name' => 'Santos',
        'birthday' => null,
        'education_level' => 'College',
        'course' => 'BS Information Technology',
        'year_graduated' => 2024,
        'email' => 'mark@example.com',
        'contact_number' => null,
        'address' => null,
    ])->assertRedirect(route('alumni.index'));

    $this->assertDatabaseHas('alumni', [
        'id' => $alumnus->id,
        'student_id' => '20-1425-480',
    ]);
});

test('alumni can submit requests but only admins can process them', function () {
    $alumnus = Alumni::create([
        'student_id' => '2016-0010',
        'first_name' => 'Nina',
        'last_name' => 'Reyes',
        'education_level' => 'Senior High School',
        'course' => 'HUMSS',
        'year_graduated' => 2021,
        'email' => 'nina@example.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'nina@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $admin = User::factory()->create([
        'email' => 'records-admin@example.com',
        'role' => 'admin',
    ]);

    $this->actingAs($alumniUser);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->post(route('portal.requests.store'), [
        'request_type' => 'Transcript of Records',
        'year_requested' => 2021,
    ])->assertRedirect(route('portal.requests.index'));

    $requestRecord = RecordRequest::first();
    expect($requestRecord)->not->toBeNull();

    $this->get(route('requests.index'))->assertForbidden();
    $this->get(route('portal.requests.index'))->assertOk()->assertSee('Transcript of Records');

    $this->actingAs($admin);

    $this->get(route('requests.index'))->assertOk()->assertSee('Record Request Processing');

    $this->patch(route('requests.status', $requestRecord), [
        'status' => 'ready_for_pickup',
        'admin_notes' => 'Bring your school ID when claiming the document.',
    ])->assertRedirect(route('requests.index'));

    $this->assertDatabaseHas('requests', [
        'id' => $requestRecord->id,
        'status' => 'ready_for_pickup',
        'admin_notes' => 'Bring your school ID when claiming the document.',
        'processed_by' => $admin->id,
    ]);
});

test('users can upload a profile photo from the profile settings page', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4//8/AwAI/AL+KDAdmwAAAABJRU5ErkJggg==');

    $this->actingAs($admin);

    $this->put(route('profile.update'), [
        'name' => 'Updated Admin',
        'email' => 'updated-admin@example.com',
        'password' => '',
        'password_confirmation' => '',
        'profile_photo' => UploadedFile::fake()->createWithContent('avatar.png', $png),
    ])->assertRedirect(route('profile.edit'));

    $admin->refresh();

    expect($admin->profile_photo_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($admin->profile_photo_path))->toBeTrue();

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('/profile-photo/'.$admin->id, false);

    $this->get(route('profile.photo', $admin))
        ->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'name' => 'Updated Admin',
        'email' => 'updated-admin@example.com',
    ]);
});

test('users can remove their current profile photo from the profile settings page', function () {
    Storage::fake('public');

    $admin = User::factory()->create([
        'role' => 'admin',
        'profile_photo_path' => 'profile-photos/admin-avatar.png',
    ]);

    Storage::disk('public')->put($admin->profile_photo_path, 'profile photo');

    $this->actingAs($admin);

    $this->put(route('profile.update'), [
        'name' => $admin->name,
        'email' => $admin->email,
        'password' => '',
        'password_confirmation' => '',
        'remove_profile_photo' => '1',
    ])->assertRedirect(route('profile.edit'));

    $admin->refresh();

    expect($admin->profile_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing('profile-photos/admin-avatar.png');

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('/profile-photo/'.$admin->id, false);
});

test('admins can manage leadership profile photos from the landing content workspace', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4//8/AwAI/AL+KDAdmwAAAABJRU5ErkJggg==');

    $this->actingAs($admin);

    $this->put(route('admin.settings.landing-profiles.update'), [
        'board_members' => [
            'anna-maria-clara-palacios' => [
                'name' => 'Dr. Anna Maria Clara Palacios',
                'role' => 'President',
            ],
        ],
        'alumni_office_team' => [
            'john-jeffry-m-mendoza' => [
                'name' => 'Mr. John Jeffry M. Mendoza',
                'role' => 'External Relation Officer / EDU HUB Manager',
                'details' => 'Coordinates external relations and alumni-facing engagements for the institution.',
            ],
            'gladys-d-alcoba' => [
                'name' => 'Ms. Gladys D. Alcoba',
                'role' => 'Alumni Office Staff',
                'details' => 'Supports alumni office concerns and day-to-day communication for graduates and visiting stakeholders.',
            ],
        ],
        'board_member_photos' => [
            'anna-maria-clara-palacios' => UploadedFile::fake()->createWithContent('board.png', $png),
        ],
        'alumni_office_team_photos' => [
            'john-jeffry-m-mendoza' => UploadedFile::fake()->createWithContent('office.png', $png),
        ],
    ])->assertRedirect(route('admin.settings.landing-video.edit'));

    $boardProfiles = json_decode((string) SiteSetting::getValue('landing_board_members'), true);
    $teamProfiles = json_decode((string) SiteSetting::getValue('landing_alumni_office_team'), true);

    expect($boardProfiles[0]['photo_path'] ?? null)->not->toBeNull();
    expect($teamProfiles[0]['photo_path'] ?? null)->not->toBeNull();
    expect(Storage::disk('public')->exists($boardProfiles[0]['photo_path']))->toBeTrue();
    expect(Storage::disk('public')->exists($teamProfiles[0]['photo_path']))->toBeTrue();

    $this->get(route('home', ['preview' => 1]))
        ->assertOk()
        ->assertSee('Board of Trustees')
        ->assertSee('Alumni Office Team')
        ->assertSee('landing-profile-media/board-members/anna-maria-clara-palacios', false)
        ->assertSee('landing-profile-media/alumni-office-team/john-jeffry-m-mendoza', false);
});

test('admins can upload a landing slider video and show it on the landing page', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    $this->put(route('admin.settings.landing-video.update'), [
        'photo_files' => [
            UploadedFile::fake()->create('campus-highlight.mp4', 1200, 'video/mp4'),
        ],
        'new_slide_titles' => [
            'Bridgetine Campus Highlight',
        ],
        'new_slide_details' => [
            'A short video tour of the campus.',
        ],
    ])->assertRedirect(route('admin.settings.landing-video.edit'));

    $slides = json_decode((string) SiteSetting::getValue('landing_school_ad_photo_gallery'), true);

    expect($slides)->toBeArray();
    expect($slides[0]['type'] ?? null)->toBe('video');
    expect($slides[0]['path'] ?? null)->toStartWith('storage:landing-slider/');

    $this->get(route('home', ['preview' => 1]))
        ->assertOk()
        ->assertSee(route('landing-media.show', ['kind' => 'video', 'index' => 0]), false)
        ->assertSee('Bridgetine Campus Highlight');
});

test('admins can delete the current landing slider media from the settings page', function () {
    Storage::fake('public');

    Storage::disk('public')->put('landing-slider/sample-slide.png', 'sample-image');

    SiteSetting::setMany([
        'landing_school_ad_photo_gallery' => json_encode([
            [
                'path' => 'storage:landing-slider/sample-slide.png',
                'type' => 'photo',
                'title' => 'Sample Slide',
                'detail' => 'A sample landing slider image.',
            ],
        ], JSON_UNESCAPED_SLASHES),
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    $this->put(route('admin.settings.landing-video.update'), [
        'remove_photos' => '1',
    ])->assertRedirect(route('admin.settings.landing-video.edit'));

    expect(SiteSetting::getValue('landing_school_ad_photo_gallery'))->toBe('[]');
    expect(Storage::disk('public')->missing('landing-slider/sample-slide.png'))->toBeTrue();
});

test('admins can post announcements and activities that alumni can see', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    $this->post(route('announcements.store'), [
        'label' => 'Records',
        'title' => 'Transcript release schedule',
        'content' => 'Transcript requests released this week will be claimed from the registrar.',
        'is_published' => '1',
    ])->assertRedirect(route('announcements.index'));

    $this->post(route('activities.store'), [
        'theme' => 'Community',
        'title' => 'Alumni outreach drive',
        'description' => 'Join the weekend alumni outreach and campus service activity.',
        'activity_date' => now()->addWeek()->toDateString(),
        'location' => 'SBC Batangas',
        'is_published' => '1',
    ])->assertRedirect(route('activities.index'));

    $this->assertDatabaseHas('announcements', ['title' => 'Transcript release schedule']);
    $this->assertDatabaseHas('activities', ['title' => 'Alumni outreach drive']);

    auth()->logout();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('SBC Alumni Feed')
        ->assertSee('Transcript release schedule')
        ->assertSee('Alumni outreach drive');

    $alumnus = Alumni::create([
        'student_id' => '2018-7788',
        'first_name' => 'Paula',
        'last_name' => 'Reyes',
        'education_level' => 'College',
        'course' => 'BSBA',
        'year_graduated' => 2022,
        'email' => 'paula@example.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'paula@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $this->actingAs($alumniUser);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee('Transcript release schedule')
        ->assertSee('Alumni outreach drive');
});

test('the landing page shows uploaded alumni posts and totals', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    Activity::create([
        'theme' => 'Community',
        'title' => 'Alumni outreach drive',
        'description' => 'Join the weekend alumni outreach and campus service activity.',
        'activity_date' => now()->addWeek()->toDateString(),
        'location' => 'SBC Batangas',
        'is_published' => true,
    ]);

    Activity::create([
        'theme' => 'Service',
        'title' => 'Career mentorship session',
        'description' => 'Mentors will guide graduating alumni on career planning.',
        'activity_date' => now()->addDays(10)->toDateString(),
        'location' => 'Online',
        'is_published' => true,
    ]);

    Activity::create([
        'theme' => 'Reunion',
        'title' => 'Homecoming volunteer signup',
        'description' => 'Volunteers can sign up for the coming homecoming support team.',
        'activity_date' => now()->addDays(14)->toDateString(),
        'location' => 'Alumni Office',
        'is_published' => true,
    ]);

    auth()->logout();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('SBC Alumni Feed')
        ->assertSee('Alumni outreach drive')
        ->assertSee('Career mentorship session')
        ->assertSee('Homecoming volunteer signup')
        ->assertSee('3 alumni posts published');
});

test('admins can upload an activity photo that appears on public and alumni views', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4//8/AwAI/AL+KDAdmwAAAABJRU5ErkJggg==');

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    $this->post(route('activities.store'), [
        'theme' => 'Community',
        'title' => 'Campus clean-up drive',
        'description' => 'Join the alumni clean-up drive this weekend.',
        'activity_date' => now()->addDays(10)->toDateString(),
        'location' => 'SBC Batangas',
        'is_published' => '1',
        'media' => UploadedFile::fake()->createWithContent('cleanup.png', $png),
    ])->assertRedirect(route('activities.index'));

    $activity = Activity::query()->where('title', 'Campus clean-up drive')->firstOrFail();

    expect($activity->media_type)->toBe('image');
    expect($activity->media_path)->not->toBeNull();

    auth()->logout();

    $this->get(route('activities.media', $activity))->assertOk();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('activities.show', $activity));

    $alumnus = Alumni::create([
        'student_id' => '2020-2201',
        'first_name' => 'Kara',
        'last_name' => 'Diaz',
        'education_level' => 'College',
        'course' => 'BS Biology',
        'year_graduated' => 2024,
        'email' => 'kara@example.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'kara@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $this->actingAs($alumniUser);

    $this->withSession([
        'portal_otp_verified_user_id' => $alumniUser->id,
    ])->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee(route('activities.media', $activity));
});

test('visitors can open an alumni post detail page and see the view count increase', function () {
    $activity = Activity::create([
        'theme' => 'Reunion',
        'title' => 'Bridgetine alumni night',
        'description' => 'An evening gathering for SBC alumni, faculty, and friends.',
        'activity_date' => now()->addDays(12)->toDateString(),
        'location' => 'St. Bridget College Batangas',
        'views_count' => 2,
        'is_published' => true,
    ]);

    $this->get(route('activities.show', $activity))
        ->assertOk()
        ->assertSee('Bridgetine alumni night')
        ->assertSee('An evening gathering for SBC alumni, faculty, and friends.')
        ->assertSee('3 views')
        ->assertSee(now()->addDays(12)->format('F d, Y'))
        ->assertSee('St. Bridget College Batangas');

    $activity->refresh();

    expect($activity->views_count)->toBe(3);
});

test('admins can upload a video for an activity', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    $this->post(route('activities.store'), [
        'theme' => 'Reunion',
        'title' => 'Homecoming teaser',
        'description' => 'A short teaser for the upcoming homecoming.',
        'is_published' => '1',
        'media' => UploadedFile::fake()->create('teaser.mp4', 1200, 'video/mp4'),
    ])->assertRedirect(route('activities.index'));

    $activity = Activity::query()->where('title', 'Homecoming teaser')->firstOrFail();

    expect($activity->media_type)->toBe('video');
    expect($activity->media_path)->not->toBeNull();

    $this->get(route('activities.media', $activity))->assertOk();
});

test('only admins can manage announcements and activities', function () {
    $announcement = Announcement::create([
        'label' => 'Records',
        'title' => 'Registrar notice',
        'content' => 'Bring a valid ID when claiming documents.',
        'is_published' => true,
        'published_at' => now(),
    ]);

    $activity = Activity::create([
        'theme' => 'Community',
        'title' => 'Volunteer Saturday',
        'description' => 'Join the alumni volunteer activity on campus.',
        'activity_date' => now()->addWeek()->toDateString(),
        'location' => 'SBC Batangas',
        'is_published' => true,
    ]);

    $alumnus = Alumni::create([
        'student_id' => '2019-0140',
        'first_name' => 'Mira',
        'last_name' => 'Santos',
        'education_level' => 'College',
        'course' => 'BSIT',
        'year_graduated' => 2023,
        'email' => 'mira@example.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'mira@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $this->actingAs($alumniUser);

    $this->get(route('announcements.index'))->assertForbidden();
    $this->get(route('announcements.edit', $announcement))->assertForbidden();
    $this->post(route('announcements.store'), [
        'label' => 'Community',
        'title' => 'Alumni-only attempt',
        'content' => 'This should be blocked.',
        'is_published' => '1',
    ])->assertForbidden();

    $this->get(route('activities.index'))->assertForbidden();
    $this->get(route('activities.edit', $activity))->assertForbidden();
    $this->post(route('activities.store'), [
        'theme' => 'Service',
        'title' => 'Blocked activity',
        'description' => 'This should not be created.',
        'is_published' => '1',
    ])->assertForbidden();

    $this->assertDatabaseMissing('announcements', ['title' => 'Alumni-only attempt']);
    $this->assertDatabaseMissing('activities', ['title' => 'Blocked activity']);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    $this->get(route('announcements.index'))->assertOk();
    $this->get(route('activities.index'))->assertOk();
});

test('alumni profile updates sync back to the admin-facing alumni record', function () {
    $alumnus = Alumni::create([
        'student_id' => '2015-1001',
        'first_name' => 'Ana',
        'last_name' => 'Lopez',
        'education_level' => 'College',
        'course' => 'BS Psychology',
        'year_graduated' => 2020,
        'email' => 'ana@example.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'ana@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $this->actingAs($alumniUser);

    $this->put(route('profile.update'), [
        'name' => 'Ana Marie Lopez',
        'email' => 'ana.marie@example.com',
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect(route('profile.edit'));

    $alumnus->refresh();

    expect($alumnus->first_name)->toBe('Ana Marie');
    expect($alumnus->last_name)->toBe('Lopez');
    expect($alumnus->email)->toBe('ana.marie@example.com');
});

test('admins can open the alumni edit page from the admin workspace', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $alumnus = Alumni::create([
        'student_id' => '2014-3333',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'education_level' => 'College',
        'course' => 'BS Biology',
        'year_graduated' => 2018,
        'email' => 'maria@example.com',
    ]);

    $this->actingAs($admin);

    $this->get(route('alumni.edit', $alumnus))
        ->assertOk()
        ->assertSee('Edit Alumni');
});

test('admins can edit user accounts and keep linked alumni records aligned', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $alumnus = Alumni::create([
        'student_id' => '2014-2222',
        'first_name' => 'Cris',
        'last_name' => 'Dela Cruz',
        'education_level' => 'Senior High School',
        'course' => 'STEM',
        'year_graduated' => 2019,
        'email' => 'cris@example.com',
    ]);

    $alumniUser = User::factory()->create([
        'name' => $alumnus->full_name,
        'email' => 'cris@example.com',
        'role' => 'alumni',
        'alumni_id' => $alumnus->id,
    ]);

    $this->actingAs($admin);

    $this->put(route('users.update', $alumniUser), [
        'name' => 'Cristina Dela Cruz',
        'email' => 'cristina@example.com',
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect(route('users.index'));

    $alumniUser->refresh();
    $alumnus->refresh();

    expect($alumniUser->name)->toBe('Cristina Dela Cruz');
    expect($alumnus->first_name)->toBe('Cristina Dela');
    expect($alumnus->last_name)->toBe('Cruz');
    expect($alumnus->email)->toBe('cristina@example.com');
});

function buildXlsxImportFile(array $rows, array $additionalSheets = [], int $activeSheetIndex = 0): UploadedFile
{
    $sheets = array_values(array_merge([$rows], $additionalSheets));
    $activeSheetIndex = max(0, min($activeSheetIndex, count($sheets) - 1));
    $baseDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alumni-xlsx-'.bin2hex(random_bytes(4));
    $worksheetDirectory = $baseDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'worksheets';
    $relationshipsDirectory = $baseDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'_rels';
    $rootRelationshipsDirectory = $baseDirectory.DIRECTORY_SEPARATOR.'_rels';
    $workbookPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alumni-xlsx-'.bin2hex(random_bytes(8)).'.zip';
    $hasStyles = false;

    foreach ($sheets as $sheetRows) {
        foreach ($sheetRows as $row) {
            foreach ($row as $value) {
                if (is_array($value) && isset($value['style'])) {
                    $hasStyles = true;
                    break 3;
                }
            }
        }
    }

    if (! mkdir($worksheetDirectory, 0777, true) && ! is_dir($worksheetDirectory)) {
        throw new RuntimeException('Unable to create a temporary Excel file.');
    }

    if (! mkdir($relationshipsDirectory, 0777, true) && ! is_dir($relationshipsDirectory)) {
        throw new RuntimeException('Unable to create a temporary Excel file.');
    }

    if (! mkdir($rootRelationshipsDirectory, 0777, true) && ! is_dir($rootRelationshipsDirectory)) {
        throw new RuntimeException('Unable to create a temporary Excel file.');
    }

    file_put_contents($baseDirectory.DIRECTORY_SEPARATOR.'[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        .'<Default Extension="xml" ContentType="application/xml"/>'
        .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        .implode('', array_map(
            fn (int $index): string => '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>',
            range(1, count($sheets))
        ))
        .($hasStyles ? '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' : '')
        .'</Types>'
    );

    if ($hasStyles) {
        file_put_contents(
            $baseDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="3">'
            .'<numFmt numFmtId="164" formatCode="000000"/>'
            .'<numFmt numFmtId="165" formatCode="00-0000-000"/>'
            .'<numFmt numFmtId="166" formatCode="&quot;20&quot;00000"/>'
            .'</numFmts>'
            .'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'</styleSheet>'
        );
    }

    file_put_contents(
        $rootRelationshipsDirectory.DIRECTORY_SEPARATOR.'.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        .'</Relationships>'
    );

    file_put_contents(
        $baseDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        .'<bookViews><workbookView activeTab="'.$activeSheetIndex.'"/></bookViews>'
        .'<sheets>'
        .implode('', array_map(
            fn (int $index): string => '<sheet name="Sheet'.$index.'" sheetId="'.$index.'" r:id="rId'.$index.'"/>',
            range(1, count($sheets))
        ))
        .'</sheets>'
        .'</workbook>'
    );

    file_put_contents(
        $relationshipsDirectory.DIRECTORY_SEPARATOR.'workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .implode('', array_map(
            fn (int $index): string => '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>',
            range(1, count($sheets))
        ))
        .'</Relationships>'
    );

    foreach ($sheets as $sheetIndex => $rowsForSheet) {
        $sheetRows = [];

        foreach ($rowsForSheet as $rowIndex => $row) {
            $cells = [];

            foreach (array_values($row) as $columnIndex => $value) {
                $cellReference = buildXlsxCellReference($columnIndex, $rowIndex + 1);

                if (is_array($value)) {
                    $style = (int) ($value['style'] ?? 0);
                    $rawValue = htmlspecialchars((string) ($value['raw'] ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
                    $cells[] = "<c r=\"{$cellReference}\" s=\"{$style}\"><v>{$rawValue}</v></c>";

                    continue;
                }

                $escapedValue = htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $cells[] = "<c r=\"{$cellReference}\" t=\"inlineStr\"><is><t>{$escapedValue}</t></is></c>";
            }

            $sheetRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        file_put_contents(
            $worksheetDirectory.DIRECTORY_SEPARATOR.'sheet'.($sheetIndex + 1).'.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'</worksheet>'
        );
    }

    $entryMap = [
        '[Content_Types].xml' => $baseDirectory.DIRECTORY_SEPARATOR.'[Content_Types].xml',
        '_rels/.rels' => $rootRelationshipsDirectory.DIRECTORY_SEPARATOR.'.rels',
        'xl/workbook.xml' => $baseDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'workbook.xml',
        'xl/_rels/workbook.xml.rels' => $relationshipsDirectory.DIRECTORY_SEPARATOR.'workbook.xml.rels',
    ];

    foreach (range(1, count($sheets)) as $sheetIndex) {
        $entryMap['xl/worksheets/sheet'.$sheetIndex.'.xml'] = $worksheetDirectory.DIRECTORY_SEPARATOR.'sheet'.$sheetIndex.'.xml';
    }

    if ($hasStyles) {
        $entryMap['xl/styles.xml'] = $baseDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'styles.xml';
    }

    $archiveEntries = '';

    foreach ($entryMap as $entryName => $sourcePath) {
        $archiveEntries .= sprintf(
            '$entry = $archive.CreateEntry(\'%s\'); $entryStream = $entry.Open(); try { $bytes = [System.IO.File]::ReadAllBytes(\'%s\'); $entryStream.Write($bytes, 0, $bytes.Length) } finally { $entryStream.Dispose() };',
            str_replace("'", "''", $entryName),
            str_replace("'", "''", $sourcePath)
        );
    }

    $command = sprintf(
        'powershell -NoProfile -Command "$ErrorActionPreference=\'Stop\'; Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem; $zipStream = [System.IO.File]::Open(\'%s\', [System.IO.FileMode]::Create); try { $archive = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create, $false); %s $archive.Dispose() } finally { $zipStream.Dispose() }"',
        str_replace("'", "''", $workbookPath),
        $archiveEntries
    );

    shell_exec($command);

    if (! is_file($workbookPath)) {
        deleteDirectoryRecursively($baseDirectory);
        throw new RuntimeException('Unable to create the temporary Excel file.');
    }

    deleteDirectoryRecursively($baseDirectory);

    return new UploadedFile(
        $workbookPath,
        'alumni-import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

function buildXlsxCellReference(int $columnIndex, int $rowIndex): string
{
    $columnLetters = '';
    $index = $columnIndex + 1;

    while ($index > 0) {
        $remainder = ($index - 1) % 26;
        $columnLetters = chr(65 + $remainder).$columnLetters;
        $index = intdiv($index - 1, 26);
    }

    return $columnLetters.$rowIndex;
}

function deleteDirectoryRecursively(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($directory);
}
