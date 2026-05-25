<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class WebStubUser extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;

    protected $table = 'web_stub_users';

    public function getPasskeyGuard(): string
    {
        return 'web';
    }
}
