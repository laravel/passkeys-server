<?php

use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

it('generates verification options', function (): void {
    $options = app(GenerateVerificationOptions::class)();

    expect($options)->toBeInstanceOf(PublicKeyCredentialRequestOptions::class);
});

it('uses empty allow credentials for discoverable flow', function (): void {
    $options = app(GenerateVerificationOptions::class)();

    expect($options->allowCredentials)->toBe([]);
});
