<?php

use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Http\Controllers\PasskeyController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;
use Laravel\Passkeys\Http\Controllers\PasskeyVerificationController;

Route::group(['middleware' => config('passkeys.middleware')], function () {
    $middleware = function (string ...$middleware): array {
        $throttle = config('passkeys.throttle');

        return array_values(array_filter([...$middleware, $throttle]));
    };

    Route::get('/passkeys/options', [PasskeyVerificationController::class, 'index'])
        ->middleware($middleware('guest:'.config('passkeys.guard')))
        ->name('passkey.verification-options');

    Route::post('/passkeys/verify', [PasskeyVerificationController::class, 'store'])
        ->middleware($middleware('guest:'.config('passkeys.guard')))
        ->name('passkey.verify');

    Route::middleware('auth:'.config('passkeys.guard'))->group(function () use ($middleware) {
        Route::get('/user/passkeys', [PasskeyController::class, 'index'])
            ->name('passkey.index');

        Route::get('/user/passkeys/options', [PasskeyRegistrationController::class, 'index'])
            ->middleware($middleware())
            ->name('passkey.registration-options');

        Route::post('/user/passkeys', [PasskeyRegistrationController::class, 'store'])
            ->middleware($middleware())
            ->name('passkey.store');

        Route::delete('/user/passkeys/{passkey}', [PasskeyController::class, 'destroy'])
            ->name('passkey.destroy');
    });
});
