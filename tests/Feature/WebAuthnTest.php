<?php

use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\AuthenticatorSelectionCriteria;
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

it('serializes browser arrays without null values', function (): void {
    $options = PublicKeyCredentialCreationOptions::create(
        rp: PublicKeyCredentialRpEntity::create(name: 'Test App', id: 'localhost'),
        user: PublicKeyCredentialUserEntity::create(
            name: 'test@example.com',
            id: 'user-id-123',
            displayName: 'Test User',
        ),
        challenge: random_bytes(32),
        authenticatorSelection: AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        ),
        excludeCredentials: [],
    );

    $array = WebAuthn::toBrowserArray($options);

    expect($array)->not->toHaveKey('attestation');
    expect($array)->toHaveKey('excludeCredentials', []);
    expect($array['authenticatorSelection'])
        ->not->toHaveKey('authenticatorAttachment')
        ->toHaveKey('residentKey', 'required');
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

it('creates validators without throwing when subdomains are allowed', function (): void {
    config(['passkeys.allow_subdomains' => true]);

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
