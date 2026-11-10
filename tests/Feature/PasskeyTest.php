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

it('only appends the authenticator label by default', function (): void {
    $passkey = new Passkey;

    expect($passkey->getAppends())->toBe(['authenticator']);
});

it('omits authenticator icons from serialization by default', function (): void {
    $passkey = new Passkey([
        'name' => 'Test',
        'credential_id' => 'cred',
        'credential' => ['aaguid' => 'a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942'],
    ]);

    $array = $passkey->toArray();

    expect($array)
        ->toHaveKey('authenticator', 'AliasVault')
        ->not->toHaveKey('authenticator_icon_light')
        ->not->toHaveKey('authenticator_icon_dark');
});

it('exposes authenticator icons via direct accessor without serialization', function (): void {
    $passkey = new Passkey([
        'name' => 'Test',
        'credential_id' => 'cred',
        'credential' => ['aaguid' => 'a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942'],
    ]);

    expect($passkey->authenticator_icon_light)->toStartWith('data:image/svg+xml;base64,');
    expect($passkey->authenticator_icon_dark)->toStartWith('data:image/svg+xml;base64,');
    expect($passkey->toArray())->not->toHaveKey('authenticator_icon_light');
});

it('includes authenticator icons in serialization when explicitly appended', function (): void {
    $passkey = new Passkey([
        'name' => 'Test',
        'credential_id' => 'cred',
        'credential' => ['aaguid' => 'a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942'],
    ]);

    $array = $passkey->append(['authenticator_icon_light', 'authenticator_icon_dark'])->toArray();

    expect($array['authenticator_icon_light'])->toStartWith('data:image/svg+xml;base64,');
    expect($array['authenticator_icon_dark'])->toStartWith('data:image/svg+xml;base64,');
});
