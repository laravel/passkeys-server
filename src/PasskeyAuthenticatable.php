<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Passkeys\Contracts\Passkey as PasskeyContract;
use Laravel\Passkeys\Contracts\PasskeyUser;
use ParagonIE\ConstantTime\Base64UrlSafe;

/**
 * @phpstan-require-implements PasskeyUser
 */
trait PasskeyAuthenticatable
{
    /**
     * Get the passkeys associated with the user.
     *
     * @return HasMany<PasskeyContract, Model>
     *
     * @phpstan-return HasMany<Passkey, Model>
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
     * Returns raw bytes. The handle is generated on first access, persisted
     * on the user record as a base64url-encoded string, and shared across
     * all of the user's passkeys.
     */
    public function getPasskeyUserHandle(): string
    {
        $encoded = $this->getAttribute('passkey_user_handle');

        if (is_string($encoded) && $encoded !== '') {
            return Base64UrlSafe::decodeNoPadding($encoded);
        }

        $handle = random_bytes(32);

        $this->forceFill([
            'passkey_user_handle' => Base64UrlSafe::encodeUnpadded($handle),
        ])->save();

        return $handle;
    }

    /**
     * Get the display name for WebAuthn registration.
     * Shown in the authenticator UI as a human-friendly name.
     */
    public function getPasskeyDisplayName(): string
    {
        return $this->email;
    }

    /**
     * Get the username for WebAuthn registration.
     * Used as the account identifier in the authenticator UI.
     */
    public function getPasskeyUsername(): string
    {
        return $this->email;
    }
}
