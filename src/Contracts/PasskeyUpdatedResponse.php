<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Laravel\Passkeys\Passkey;

interface PasskeyUpdatedResponse extends Responsable
{
    /**
     * Set the passkey that was updated.
     */
    public function withPasskey(Passkey $passkey): static;
}
