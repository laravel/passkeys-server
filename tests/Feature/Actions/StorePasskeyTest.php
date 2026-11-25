<?php

use Illuminate\Support\Facades\Event;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Tests\User;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AttestedCredentialData;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorData;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

it('stores a passkey for the user', function (): void {
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

    Event::assertDispatched(PasskeyRegistered::class, fn ($event): bool => $event->user->is($user) && $event->passkey->is($passkey));
});

it('throws exception when credential is already registered', function (): void {
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
})->throws(InvalidPasskeyException::class, 'Unable to register this passkey.');

it('throws exception when response is not an attestation response', function (): void {
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

it('rejects an attestation created for a different challenge', function (): void {
    config()->set('passkeys.allowed_origins', ['https://localhost']);
    config()->set('passkeys.relying_party_id', 'localhost');

    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Opening the registration dialog in a second tab replaces the options in
    // the session, so the first tab's attestation answers a stale challenge.
    $options = createRegistrationOptions($user);
    $staleOptions = createRegistrationOptions($user);

    $clientDataPayload = [
        'type' => 'webauthn.create',
        'challenge' => Base64UrlSafe::encodeUnpadded($staleOptions->challenge),
        'origin' => 'https://localhost',
    ];
    $clientDataRaw = json_encode($clientDataPayload);

    $credentialId = random_bytes(16);
    $rpIdHash = hash('sha256', 'localhost', binary: true);
    $flags = chr(AuthenticatorData::FLAG_UP | AuthenticatorData::FLAG_AT);

    $authenticatorData = AuthenticatorData::create(
        authData: $rpIdHash.$flags.pack('N', 0),
        rpIdHash: $rpIdHash,
        flags: $flags,
        signCount: 0,
        attestedCredentialData: AttestedCredentialData::create(
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            $credentialId,
            fixtureEcKeypair()['cose_public_key'],
        ),
    );

    $credential = PublicKeyCredential::create(
        type: 'public-key',
        rawId: $credentialId,
        response: AuthenticatorAttestationResponse::create(
            clientDataJSON: CollectedClientData::create($clientDataRaw, $clientDataPayload),
            attestationObject: AttestationObject::create(
                rawAttestationObject: '',
                attStmt: AttestationStatement::createNone('none', [], EmptyTrustPath::create()),
                authData: $authenticatorData,
            ),
        ),
    );

    try {
        app(StorePasskey::class)($user, 'My MacBook', $credential, $options);
    } finally {
        expect($user->passkeys()->count())->toBe(0);
    }
})->throws(InvalidPasskeyException::class, 'Unable to register passkey');
