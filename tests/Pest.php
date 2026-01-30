<?php

use Laravel\Passkeys\Tests\TestCase;
use Laravel\Passkeys\Tests\User;
use Symfony\Component\Uid\Uuid;
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
