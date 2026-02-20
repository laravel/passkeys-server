<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Contracts;

/**
 * @property int $id
 * @property int|string $user_id
 * @property string $name
 * @property string $credential_id
 * @property array<string, mixed> $credential
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read PasskeyUser $user
 * @property-read string|null $authenticator
 */
interface Passkey
{
    //
}
