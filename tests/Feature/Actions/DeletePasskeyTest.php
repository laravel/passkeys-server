<?php

use Illuminate\Support\Facades\Event;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Tests\User;

it('deletes the passkey', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'Test Passkey',
        'credential_id' => 'dGVzdC1jcmVkZW50aWFsLWlk',
        'credential' => ['publicKey' => 'test'],
    ]);

    app(DeletePasskey::class)($user, $passkey);

    expect(Passkey::find($passkey->id))->toBeNull();
});

it('dispatches passkey deleted event', function (): void {
    Event::fake([PasskeyDeleted::class]);

    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'Test Passkey',
        'credential_id' => 'dGVzdC1jcmVkZW50aWFsLWlk',
        'credential' => ['publicKey' => 'test'],
    ]);

    app(DeletePasskey::class)($user, $passkey);

    Event::assertDispatched(PasskeyDeleted::class, fn ($event): bool => $event->user->is($user) && $event->passkey->is($passkey));
});
