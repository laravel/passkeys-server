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
     *
     * The optional $guard parameter scopes the action to a specific auth
     * guard for multi-guard installs; defaults to 'web' for BC.
     */
    public function __invoke(Authenticatable $user, Passkey $passkey, string $guard = 'web'): void
    {
        $passkey->delete();

        PasskeyDeleted::dispatch($user, $passkey);
    }
}
