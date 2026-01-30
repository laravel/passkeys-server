<?php

use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;

it('returns verification options', function () {
    $this->getJson('/passkeys/options')
        ->assertOk()
        ->assertJsonStructure([
            'options' => [
                'challenge',
                'timeout',
                'rpId',
            ],
        ]);
});

it('stores verification options in session', function () {
    $this->getJson('/passkeys/options')->assertOk();

    expect(session('passkey.verification_options'))->not->toBeNull();
});

it('returns validation error when passkey is invalid', function () {
    $this->mock(VerifyPasskey::class, fn ($mock) => $mock
        ->shouldReceive('__invoke')
        ->andThrow(InvalidPasskeyException::make())
    );

    $this->withSession(['passkey.verification_options' => 'serialized-options'])
        ->postJson('/passkeys/verify', [
            'credential' => [
                'id' => 'dGVzdC1pZA',
                'rawId' => 'dGVzdC1pZA',
                'type' => 'public-key',
                'response' => ['test' => 'response'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credential']);

    $this->assertGuest();
});

it('returns validation error when verification throws unexpected exception', function () {
    $this->mock(VerifyPasskey::class, fn ($mock) => $mock
        ->shouldReceive('__invoke')
        ->andThrow(new RuntimeException('Unexpected'))
    );

    $this->withSession(['passkey.verification_options' => 'serialized-options'])
        ->postJson('/passkeys/verify', [
            'credential' => [
                'id' => 'dGVzdC1pZA',
                'rawId' => 'dGVzdC1pZA',
                'type' => 'public-key',
                'response' => ['test' => 'response'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credential']);

    $this->assertGuest();
});

it('returns validation error when session has expired', function () {
    $this->postJson('/passkeys/verify', [
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
