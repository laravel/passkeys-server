<?php

use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Tests\User;

afterEach(function (): void {
    Passkeys::authorizeLoginUsing(null);
});

it('returns the default passkey model', function (): void {
    expect(Passkeys::passkeyModel())->toBe(Passkey::class);
});

it('can set a custom passkey model', function (): void {
    Passkeys::usePasskeyModel(CustomPasskey::class);

    expect(Passkeys::passkeyModel())->toBe(CustomPasskey::class);

    // Reset to default
    Passkeys::usePasskeyModel(Passkey::class);
});

it('returns the configured timeout', function (): void {
    config(['passkeys.timeout' => 30000]);

    expect(Passkeys::timeout())->toBe(30000);
});

it('returns the configured allowed origins', function (): void {
    config(['passkeys.allowed_origins' => ['https://example.com', 'https://app.example.com']]);

    expect(Passkeys::allowedOrigins())->toBe(['https://example.com', 'https://app.example.com']);
});

it('filters out empty allowed origin entries', function (): void {
    config(['passkeys.allowed_origins' => ['https://example.com', '', null]]);

    expect(Passkeys::allowedOrigins())->toBe(['https://example.com']);
});

it('throws when no allowed origins are configured', function (): void {
    config(['passkeys.allowed_origins' => []]);

    Passkeys::allowedOrigins();
})->throws(RuntimeException::class, 'At least one passkey allowed origin must be configured.');

it('does not allow subdomains by default', function (): void {
    config()->offsetUnset('passkeys.allow_subdomains');

    expect(Passkeys::allowSubdomains())->toBeFalse();
});

it('allows subdomains when enabled', function (): void {
    config(['passkeys.allow_subdomains' => true]);

    expect(Passkeys::allowSubdomains())->toBeTrue();
});

it('casts a truthy allow subdomains config value to a boolean', function (): void {
    config(['passkeys.allow_subdomains' => '1']);

    expect(Passkeys::allowSubdomains())->toBeTrue();
});

it('supports custom sign-in authorization callbacks', function (): void {
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $passkey = $user->passkeys()->create([
        'name' => 'My Passkey',
        'credential_id' => 'test-credential-id',
        'credential' => [],
    ]);

    Passkeys::authorizeLoginUsing(fn (): bool => false);

    expect(Passkeys::allowsLogin(request(), $passkey))->toBeFalse();
});

class CustomPasskey extends Passkey
{
    //
}
