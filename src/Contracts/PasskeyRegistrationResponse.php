<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Laravel\Passkeys\Passkey;

interface PasskeyRegistrationResponse extends Responsable
{
    /**
     * Set the passkey that was registered.
     */
    public function withPasskey(Passkey $passkey): static;
}
