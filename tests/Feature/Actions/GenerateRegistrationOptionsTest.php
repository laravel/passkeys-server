<?php

use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Tests\User;
use Webauthn\PublicKeyCredentialCreationOptions;

it('generates registration options with user data', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $options = app(GenerateRegistrationOptions::class)($user);

    expect($options)->toBeInstanceOf(PublicKeyCredentialCreationOptions::class);
    expect($options->user->name)->toBe('john@example.com');
    expect($options->user->displayName)->toBe('John Doe');
});

it('excludes existing credentials from registration', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $user->passkeys()->create([
        'name' => 'Test Passkey',
        'credential_id' => 'dGVzdC1jcmVkZW50aWFsLWlk',
        'credential' => ['publicKey' => 'test'],
    ]);

    $options = app(GenerateRegistrationOptions::class)($user);

    expect($options->excludeCredentials)->toHaveCount(1);
});
