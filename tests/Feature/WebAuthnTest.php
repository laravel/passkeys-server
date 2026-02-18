<?php

use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

beforeEach(function (): void {
    WebAuthn::flush();
});

it('serializes and deserializes registration options', function (): void {
    $options = PublicKeyCredentialCreationOptions::create(
        rp: PublicKeyCredentialRpEntity::create(name: 'Test App', id: 'localhost'),
        user: PublicKeyCredentialUserEntity::create(
            name: 'test@example.com',
            id: 'user-id-123',
            displayName: 'Test User',
        ),
        challenge: random_bytes(32),
    );

    $json = WebAuthn::toJson($options);
    $restored = WebAuthn::fromJson($json, PublicKeyCredentialCreationOptions::class);

    expect($restored->rp->name)->toBe('Test App');
    expect($restored->rp->id)->toBe('localhost');
    expect($restored->user->name)->toBe('test@example.com');
    expect($restored->user->displayName)->toBe('Test User');
});

it('serializes and deserializes verification options', function (): void {
    $options = PublicKeyCredentialRequestOptions::create(
        challenge: random_bytes(32),
        rpId: 'localhost',
    );

    $json = WebAuthn::toJson($options);
    $restored = WebAuthn::fromJson($json, PublicKeyCredentialRequestOptions::class);

    expect($restored->rpId)->toBe('localhost');
    expect($restored->challenge)->toBe($options->challenge);
});

it('creates validators without throwing', function (): void {
    // These should create functional validators
    // without any configuration errors
    $attestation = WebAuthn::attestationValidator();
    $assertion = WebAuthn::assertionValidator();

    expect($attestation)->not->toBeNull();
    expect($assertion)->not->toBeNull();
});

it('flushes cached instances', function (): void {
    // Call twice to populate cache
    WebAuthn::toJson(['test' => 'data']);
    WebAuthn::toJson(['test' => 'data']);

    // Flush and verify it still works (new instance created)
    WebAuthn::flush();

    $json = WebAuthn::toJson(['test' => 'data']);

    expect($json)->toContain('test');
});
