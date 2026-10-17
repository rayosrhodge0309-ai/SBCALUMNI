<?php

use App\Http\Middleware\EnsurePortalOtpVerified;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserRole::class,
            'portal.otp' => EnsurePortalOtpVerified::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            return route('portal.login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            return $user && $user->role === 'alumni'
                ? route('portal.dashboard')
                : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419
                || ! ($exception->getPrevious() instanceof TokenMismatchException)
                || ! $request->routeIs('portal.login.attempt')
                || $request->expectsJson()) {
                return null;
            }

            // Reject the expired submission. Never replay or flash login passwords.
            $email = $request->input('email');

            return redirect()->route('portal.login', [], 303)
                ->withInput(is_string($email) ? ['email' => mb_substr($email, 0, 255)] : [])
                ->with('login_expired', 'Your login session expired. Please enter your password and sign in again.')
                ->header('Cache-Control', 'no-store, private');
        });
    })->create();

if (($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'testing') {
    $app->useStoragePath(dirname(__DIR__).'/tmp/storage');

    foreach ([
        $app->storagePath('logs'),
        $app->storagePath('framework/testing/disks/public/profile-photos'),
    ] as $directory) {
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create the directory [%s].', $directory));
        }
    }
}

return $app;
