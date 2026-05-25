<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Tests\Stubs\AdminStubUser;
use Laravel\Passkeys\Tests\Stubs\WebStubUser;

beforeEach(function (): void {
    // Skip the package's legacy single-guard route group; this test exercises the new macro.
    Passkeys::ignoreRoutes();

    config()->set('passkeys.guards', [
        'web' => [
            'user_model' => WebStubUser::class,
            'connection' => null,
            'redirect' => '/',
            'middleware' => ['web'],
            'management_middleware' => ['password.confirm'],
        ],
        'admin' => [
            'user_model' => AdminStubUser::class,
            'connection' => 'central',
            'redirect' => '/admin',
            'middleware' => ['web', 'admin'],
            'management_middleware' => ['password.confirm'],
        ],
    ]);

    Route::passkeys('web', ['prefix' => '/passkeys']);
    Route::passkeys('admin', ['prefix' => '/admin/passkeys']);

    // Name lookups are populated when add() is called, but ->name() chains run after that;
    // refresh so getByName() sees the chained names.
    Route::getRoutes()->refreshNameLookups();
});

it('registers per-guard named routes for the web guard', function (): void {
    $routes = Route::getRoutes();

    expect($routes->getByName('passkeys.web.register.options'))->not->toBeNull();
    expect($routes->getByName('passkeys.web.register'))->not->toBeNull();
    expect($routes->getByName('passkeys.web.login.options'))->not->toBeNull();
    expect($routes->getByName('passkeys.web.login'))->not->toBeNull();
    expect($routes->getByName('passkeys.web.destroy'))->not->toBeNull();
});

it('registers per-guard named routes for the admin guard', function (): void {
    $routes = Route::getRoutes();

    expect($routes->getByName('passkeys.admin.register.options'))->not->toBeNull();
    expect($routes->getByName('passkeys.admin.register'))->not->toBeNull();
    expect($routes->getByName('passkeys.admin.login.options'))->not->toBeNull();
    expect($routes->getByName('passkeys.admin.login'))->not->toBeNull();
    expect($routes->getByName('passkeys.admin.destroy'))->not->toBeNull();
});

it('applies the configured prefix to each guard group', function (): void {
    $routes = Route::getRoutes();

    expect($routes->getByName('passkeys.web.login')->uri())->toBe('passkeys/login');
    expect($routes->getByName('passkeys.admin.login')->uri())->toBe('admin/passkeys/login');
});

it('sets the passkey_guard route default so resolvers can read it', function (): void {
    $routes = Route::getRoutes();

    expect($routes->getByName('passkeys.web.login')->defaults['passkey_guard'] ?? null)->toBe('web');
    expect($routes->getByName('passkeys.admin.login')->defaults['passkey_guard'] ?? null)->toBe('admin');
});

it('attaches the per-guard middleware stack to login routes', function (): void {
    $routes = Route::getRoutes();

    $webLogin = $routes->getByName('passkeys.web.login');
    $adminLogin = $routes->getByName('passkeys.admin.login');

    expect($webLogin->gatherMiddleware())->toContain('web');
    expect($adminLogin->gatherMiddleware())->toContain('web')->toContain('admin');
});

it('applies the management middleware to register + destroy routes', function (): void {
    $routes = Route::getRoutes();

    expect($routes->getByName('passkeys.web.register')->gatherMiddleware())->toContain('password.confirm');
    expect($routes->getByName('passkeys.web.destroy')->gatherMiddleware())->toContain('password.confirm');
    expect($routes->getByName('passkeys.admin.register')->gatherMiddleware())->toContain('password.confirm');
});

it('does not apply management middleware to login routes', function (): void {
    $routes = Route::getRoutes();

    expect($routes->getByName('passkeys.web.login')->gatherMiddleware())->not->toContain('password.confirm');
    expect($routes->getByName('passkeys.web.login.options')->gatherMiddleware())->not->toContain('password.confirm');
});
