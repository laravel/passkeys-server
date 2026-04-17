<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Passkeys\Tests\User;

it('uses the name column for the display name', function (): void {
    $user = User::create([
        'name' => 'Alex Müller',
        'email' => 'alex@example.com',
    ]);

    expect($user->getPasskeyDisplayName())->toBe('Alex Müller');
});

it('uses the email column for the username', function (): void {
    $user = User::create([
        'name' => 'Alex Müller',
        'email' => 'alex@example.com',
    ]);

    expect($user->getPasskeyUsername())->toBe('alex@example.com');
});

it('falls back to email for display name when name is absent', function (): void {
    Schema::create('minimal_users', function (Blueprint $table): void {
        $table->id();
        $table->string('email')->unique();
        $table->timestamps();
    });

    $user = MinimalUser::create(['email' => 'only@example.com']);

    expect($user->getPasskeyDisplayName())->toBe('only@example.com');
    expect($user->getPasskeyUsername())->toBe('only@example.com');
});

it('falls back to the auth identifier when email is absent', function (): void {
    Schema::create('bare_users', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
    });

    $user = BareUser::create();

    expect($user->getPasskeyDisplayName())->toBe((string) $user->id);
    expect($user->getPasskeyUsername())->toBe((string) $user->id);
});

class MinimalUser extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;

    protected $table = 'minimal_users';

    protected $guarded = [];
}

class BareUser extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;

    protected $table = 'bare_users';

    protected $guarded = [];
}
