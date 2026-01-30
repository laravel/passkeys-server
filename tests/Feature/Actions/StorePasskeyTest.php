<?php

use Illuminate\Support\Facades\Event;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Tests\User;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

it('stores a passkey for the user', function () {
    Event::fake();

    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $attestationResponse = Mockery::mock(AuthenticatorAttestationResponse::class);

    $credential = PublicKeyCredential::create(
        type: 'public-key',
        rawId: random_bytes(16),
        response: $attestationResponse
    );

    $options = createRegistrationOptions($user);
    $source = createCredentialSource((string) $user->id);

    $action = Mockery::mock(StorePasskey::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('validate')
        ->once()
        ->andReturn($source)
        ->getMock();

    $passkey = $action($user, 'My MacBook', $credential, $options);

    expect($passkey)->toBeInstanceOf(Passkey::class);
    expect($passkey->name)->toBe('My MacBook');
    expect($passkey->user_id)->toBe($user->id);
    expect($user->passkeys()->count())->toBe(1);

    Event::assertDispatched(PasskeyRegistered::class, function ($event) use ($user, $passkey) {
        return $event->user->is($user) && $event->passkey->is($passkey);
    });
});

it('throws exception when credential is already registered', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $credentialId = random_bytes(16);

    $user->passkeys()->create([
        'name' => 'Existing Passkey',
        'credential_id' => Base64UrlSafe::encodeUnpadded($credentialId),
        'credential' => ['test' => 'data'],
    ]);

    $attestationResponse = Mockery::mock(AuthenticatorAttestationResponse::class);

    $credential = PublicKeyCredential::create(
        type: 'public-key',
        rawId: $credentialId,
        response: $attestationResponse
    );

    $options = createRegistrationOptions($user);
    $source = createCredentialSource((string) $user->id, $credentialId);

    $action = Mockery::mock(StorePasskey::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('validate')
        ->once()
        ->andReturn($source)
        ->getMock();

    $action($user, 'Duplicate Passkey', $credential, $options);
})->throws(InvalidPasskeyException::class, 'already registered');

it('throws exception when response is not an attestation response', function () {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $assertionResponse = Mockery::mock(AuthenticatorAssertionResponse::class);

    $credential = PublicKeyCredential::create(
        type: 'public-key',
        rawId: 'test-raw-id',
        response: $assertionResponse
    );

    $options = PublicKeyCredentialCreationOptions::create(
        rp: PublicKeyCredentialRpEntity::create('Test'),
        user: PublicKeyCredentialUserEntity::create('test', '1', 'Test'),
        challenge: random_bytes(32),
    );

    app(StorePasskey::class)($user, 'Test Passkey', $credential, $options);
})->throws(InvalidPasskeyException::class, 'Unable to register passkey');
