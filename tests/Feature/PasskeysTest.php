<?php

use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

it('returns the default passkey model', function () {
    expect(Passkeys::passkeyModel())->toBe(Passkey::class);
});

it('can set a custom passkey model', function () {
    Passkeys::usePasskeyModel(CustomPasskey::class);

    expect(Passkeys::passkeyModel())->toBe(CustomPasskey::class);

    // Reset to default
    Passkeys::usePasskeyModel(Passkey::class);
});

it('returns the configured timeout', function () {
    config(['passkeys.timeout' => 30000]);

    expect(Passkeys::timeout())->toBe(30000);
});

class CustomPasskey extends Passkey
{
    //
}
