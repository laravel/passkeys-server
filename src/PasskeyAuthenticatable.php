<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Passkeys\Contracts\Passkey as PasskeyContract;

/**
 * @phpstan-require-implements \Laravel\Passkeys\Contracts\PasskeyUser
 */
trait PasskeyAuthenticatable
{
    /**
     * Get the passkeys associated with the user.
     *
     * @return HasMany<PasskeyContract, \Illuminate\Database\Eloquent\Model>
     *
     * @phpstan-return HasMany<\Laravel\Passkeys\Passkey, \Illuminate\Database\Eloquent\Model>
     */
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkeys::passkeyModel());
    }

    /**
     * Determine if the user has any passkeys enabled.
     */
    public function hasPasskeysEnabled(): bool
    {
        return $this->passkeys()->exists();
    }

    /**
     * Get the unique user handle for WebAuthn.
     *
     * This should be a stable identifier that does not reveal PII.
     */
    public function getPasskeyUserHandle(): string
    {
        if (isset($this->passkey_user_handle) && ! empty($this->passkey_user_handle)) {
            return $this->passkey_user_handle;
        }

        return (string) $this->getKey();
    }

    /**
     * Get the display name for WebAuthn registration.
     */
    public function getPasskeyDisplayName(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Get the username for WebAuthn registration.
     */
    public function getPasskeyUsername(): string
    {
        return $this->email;
    }
}
