<?php

use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Tests\User;

afterEach(function (): void {
    Passkeys::authorizeSignInUsing(null);
});

it('returns the default passkey model', function (): void {
    expect(Passkeys::passkeyModel())->toBe(Passkey::class);
});

it('can set a custom passkey model', function (): void {
    Passkeys::usePasskeyModel(CustomPasskey::class);

    expect(Passkeys::passkeyModel())->toBe(CustomPasskey::class);

    // Reset to default
    Passkeys::usePasskeyModel(Passkey::class);
});

it('returns the configured timeout', function (): void {
    config(['passkeys.timeout' => 30000]);

    expect(Passkeys::timeout())->toBe(30000);
});

it('supports custom sign-in authorization callbacks', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'My Passkey',
        'credential_id' => 'test-credential-id',
        'credential' => [],
    ]);

    Passkeys::authorizeSignInUsing(fn (): bool => false);

    expect(Passkeys::allowsSignIn(request(), $passkey))->toBeFalse();
});

class CustomPasskey extends Passkey
{
    //
}
