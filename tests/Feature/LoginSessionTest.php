<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Laravel normally skips CSRF in tests; exercise the production check here.
    $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
    {
        protected function runningUnitTests()
        {
            return false;
        }
    });
});

test('login forms are not cached and contain the current session token', function () {
    $response = $this->withSession(['_token' => 'current-login-token'])
        ->get(route('portal.login'))->assertOk()
        ->assertSee('name="_token" value="current-login-token"', false);

    expect($response->headers->hasCacheControlDirective('no-store'))->toBeTrue();
});

test('an expired browser login returns to a fresh form without keeping the password', function (?string $submittedToken) {
    $user = User::factory()->create([
        'email' => 'login.test@gmail.com',
        'password' => 'password123',
        'role' => 'admin',
    ]);

    $this->withSession(['_token' => 'current-login-token'])
        ->withHeader('Referer', 'https://untrusted.example/login')
        ->post(route('portal.login.attempt'), [
            '_token' => $submittedToken,
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(303)->assertRedirect(route('portal.login'))
        ->assertSessionHas('login_expired')
        ->assertSessionHas('_old_input', ['email' => $user->email]);
    $this->assertGuest();

    $this->get(route('portal.login'))->assertOk()
        ->assertSee('Your login session expired.')
        ->assertSee('value="login.test@gmail.com"', false)
        ->assertSee('name="_token" value="current-login-token"', false)
        ->assertDontSee('password123');

    $this->post(route('portal.login.attempt'), [
        '_token' => 'current-login-token',
        'email' => $user->email,
        'password' => 'password123',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
})->with(['stale token' => ['outdated-login-token'], 'missing token' => [null]]);

test('non string email input does not break expired login recovery', function () {
    $this->withSession(['_token' => 'current-login-token'])
        ->post(route('portal.login.attempt'), ['email' => ['unexpected'], 'password' => 'secret'])
        ->assertRedirect(route('portal.login'))
        ->assertSessionHas('_old_input', []);

    $this->get(route('portal.login'))->assertOk()->assertDontSee('secret');
});

test('login can recover after the original session has been lost', function () {
    $user = User::factory()->create([
        'email' => 'login.test@gmail.com',
        'password' => 'password123',
        'role' => 'admin',
    ]);
    $this->withSession(['_token' => 'expired-session-token'])->get(route('portal.login'))->assertOk();
    $this->app['session']->invalidate();

    $this->post(route('portal.login.attempt'), [
        '_token' => 'expired-session-token',
        'email' => $user->email,
        'password' => 'password123',
    ])->assertStatus(303)->assertRedirect(route('portal.login'));
    $this->assertGuest();

    $response = $this->get(route('portal.login'))->assertOk()->assertSee('Your login session expired.');
    preg_match('/name="_token" value="([^"]+)"/', $response->getContent(), $matches);
    $freshToken = $matches[1] ?? '';
    expect($freshToken)->not->toBe('')->not->toBe('expired-session-token');

    $this->post(route('portal.login.attempt'), [
        '_token' => $freshToken,
        'email' => $user->email,
        'password' => 'password123',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('a valid login token still requires the correct password', function (string $role, string $destination) {
    $user = User::factory()->create([
        'email' => 'login.test@gmail.com',
        'password' => 'password123',
        'role' => $role,
        'account_status' => 'approved',
    ]);

    $this->withSession(['_token' => 'current-login-token'])
        ->from(route('portal.login'))
        ->post(route('portal.login.attempt'), [
            '_token' => 'current-login-token',
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect(route('portal.login'))->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(route('portal.login.attempt'), [
        '_token' => 'current-login-token',
        'email' => $user->email,
        'password' => 'password123',
    ])->assertSessionHasNoErrors()->assertRedirect(route($destination));
    $this->assertAuthenticatedAs($user);
})->with([
    'administrator' => ['admin', 'dashboard'],
    'alumni' => ['alumni', 'portal.otp.create'],
]);

test('JSON login requests with invalid tokens still return 419', function () {
    $this->withSession(['_token' => 'current-login-token'])
        ->postJson(route('portal.login.attempt'), [
            '_token' => 'outdated-login-token',
            'email' => 'login.test@gmail.com',
            'password' => 'secret',
        ])->assertStatus(419)->assertSessionMissing('_old_input.password');
    $this->assertGuest();
});

test('expired tokens on other actions are still rejected', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user)->withSession(['_token' => 'current-login-token'])
        ->post(route('logout'), ['_token' => 'outdated-login-token'])->assertStatus(419);
    $this->assertAuthenticatedAs($user);
});
