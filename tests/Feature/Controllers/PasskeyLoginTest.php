<?php

use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Support\WebAuthn;
use Laravel\Passkeys\Tests\User;

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

it('returns login options to an already authenticated user', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $this->actingAs($user)
        ->getJson('/passkeys/login/options')
        ->assertOk()
        ->assertJsonStructure([
            'options' => [
                'challenge',
                'rpId',
            ],
        ]);
});

it('skips the ceremony and answers an already authenticated verification request with the login response', function (): void {
    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    // The credential is deliberately invalid: reaching the controller would
    // answer 422 instead of the login response.
    $this->actingAs($user)
        ->postJson('/passkeys/login', [
            'credential' => ['id' => 'dGVzdC1pZA'],
        ])
        ->assertOk()
        ->assertExactJson(['redirect' => url(config('passkeys.redirect'))]);
});

it('redirects an already authenticated verification request that does not expect JSON', function (): void {
    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $this->actingAs($user)
        ->post('/passkeys/login')
        ->assertRedirect(config('passkeys.redirect'));
});
