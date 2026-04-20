<?php

namespace Laravel\Passkeys\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\PasskeysServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [PasskeysServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('passkeys.relying_party_id', 'localhost');
        $app['config']->set('auth.providers.users.model', User::class);

        Passkeys::useUserModel(User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('passkeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Passkeys::userModel(), 'user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }
}

class User extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;

    protected $guarded = [];
}
