<?php

use Laravel\Passkeys\Tests\User;
use ParagonIE\ConstantTime\Base64UrlSafe;

it('generates and persists a random handle on first access', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($user->passkey_user_handle)->toBeNull();

    $handle = $user->getPasskeyUserHandle();

    expect($handle)->toBeString();
    expect(strlen($handle))->toBe(32);
    expect($user->fresh()->passkey_user_handle)->toBe(Base64UrlSafe::encodeUnpadded($handle));
});

it('reuses the same handle on subsequent calls', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($user->getPasskeyUserHandle())->toBe($user->getPasskeyUserHandle());
});

it('issues different handles to different users', function (): void {
    $one = User::create(['name' => 'One', 'email' => 'one@example.com']);
    $two = User::create(['name' => 'Two', 'email' => 'two@example.com']);

    expect($one->getPasskeyUserHandle())->not->toBe($two->getPasskeyUserHandle());
});
