<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Config;
use Laravel\Passkeys\Contracts\PasskeyUser;

/**
 * @phpstan-require-implements PasskeyUser
 */
trait PasskeyAuthenticatable
{
    /**
     * Polymorphic morphMany — single passkeys table can hold
     * credentials for many distinct user models across guards.
     *
     * @return MorphMany<Passkey, Model>
     *
     * @phpstan-return MorphMany<Passkey, Model>
     */
    public function passkeys(): MorphMany
    {
        return $this->morphMany(
            Passkeys::passkeyModel(),
            'authenticatable',
        );
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
     * `name` to `email` to the auth identifier when columns are absent.
     */
    public function getPasskeyDisplayName(): string
    {
        return $this->getAttribute('name')
            ?? $this->getAttribute('email')
            ?? (string) $this->getAuthIdentifier();
    }

    /**
     * Get the username for WebAuthn registration.
     *
     * Used as the account identifier in authenticator UIs, typically
     * rendered as the subtitle beneath the display name. Falls back
     * from `email` to the auth identifier when the column is absent.
     */
    public function getPasskeyUsername(): string
    {
        return $this->getAttribute('email')
            ?? (string) $this->getAuthIdentifier();
    }

    /**
     * Get the auth guard this user belongs to.
     *
     * Default implementation returns the application's default
     * guard, preserving single-guard backwards compatibility.
     * Override in the user model when participating in the
     * multi-guard resolver (see config('passkeys.guards.{name}')).
     */
    public function getPasskeyGuard(): string
    {
        return Config::string('auth.defaults.guard');
    }
}
