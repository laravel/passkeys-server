<?php

use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Tests\User;

it('returns registration options for authenticated user', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/user/passkeys/options')
        ->assertOk()
        ->assertJsonStructure([
            'options' => [
                'challenge',
                'timeout',
                'rp',
                'user',
            ],
        ]);
});

it('stores registration options in session', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/user/passkeys/options')
        ->assertOk();

    expect(session('passkey.registration_options'))->not->toBeNull();
});

it('requires authentication for registration options', function (): void {
    $this->getJson('/user/passkeys/options')
        ->assertUnauthorized();
});

it('requires authentication to store a passkey', function (): void {
    $this->postJson('/user/passkeys', [
        'name' => 'My Passkey',
        'credential' => [
            'id' => 'test-id',
            'rawId' => 'test-raw-id',
            'type' => 'public-key',
            'response' => ['test' => 'response'],
        ],
    ])
        ->assertUnauthorized();
});

it('returns validation error when passkey is invalid', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->mock(StorePasskey::class, fn ($mock) => $mock
        ->shouldReceive('__invoke')
        ->andThrow(InvalidPasskeyException::make())
    );

    $this->actingAs($user)
        ->withSession(['passkey.registration_options' => 'serialized-options'])
        ->postJson('/user/passkeys', [
            'name' => 'My Passkey',
            'credential' => [
                'id' => 'dGVzdC1pZA',
                'rawId' => 'dGVzdC1pZA',
                'type' => 'public-key',
                'response' => ['test' => 'response'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credential']);
});

it('returns validation error when session has expired', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->postJson('/user/passkeys', [
            'name' => 'My Passkey',
            'credential' => [
                'id' => 'dGVzdC1pZA',
                'rawId' => 'dGVzdC1pZA',
                'type' => 'public-key',
                'response' => ['test' => 'response'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credential']);
});

it('requires authentication to delete a passkey', function (): void {
    $this->deleteJson('/user/passkeys/1')
        ->assertUnauthorized();
});

it('deletes a passkey for the authenticated user', function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'Test Passkey',
        'credential_id' => 'dGVzdC1jcmVkZW50aWFsLWlk',
        'credential' => ['publicKey' => 'test'],
    ]);

    $this->actingAs($user)
        ->deleteJson("/user/passkeys/{$passkey->id}")
        ->assertOk()
        ->assertJson(['status' => 'passkey-deleted']);

    expect(Passkey::find($passkey->id))->toBeNull();
});

it("forbids deleting another user's passkey", function (): void {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $otherUser = User::create([
        'name' => 'Other User',
        'email' => 'other@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'Test Passkey',
        'credential_id' => 'dGVzdC1jcmVkZW50aWFsLWlkLW90aGVy',
        'credential' => ['publicKey' => 'test'],
    ]);

    $this->actingAs($otherUser)
        ->deleteJson("/user/passkeys/{$passkey->id}")
        ->assertForbidden();
});
