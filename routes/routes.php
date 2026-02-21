<?php

use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

Route::group(['middleware' => config('passkeys.middleware')], function () {
    $middleware = function (string ...$middleware): array {
        $throttle = config('passkeys.throttle');

        return array_values(array_filter([...$middleware, $throttle]));
    };

    Route::get('/passkeys/login/options', [PasskeyLoginController::class, 'index'])
        ->middleware($middleware('guest:'.config('passkeys.guard')))
        ->name('passkey.login-options');

    Route::post('/passkeys/login', [PasskeyLoginController::class, 'store'])
        ->middleware($middleware('guest:'.config('passkeys.guard')))
        ->name('passkey.login');

    Route::middleware('auth:'.config('passkeys.guard'))->group(function () use ($middleware) {
        Route::get('/passkeys/confirm/options', [PasskeyConfirmationController::class, 'index'])
            ->middleware($middleware())
            ->name('passkey.confirm-options');

        Route::post('/passkeys/confirm', [PasskeyConfirmationController::class, 'store'])
            ->middleware($middleware())
            ->name('passkey.confirm');

        Route::get('/user/passkeys/options', [PasskeyRegistrationController::class, 'index'])
            ->middleware($middleware())
            ->name('passkey.registration-options');

        Route::post('/user/passkeys', [PasskeyRegistrationController::class, 'store'])
            ->middleware($middleware())
            ->name('passkey.store');

        Route::delete('/user/passkeys/{passkey}', [PasskeyRegistrationController::class, 'destroy'])
            ->name('passkey.destroy');
    });
});
