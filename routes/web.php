<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\AlumniImportController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LandingVideoSettingController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\PortalDashboardController;
use App\Http\Controllers\PortalOtpController;
use App\Http\Controllers\PortalRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordRequestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');
Route::get('/landing-media/{kind}/{index?}', [LandingVideoSettingController::class, 'media'])
    ->where('kind', 'photo|video|poster')
    ->whereNumber('index')
    ->name('landing-media.show');
Route::get('/landing-profile-media/{group}/{key}', [LandingVideoSettingController::class, 'profileMedia'])
    ->name('landing-profile-media.show');
Route::get('/alumni-posts/{activity}', [ActivityController::class, 'show'])
    ->whereNumber('activity')
    ->name('activities.show');
Route::get('/events/{event}/media', [EventController::class, 'media'])
    ->whereNumber('event')
    ->name('events.media');
Route::get('/announcements/{announcement}/media', [AnnouncementController::class, 'media'])
    ->whereNumber('announcement')
    ->name('announcements.media');
Route::get('/activities/{activity}/media', [ActivityController::class, 'media'])
    ->whereNumber('activity')
    ->name('activities.media');

// Guest middleware group
Route::middleware(['guest'])->group(function () {
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/login', [PortalAuthController::class, 'create'])->name('login');
        Route::post('/login', [PortalAuthController::class, 'store'])->name('login.attempt');

        Route::get('/register', [PortalAuthController::class, 'register'])->name('register');
        Route::post('/register', [PortalAuthController::class, 'saveRegistration'])->name('register.store');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile-photo/{user}', [ProfileController::class, 'photo'])
        ->whereNumber('user')
        ->name('profile.photo');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('portal')->name('portal.')->middleware('role:alumni')->group(function () {
        Route::get('/verify-otp', [PortalOtpController::class, 'create'])->name('otp.create');
        Route::post('/verify-otp', [PortalOtpController::class, 'store'])->name('otp.store');
        Route::post('/verify-otp/resend', [PortalOtpController::class, 'resend'])->name('otp.resend');
    });
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('alumni', AlumniController::class)->except('show');
    Route::delete('/alumni', [AlumniController::class, 'bulkDestroy'])->name('alumni.bulk-destroy');
    Route::post('/alumni/import', [AlumniImportController::class, 'store'])->name('alumni.import');
    Route::post('/alumni/{alumnus}/portal-account', [AlumniController::class, 'createPortalAccount'])
        ->name('alumni.portal-account');
    Route::resource('events', EventController::class)->except('show');
    Route::resource('announcements', AnnouncementController::class)->except('show');
    Route::resource('activities', ActivityController::class)->except('show');
    Route::get('/users/pending', [UserController::class, 'pending'])->name('users.pending');
    Route::patch('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::patch('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');
    Route::resource('users', UserController::class)->only(['index', 'edit', 'update']);
    Route::get('/settings/landing-video', [LandingVideoSettingController::class, 'edit'])
        ->name('admin.settings.landing-video.edit');
    Route::put('/settings/landing-video', [LandingVideoSettingController::class, 'update'])
        ->name('admin.settings.landing-video.update');
    Route::put('/settings/landing-profiles', [LandingVideoSettingController::class, 'updateProfiles'])
        ->name('admin.settings.landing-profiles.update');

    Route::get('/requests', [RecordRequestController::class, 'index'])->name('requests.index');
    Route::patch('/requests/{recordRequest}/status', [RecordRequestController::class, 'updateStatus'])
        ->name('requests.status');
});

Route::middleware(['auth', 'role:alumni', 'portal.otp'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');
    Route::get('/requests', [PortalRequestController::class, 'index'])->name('requests.index');
    Route::post('/requests', [PortalRequestController::class, 'store'])->name('requests.store');
});
