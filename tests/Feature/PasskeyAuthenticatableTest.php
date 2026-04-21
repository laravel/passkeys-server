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

it('falls back to an opaque label when email is absent', function (): void {
    config(['passkeys.user_handle_secret' => 'test-secret']);

    Schema::create('bare_users', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
    });

    $user = BareUser::create();
    $expected = 'user-'.substr(bin2hex($user->getPasskeyUserHandle()), 0, 10);

    expect($user->getPasskeyDisplayName())->toBe($expected);
    expect($user->getPasskeyUsername())->toBe($expected);
    expect($expected)->not->toContain((string) $user->id);
});

it('returns the same handle across fresh model instances', function (): void {
    config(['passkeys.user_handle_secret' => 'test-secret']);

    $user = User::create([
        'name' => 'Alex Müller',
        'email' => 'alex@example.com',
    ]);
    $handle = $user->getPasskeyUserHandle();

    expect(User::find($user->id)->getPasskeyUserHandle())->toBe($handle);
    expect(strlen($handle))->toBe(32);
});

it('does not change when non-identifying attributes change', function (): void {
    config(['passkeys.user_handle_secret' => 'test-secret']);

    $user = User::create([
        'name' => 'Alex Müller',
        'email' => 'alex@example.com',
    ]);
    $before = $user->getPasskeyUserHandle();

    $user->update(['name' => 'Alexandra', 'email' => 'new@example.com']);

    expect($user->fresh()->getPasskeyUserHandle())->toBe($before);
});

it('changes when the secret rotates', function (): void {
    config(['passkeys.user_handle_secret' => 'secret-a']);

    $user = User::create([
        'name' => 'Alex Müller',
        'email' => 'alex@example.com',
    ]);
    $before = $user->getPasskeyUserHandle();

    config(['passkeys.user_handle_secret' => 'secret-b']);

    expect($user->getPasskeyUserHandle())->not->toBe($before);
});

it('uses distinct user handles for different users', function (): void {
    config(['passkeys.user_handle_secret' => 'test-secret']);

    $one = User::create(['name' => 'One', 'email' => 'one@example.com']);
    $two = User::create(['name' => 'Two', 'email' => 'two@example.com']);

    expect($one->getPasskeyUserHandle())->not->toBe($two->getPasskeyUserHandle());
});

it('includes the model table when deriving user handles', function (): void {
    config(['passkeys.user_handle_secret' => 'test-secret']);

    Schema::create('admin_users', function (Blueprint $table): void {
        $table->id();
        $table->timestamps();
    });

    $user = User::create([
        'name' => 'Alex Müller',
        'email' => 'alex@example.com',
    ]);

    $admin = AdminUser::create();

    expect($user->getPasskeyUserHandle())->not->toBe($admin->getPasskeyUserHandle());
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

class AdminUser extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;

    protected $table = 'admin_users';

    protected $guarded = [];
}
