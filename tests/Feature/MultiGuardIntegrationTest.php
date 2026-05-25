<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Tests\Stubs\AdminStubUser;
use Laravel\Passkeys\Tests\Stubs\WebStubUser;

beforeEach(function (): void {
    config()->set('passkeys.guards', [
        'web' => [
            'user_model' => WebStubUser::class,
            'connection' => null,
            'redirect' => '/',
            'middleware' => ['web'],
            'management_middleware' => [],
        ],
        'admin' => [
            'user_model' => AdminStubUser::class,
            'connection' => null,
            'redirect' => '/admin',
            'middleware' => ['web'],
            'management_middleware' => [],
        ],
    ]);

    Schema::create('web_stub_users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('admin_stub_users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('web_stub_users');
    Schema::dropIfExists('admin_stub_users');
});

it('isolates web-user passkeys from admin-user passkeys via the morph type column', function (): void {
    $webUser = new WebStubUser;
    $webUser->name = 'Web Alice';
    $webUser->save();

    $adminUser = new AdminStubUser;
    $adminUser->name = 'Admin Bob';
    $adminUser->save();

    Passkey::create([
        'authenticatable_type' => WebStubUser::class,
        'authenticatable_id' => $webUser->getKey(),
        'name' => 'web-device',
        'credential_id' => 'web-credential-id',
        'credential' => [],
    ]);

    Passkey::create([
        'authenticatable_type' => AdminStubUser::class,
        'authenticatable_id' => $adminUser->getKey(),
        'name' => 'admin-device',
        'credential_id' => 'admin-credential-id',
        'credential' => [],
    ]);

    $webPasskeys = $webUser->passkeys()->get();
    $adminPasskeys = $adminUser->passkeys()->get();

    expect($webPasskeys)->toHaveCount(1);
    expect($webPasskeys->first()->name)->toBe('web-device');

    expect($adminPasskeys)->toHaveCount(1);
    expect($adminPasskeys->first()->name)->toBe('admin-device');
});

it('keeps passkeys isolated when web and admin user ids collide', function (): void {
    $webUser = new WebStubUser;
    $webUser->id = 1;
    $webUser->name = 'Web One';
    $webUser->save();

    $adminUser = new AdminStubUser;
    $adminUser->id = 1;
    $adminUser->name = 'Admin One';
    $adminUser->save();

    // Sanity-check: IDs collide across the two user tables.
    expect($webUser->getKey())->toBe(1);
    expect($adminUser->getKey())->toBe(1);

    Passkey::create([
        'authenticatable_type' => WebStubUser::class,
        'authenticatable_id' => $webUser->getKey(),
        'name' => 'A',
        'credential_id' => 'collide-credential-id',
        'credential' => [],
    ]);

    expect($webUser->passkeys()->where('name', 'A')->exists())->toBeTrue();
    expect($adminUser->passkeys()->get())->toBeEmpty();
    expect(
        $webUser->passkeys()->where('authenticatable_type', AdminStubUser::class)->exists()
    )->toBeFalse();
});

it('threads per-guard config through Passkeys::userModelFor() and redirectFor()', function (): void {
    expect(Passkeys::userModelFor('web'))->toBe(WebStubUser::class);
    expect(Passkeys::userModelFor('admin'))->toBe(AdminStubUser::class);
    expect(Passkeys::redirectFor('web'))->toBe('/');
    expect(Passkeys::redirectFor('admin'))->toBe('/admin');
});
