<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Passkey;

class DeletePasskey
{
    /**
     * Delete the given passkey.
     */
    public function __invoke(Authenticatable $user, Passkey $passkey): void
    {
        $passkey->delete();

        PasskeyDeleted::dispatch($user, $passkey);
    }
}
