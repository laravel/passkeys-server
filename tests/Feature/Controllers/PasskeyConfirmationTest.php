<?php

use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Support\WebAuthn;
use Laravel\Passkeys\Tests\User;

it('returns confirmation options for authenticated user', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/passkeys/confirm/options')
        ->assertOk()
        ->assertJsonStructure([
            'options' => [
                'challenge',
                'timeout',
                'rpId',
                'allowCredentials',
            ],
        ]);
});

it('stores confirmation options in session', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/passkeys/confirm/options')
        ->assertOk();

    expect(session('passkey.verification_options'))->not->toBeNull();
});

it('requires authentication for confirmation options', function (): void {
    $this->getJson('/passkeys/confirm/options')
        ->assertUnauthorized();
});

it('requires authentication to confirm a passkey', function (): void {
    $this->postJson('/passkeys/confirm', [
        'credential' => [
            'id' => 'test-id',
            'rawId' => 'test-raw-id',
            'type' => 'public-key',
            'response' => ['test' => 'response'],
        ],
    ])
        ->assertUnauthorized();
});

it('returns validation error when passkey confirmation is invalid', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->mock(VerifyPasskey::class, fn ($mock) => $mock
        ->shouldReceive('__invoke')
        ->andThrow(InvalidPasskeyException::make())
    );

    $this->actingAs($user)
        ->withSession(['passkey.verification_options' => WebAuthn::toJson(createRequestOptions())])
        ->postJson('/passkeys/confirm', [
            'credential' => [
                'id' => 'dGVzdC1pZA',
                'rawId' => 'dGVzdC1pZA',
                'type' => 'public-key',
                'response' => ['test' => 'response'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credential']);

    $this->assertAuthenticatedAs($user);
});

it('marks the password as confirmed when passkey confirmation succeeds', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'Test Passkey',
        'credential_id' => 'test-credential-id',
        'credential' => [],
    ]);

    $this->mock(VerifyPasskey::class)
        ->shouldReceive('__invoke')
        ->once()
        ->withArgs(fn ($credential, $options, $expectedUser): bool => $expectedUser instanceof User && $expectedUser->is($user))
        ->andReturn($passkey);

    $this->actingAs($user)
        ->withSession(['passkey.verification_options' => WebAuthn::toJson(createRequestOptions())])
        ->postJson('/passkeys/confirm', [
            'credential' => createAssertionCredential(),
        ])
        ->assertOk()
        ->assertJsonStructure(['redirect'])
        ->assertJsonMissing(['confirmed']);

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});
