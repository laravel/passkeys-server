<?php

declare(strict_types=1);

use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Tests\Stubs\AdminStubUser;
use Laravel\Passkeys\Tests\Stubs\WebStubUser;
use Laravel\Passkeys\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('passkeys.guards', [
        'web' => [
            'user_model' => WebStubUser::class,
            'connection' => null,
            'redirect' => '/dashboard',
        ],
        'admin' => [
            'user_model' => AdminStubUser::class,
            'connection' => 'central',
            'redirect' => '/admin',
        ],
    ]);
});

it('resolves the user model for the web guard', function (): void {
    expect(Passkeys::userModelFor('web'))->toBe(WebStubUser::class);
});

it('resolves the user model for the admin guard', function (): void {
    expect(Passkeys::userModelFor('admin'))->toBe(AdminStubUser::class);
});

it('returns null connection for the web guard', function (): void {
    expect(Passkeys::tableConnectionFor('web'))->toBeNull();
});

it('returns the central connection for the admin guard', function (): void {
    expect(Passkeys::tableConnectionFor('admin'))->toBe('central');
});

it('resolves the dashboard redirect for the web guard', function (): void {
    expect(Passkeys::redirectFor('web'))->toBe('/dashboard');
});

it('resolves the admin redirect for the admin guard', function (): void {
    expect(Passkeys::redirectFor('admin'))->toBe('/admin');
});

it('throws when resolving a guard that has no configuration', function (): void {
    Passkeys::userModelFor('nonexistent');
})->throws(RuntimeException::class, "No passkeys configuration for guard 'nonexistent'");
