<?php

use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Tests\User;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;

it('generates registration options with user data', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $options = app(GenerateRegistrationOptions::class)($user);

    expect($options)->toBeInstanceOf(PublicKeyCredentialCreationOptions::class);
    expect($options->user->name)->toBe('john@example.com');
    expect($options->user->displayName)->toBe('John Doe');
});

it('excludes existing credentials from registration', function (): void {
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

it('allows overriding authenticator selection with a custom action binding', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    app()->bind(GenerateRegistrationOptions::class, fn () => new class extends GenerateRegistrationOptions
    {
        public function authenticatorSelection(): AuthenticatorSelectionCriteria
        {
            return AuthenticatorSelectionCriteria::create(
                authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            );
        }
    });

    $options = app(GenerateRegistrationOptions::class)($user);

    expect($options->authenticatorSelection->authenticatorAttachment)
        ->toBe(AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM);
});
