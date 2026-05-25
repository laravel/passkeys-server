<?php

use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

/**
 * Reflection-based proof that every controller action that calls a Passkeys
 * action reads the guard from the route default (set by the Route::passkeys
 * macro as ->defaults('passkey_guard', $guard)) rather than from config or a
 * hard-coded value.
 *
 * If any of these assertions fail it means a controller method is invoking
 * an action without first sourcing the guard from the matched route's
 * defaults, which would silently break multi-guard installs.
 */
dataset('guarded controller methods', [
    'PasskeyLoginController@index' => [PasskeyLoginController::class, 'index'],
    'PasskeyLoginController@store' => [PasskeyLoginController::class, 'store'],
    'PasskeyRegistrationController@index' => [PasskeyRegistrationController::class, 'index'],
    'PasskeyRegistrationController@store' => [PasskeyRegistrationController::class, 'store'],
    'PasskeyRegistrationController@destroy' => [PasskeyRegistrationController::class, 'destroy'],
]);

it('reads passkey_guard from the matched route defaults', function (string $class, string $method): void {
    $reflection = new ReflectionMethod($class, $method);
    $source = file_get_contents($reflection->getFileName());
    expect($source)->not->toBeFalse();

    $lines = explode("\n", $source);
    $start = $reflection->getStartLine() - 1;
    $end = $reflection->getEndLine();
    $body = implode("\n", array_slice($lines, $start, $end - $start));

    expect($body)->toContain("'passkey_guard'");
})->with('guarded controller methods');
