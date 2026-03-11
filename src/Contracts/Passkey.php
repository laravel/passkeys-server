<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Contracts;

use Illuminate\Support\Carbon;

/**
 * Passkey model contract. Implementations must extend \Illuminate\Database\Eloquent\Model.
 *
 * @property int $id
 * @property int|string $user_id
 * @property string $name
 * @property string $credential_id
 * @property array<string, mixed> $credential
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PasskeyUser $user
 * @property-read string|null $authenticator
 */
interface Passkey
{
    //
}
