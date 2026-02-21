<?php

use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Support\WebAuthn;

it('returns login options', function (): void {
    $this->getJson('/passkeys/login/options')
        ->assertOk()
        ->assertJsonStructure([
            'options' => [
                'challenge',
                'timeout',
                'rpId',
            ],
        ]);
});

it('stores login options in session', function (): void {
    $this->getJson('/passkeys/login/options')->assertOk();

    expect(session('passkey.verification_options'))->not->toBeNull();
});

it('returns validation error when passkey is invalid', function (): void {
    $this->mock(VerifyPasskey::class, fn ($mock) => $mock
        ->shouldReceive('__invoke')
        ->andThrow(InvalidPasskeyException::make())
    );

    $this->withSession(['passkey.verification_options' => WebAuthn::toJson(createRequestOptions())])
        ->postJson('/passkeys/login', [
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

it('returns validation error when verification throws unexpected exception', function (): void {
    $this->mock(VerifyPasskey::class, fn ($mock) => $mock
        ->shouldReceive('__invoke')
        ->andThrow(new RuntimeException('Unexpected'))
    );

    $this->withSession(['passkey.verification_options' => WebAuthn::toJson(createRequestOptions())])
        ->postJson('/passkeys/login', [
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

it('returns validation error when session has expired', function (): void {
    $this->postJson('/passkeys/login', [
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
