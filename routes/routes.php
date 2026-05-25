<?php

use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

/*
|--------------------------------------------------------------------------
| Legacy Single-Guard Routes
|--------------------------------------------------------------------------
|
| These routes are auto-registered (when Passkeys::shouldRegisterRoutes()
| is true) for single-guard applications. They bind to the application's
| default auth guard so that out-of-the-box installations work without
| any bootstrap code. Multi-guard apps should disable auto-registration
| and call Route::passkeys($guard, $opts) per guard instead.
*/

$guard = (string) config('auth.defaults.guard', 'web');
$guardMiddleware = (array) config("passkeys.guards.{$guard}.middleware", ['web']);
$managementMiddleware = array_values(array_filter(
    (array) config("passkeys.guards.{$guard}.management_middleware", ['password.confirm'])
));

Route::group(['middleware' => $guardMiddleware], function () use ($guard, $managementMiddleware) {
    $middleware = function (string ...$middleware): array {
        $throttle = config('passkeys.throttle');

        return array_values(array_filter([...$middleware, $throttle]));
    };

    Route::get('/passkeys/login/options', [PasskeyLoginController::class, 'index'])
        ->middleware($middleware('guest:'.$guard))
        ->defaults('passkey_guard', $guard)
        ->name('passkey.login-options');

    Route::post('/passkeys/login', [PasskeyLoginController::class, 'store'])
        ->middleware($middleware('guest:'.$guard))
        ->defaults('passkey_guard', $guard)
        ->name('passkey.login');

    Route::middleware('auth:'.$guard)->group(function () use ($guard, $managementMiddleware, $middleware) {
        Route::get('/passkeys/confirm/options', [PasskeyConfirmationController::class, 'index'])
            ->middleware($middleware())
            ->defaults('passkey_guard', $guard)
            ->name('passkey.confirm-options');

        Route::post('/passkeys/confirm', [PasskeyConfirmationController::class, 'store'])
            ->middleware($middleware())
            ->defaults('passkey_guard', $guard)
            ->name('passkey.confirm');

        Route::get('/user/passkeys/options', [PasskeyRegistrationController::class, 'index'])
            ->middleware($middleware(...$managementMiddleware))
            ->defaults('passkey_guard', $guard)
            ->name('passkey.registration-options');

        Route::post('/user/passkeys', [PasskeyRegistrationController::class, 'store'])
            ->middleware($middleware(...$managementMiddleware))
            ->defaults('passkey_guard', $guard)
            ->name('passkey.store');

        Route::delete('/user/passkeys/{passkey}', [PasskeyRegistrationController::class, 'destroy'])
            ->middleware($managementMiddleware)
            ->defaults('passkey_guard', $guard)
            ->name('passkey.destroy');
    });
});
