<?php

use Laravel\Passkeys\Passkey;

it('can instantiate the passkey model', function (): void {
    $passkey = new Passkey;

    expect($passkey)->toBeInstanceOf(Passkey::class);
});

it('has the correct fillable attributes', function (): void {
    $passkey = new Passkey;

    expect($passkey->getFillable())->toBe([
        'name',
        'credential_id',
        'credential',
    ]);
});
