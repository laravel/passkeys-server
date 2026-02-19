<?php

use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Tests\User;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredentialRequestOptions;

it('generates verification options', function (): void {
    $options = app(GenerateVerificationOptions::class)();

    expect($options)->toBeInstanceOf(PublicKeyCredentialRequestOptions::class);
});

it('uses empty allow credentials for discoverable flow', function (): void {
    $options = app(GenerateVerificationOptions::class)();

    expect($options->allowCredentials)->toBe([]);
});

it('uses only the authenticated user passkeys for allow credentials', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $credentialIdOne = random_bytes(16);
    $credentialIdTwo = random_bytes(16);

    $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => Base64UrlSafe::encodeUnpadded($credentialIdOne),
        'credential' => [],
    ]);

    $user->passkeys()->create([
        'name' => 'iPhone',
        'credential_id' => Base64UrlSafe::encodeUnpadded($credentialIdTwo),
        'credential' => [],
    ]);

    $options = app(GenerateVerificationOptions::class)($user);

    expect($options->allowCredentials)->toHaveCount(2);
    expect(
        collect($options->allowCredentials)->map(
            fn ($credential): string => Base64UrlSafe::encodeUnpadded($credential->id)
        )->sort()->values()->all()
    )->toBe(
        collect([
            Base64UrlSafe::encodeUnpadded($credentialIdOne),
            Base64UrlSafe::encodeUnpadded($credentialIdTwo),
        ])->sort()->values()->all()
    );
});
