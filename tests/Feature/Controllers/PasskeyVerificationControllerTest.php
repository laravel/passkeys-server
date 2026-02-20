<?php

use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use Laravel\Passkeys\Tests\User;

afterEach(function (): void {
    Passkeys::authorizeSignInUsing(null);
});

it('does not log in when custom sign-in authorization callback returns false', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'My MacBook',
        'credential_id' => 'test-credential-id',
        'credential' => [],
    ]);

    $this->mock(VerifyPasskey::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn($passkey);

    Passkeys::authorizeSignInUsing(fn (): bool => false);

    $this->withSession(['passkey.verification_options' => WebAuthn::toJson(createRequestOptions())])
        ->postJson('/passkeys/verify', ['credential' => createAssertionCredential()])
        ->assertUnprocessable();

    $this->assertGuest();
});

it('logs in when custom sign-in authorization callback returns true', function (): void {
    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'My iPhone',
        'credential_id' => 'test-credential-id-2',
        'credential' => [],
    ]);

    $this->mock(VerifyPasskey::class)
        ->shouldReceive('__invoke')
        ->once()
        ->andReturn($passkey);

    Passkeys::authorizeSignInUsing(fn (): bool => true);

    $this->withSession(['passkey.verification_options' => WebAuthn::toJson(createRequestOptions())])
        ->postJson('/passkeys/verify', [
            'credential' => createAssertionCredential(),
            'remember' => true,
        ])
        ->assertOk()
        ->assertJson(['verified' => true]);

    $this->assertAuthenticatedAs($user);
});
