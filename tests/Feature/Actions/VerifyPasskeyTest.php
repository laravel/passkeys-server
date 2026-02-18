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

    $assertionResponse = Mockery::mock(AuthenticatorAssertionResponse::class);

    $credential = PublicKeyCredential::create(
        type: 'public-key',
        rawId: $credentialId,
        response: $assertionResponse
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

    $result = $action($credential, $options);

    expect($result)->toBeInstanceOf(Passkey::class);
    expect($result->id)->toBe($passkey->id);
    expect($result->last_used_at)->not->toBeNull();

    $result->refresh();
    expect($result->credential['counter'])->toBe(6);

    Event::assertDispatched(PasskeyVerified::class, fn ($event): bool => $event->user->is($user) && $event->passkey->is($passkey));
});

it('throws exception when response is not an assertion response', function (): void {
    $attestationResponse = Mockery::mock(AuthenticatorAttestationResponse::class);

    $credential = PublicKeyCredential::create(
        type: 'public-key',
        rawId: 'test-raw-id',
        response: $attestationResponse
    );

    $options = createRequestOptions();

    app(VerifyPasskey::class)($credential, $options);
})->throws(InvalidPasskeyException::class, 'Unable to verify passkey');

it('throws exception when passkey is not found', function (): void {
    $assertionResponse = Mockery::mock(AuthenticatorAssertionResponse::class);

    $credential = PublicKeyCredential::create(
        type: 'public-key',
        rawId: random_bytes(16),
        response: $assertionResponse
    );

    $options = createRequestOptions();

    app(VerifyPasskey::class)($credential, $options);
})->throws(InvalidPasskeyException::class, 'Passkey not recognized');
