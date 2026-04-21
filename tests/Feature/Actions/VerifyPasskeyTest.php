<?php

use Illuminate\Support\Facades\Event;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Events\PasskeyVerified;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Laravel\Passkeys\Tests\User;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;

it('verifies a passkey and returns it', function (): void {
    Event::fake();

    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $credentialId = random_bytes(16);
    $userHandle = (string) $user->id;

    $source = createCredentialSource($userHandle, $credentialId, counter: 5);

    $passkey = $user->passkeys()->create([
        'name' => 'My MacBook',
        'credential_id' => Base64UrlSafe::encodeUnpadded($credentialId),
        'credential' => json_decode(WebAuthn::toJson($source), true),
    ]);

    $assertion = PublicKeyCredential::create(
        type: 'public-key',
        rawId: $credentialId,
        response: Mockery::mock(AuthenticatorAssertionResponse::class),
    );

    $options = createRequestOptions();

    $updatedSource = createCredentialSource($userHandle, $credentialId, counter: 6);

    $action = Mockery::mock(VerifyPasskey::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('validate')
        ->once()
        ->andReturn($updatedSource)
        ->getMock();

    $result = $action($assertion, $options);

    expect($result)->toBeInstanceOf(Passkey::class);
    expect($result->id)->toBe($passkey->id);
    expect($result->last_used_at)->not->toBeNull();

    $result->refresh();
    expect($result->credential['counter'])->toBe(6);

    Event::assertDispatched(PasskeyVerified::class, fn ($event): bool => $event->user->is($user) && $event->passkey->is($passkey));
});

it('throws exception when response is not an assertion response', function (): void {
    $assertion = PublicKeyCredential::create(
        type: 'public-key',
        rawId: 'test-raw-id',
        response: Mockery::mock(AuthenticatorAttestationResponse::class),
    );

    app(VerifyPasskey::class)($assertion, createRequestOptions());
})->throws(InvalidPasskeyException::class, 'Unable to verify passkey');

it('throws exception when passkey is not found', function (): void {
    $assertion = PublicKeyCredential::create(
        type: 'public-key',
        rawId: random_bytes(16),
        response: Mockery::mock(AuthenticatorAssertionResponse::class),
    );

    app(VerifyPasskey::class)($assertion, createRequestOptions());
})->throws(InvalidPasskeyException::class, 'Passkey not recognized');

it('throws exception when passkey does not belong to expected user', function (): void {
    $owner = User::create([
        'name' => 'Passkey Owner',
        'email' => 'owner@example.com',
    ]);

    $otherUser = User::create([
        'name' => 'Other User',
        'email' => 'other@example.com',
    ]);

    $credentialId = random_bytes(16);
    $userHandle = (string) $owner->id;
    $source = createCredentialSource($userHandle, $credentialId, counter: 1);

    $owner->passkeys()->create([
        'name' => 'My MacBook',
        'credential_id' => Base64UrlSafe::encodeUnpadded($credentialId),
        'credential' => json_decode(WebAuthn::toJson($source), true),
    ]);

    $assertion = PublicKeyCredential::create(
        type: 'public-key',
        rawId: $credentialId,
        response: Mockery::mock(AuthenticatorAssertionResponse::class),
    );

    $action = Mockery::mock(VerifyPasskey::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('validate')
        ->never()
        ->getMock();

    $action($assertion, createRequestOptions(), $otherUser);
})->throws(InvalidPasskeyException::class, 'Passkey not recognized');

it('verifies an existing passkey after user handle secret rotation', function (): void {
    config()->set('passkeys.allowed_origins', ['https://localhost']);
    config()->set('passkeys.relying_party_id', 'localhost');
    config()->set('passkeys.user_handle_secret', 'initial-user-handle-secret');

    $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $credentialId = random_bytes(16);
    $initialUserHandle = $user->getPasskeyUserHandle();

    $passkey = $user->passkeys()->create([
        'name' => 'My MacBook',
        'credential_id' => Base64UrlSafe::encodeUnpadded($credentialId),
        'credential' => json_decode(WebAuthn::toJson(createCredentialSource($initialUserHandle, $credentialId)), true),
    ]);

    config()->set('passkeys.user_handle_secret', 'rotated-user-handle-secret');

    $options = createRequestOptions();
    $assertion = PublicKeyCredential::create(
        type: 'public-key',
        rawId: $credentialId,
        response: createSignedAssertionResponse($options->challenge, 'https://localhost', signCount: 6, rpId: 'localhost'),
    );

    $result = app(VerifyPasskey::class)($assertion, $options, $user);

    expect($result->id)->toBe($passkey->id);
    expect(Base64UrlSafe::decodeNoPadding($result->refresh()->credential['userHandle']))->toBe($initialUserHandle);
});
