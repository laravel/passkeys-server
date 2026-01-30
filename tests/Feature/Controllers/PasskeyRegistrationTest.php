<?php

use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Tests\User;

it('returns registration options for authenticated user', function () {
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

it('stores registration options in session', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/user/passkeys/options')
        ->assertOk();

    expect(session('passkey.registration_options'))->not->toBeNull();
});

it('requires authentication for registration options', function () {
    $this->getJson('/user/passkeys/options')
        ->assertUnauthorized();
});

it('requires authentication to store a passkey', function () {
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

it('returns validation error when passkey is invalid', function () {
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

it('returns validation error when session has expired', function () {
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
