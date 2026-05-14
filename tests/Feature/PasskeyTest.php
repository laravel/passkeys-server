<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Passkeys\Passkeys;

it('can instantiate the passkey model', function (): void {
    $passkey = new Passkey;

    expect($passkey)->toBeInstanceOf(Passkey::class);
});

it('has the correct fillable attributes', function (): void {
    $passkey = new Passkey;

    expect($passkey->getFillable())->toBe([
        'name',
        'credential_id',
        'credential',
    ]);
});

it('can belong to a user model with a custom primary key', function (): void {
    Schema::create('users_with_custom_primary_keys', function (Blueprint $table): void {
        $table->unsignedBigInteger('user_id')->primary();
    });

    Passkeys::useUserModel(UserWithCustomPrimaryKey::class);

    $user = UserWithCustomPrimaryKey::create([
        'user_id' => 1234,
    ]);

    $passkey = (new Passkey)->forceFill([
        'user_id' => $user->getKey(),
    ]);

    expect($passkey->user)->not->toBeNull();
    expect($passkey->user->is($user))->toBeTrue();
});

class UserWithCustomPrimaryKey extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;

    protected $table = 'users_with_custom_primary_keys';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
