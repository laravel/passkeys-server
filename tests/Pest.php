<?php

use Laravel\Passkeys\Tests\TestCase;
use Laravel\Passkeys\Tests\User;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorData;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

pest()->extend(TestCase::class)->in('Feature');

function createCredentialSource(string $userHandle, ?string $credentialId = null, int $counter = 0): PublicKeyCredentialSource
{
    return PublicKeyCredentialSource::create(
        publicKeyCredentialId: $credentialId ?? random_bytes(16),
        type: 'public-key',
        transports: [],
        attestationType: 'none',
        trustPath: EmptyTrustPath::create(),
        aaguid: Uuid::v4(),
        credentialPublicKey: random_bytes(77),
        userHandle: $userHandle,
        counter: $counter,
    );
}

function createRegistrationOptions(User $user): PublicKeyCredentialCreationOptions
{
    return PublicKeyCredentialCreationOptions::create(
        rp: PublicKeyCredentialRpEntity::create('Test App', 'localhost'),
        user: PublicKeyCredentialUserEntity::create($user->email, (string) $user->id, $user->name),
        challenge: random_bytes(32),
    );
}

function createRequestOptions(): PublicKeyCredentialRequestOptions
{
    return PublicKeyCredentialRequestOptions::create(
        challenge: random_bytes(32),
        rpId: 'localhost',
    );
}

/**
 * Create a structurally valid assertion credential array for HTTP tests.
 *
 * The values are cryptographically meaningless but survive the
 * WebAuthn deserialization chain, so they can be POSTed and
 * deserialized by PasskeyVerificationRequest::passedValidation().
 *
 * @return array<string, mixed>
 */
function createAssertionCredential(): array
{
    $rawId = random_bytes(16);

    $clientDataJson = json_encode([
        'type' => 'webauthn.get',
        'challenge' => Base64UrlSafe::encodeUnpadded(random_bytes(32)),
        'origin' => 'https://localhost',
    ]);

    $rpIdHash = hash('sha256', 'localhost', binary: true);
    $flags = chr(AuthenticatorData::FLAG_UP);
    $authData = $rpIdHash.$flags.pack('N', 0);

    return [
        'id' => Base64UrlSafe::encodeUnpadded($rawId),
        'type' => 'public-key',
        'rawId' => Base64UrlSafe::encodeUnpadded($rawId),
        'response' => [
            'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientDataJson),
            'authenticatorData' => Base64UrlSafe::encodeUnpadded($authData),
            'signature' => Base64UrlSafe::encodeUnpadded(random_bytes(64)),
        ],
    ];
}
