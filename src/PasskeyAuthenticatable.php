<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;
use Laravel\Passkeys\Contracts\Passkey as PasskeyContract;
use Laravel\Passkeys\Contracts\PasskeyUser;

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
     * This should be a stable identifier that does not reveal PII.
     */
    public function getPasskeyUserHandle(): string
    {
        return hash_hmac(
            'sha256',
            $this->getTable().'|'.$this->getKey(),
            Config::string('passkeys.user_handle_secret'),
            binary: true,
        );
    }

    /**
     * Get the display name for WebAuthn registration.
     *
     * Shown prominently in authenticator UIs (registration prompts,
     * account pickers, password manager entries). Falls back from
     * `name` to `email` to an opaque label derived from the user
     * handle when those columns are absent.
     */
    public function getPasskeyDisplayName(): string
    {
        return $this->getAttribute('name')
            ?? $this->getAttribute('email')
            ?? $this->fallbackPasskeyLabel();
    }

    /**
     * Get the username for WebAuthn registration.
     *
     * Used as the account identifier in authenticator UIs, typically
     * rendered as the subtitle beneath the display name. Falls back
     * to an opaque label derived from the user handle when `email`
     * is absent.
     */
    public function getPasskeyUsername(): string
    {
        return $this->getAttribute('email')
            ?? $this->fallbackPasskeyLabel();
    }

    /**
     * An opaque, stable label used when no name or email is available.
     */
    protected function fallbackPasskeyLabel(): string
    {
        return 'user-'.substr(bin2hex($this->getPasskeyUserHandle()), 0, 10);
    }
}
