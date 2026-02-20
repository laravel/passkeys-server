<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravel\Passkeys\Passkey;

class PasskeyVerified
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Authenticatable $user,
        public Passkey $passkey
    ) {
        //
    }
}
