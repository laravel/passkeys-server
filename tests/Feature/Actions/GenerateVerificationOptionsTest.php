<?php

use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

it('generates verification options', function () {
    $options = app(GenerateVerificationOptions::class)();

    expect($options)->toBeInstanceOf(PublicKeyCredentialRequestOptions::class);
});

it('uses empty allow credentials for discoverable flow', function () {
    $options = app(GenerateVerificationOptions::class)();

    expect($options->allowCredentials)->toBe([]);
});
