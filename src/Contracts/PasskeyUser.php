<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface PasskeyUser extends Authenticatable
{
    /**
     * Get the passkeys associated with the user.
     *
     * @return MorphMany<Passkey, Model>
     *
     * @phpstan-return MorphMany<\Laravel\Passkeys\Passkey, Model>
     */
    public function passkeys(): MorphMany;

    /**
     * Determine if the user has any passkeys enabled.
     */
    public function hasPasskeysEnabled(): bool;

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey();

    /**
     * Get the unique user handle for WebAuthn.
     */
    public function getPasskeyUserHandle(): string;

    /**
     * Get the display name for WebAuthn registration.
     */
    public function getPasskeyDisplayName(): string;

    /**
     * Get the username for WebAuthn registration.
     */
    public function getPasskeyUsername(): string;

    /**
     * Get the auth guard this user belongs to.
     *
     * Used by the multi-guard resolver to pick the correct
     * user_model, DB connection, and post-login redirect from
     * config('passkeys.guards.{name}').
     */
    public function getPasskeyGuard(): string;
}
