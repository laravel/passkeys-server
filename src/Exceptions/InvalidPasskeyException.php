<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Exceptions;

use Illuminate\Validation\ValidationException;

class InvalidPasskeyException extends ValidationException
{
    /**
     * Create a new invalid passkey exception.
     *
     * @param  array<array-key, mixed>|string|null  $message
     */
    public static function make(array|string|null $message = null): static
    {
        return static::withMessages([
            'credential' => $message ?? __('Unable to register passkey. Please try again.'),
        ]);
    }
}
