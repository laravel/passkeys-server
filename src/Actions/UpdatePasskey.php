<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passkeys\Events\PasskeyUpdated;
use Laravel\Passkeys\Passkey;

class UpdatePasskey
{
    /**
     * Update the given passkey's name.
     */
    public function __invoke(Authenticatable $user, Passkey $passkey, string $name): Passkey
    {
        $passkey->forceFill(['name' => $name])->save();

        PasskeyUpdated::dispatch($user, $passkey);

        return $passkey;
    }
}
