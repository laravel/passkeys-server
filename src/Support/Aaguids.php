<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Support;

/**
 * AAGUID to authenticator metadata mapping.
 *
 * @see https://github.com/passkeydeveloper/passkey-authenticator-aaguids
 *
 * @phpstan-type AaguidEntry array{name: string, icon_light: string|null, icon_dark: string|null}
 */
class Aaguids
{
    /**
     * The cached AAGUID metadata mapping.
     *
     * @var array<string, AaguidEntry>|null
     */
    protected static ?array $aaguids = null;

    /**
     * Get the authenticator label for the given AAGUID.
     */
    public static function labelFor(string $aaguid): ?string
    {
        return static::all()[$aaguid]['name'] ?? null;
    }

    /**
     * Get the light-mode icon (data URI) for the given AAGUID.
     */
    public static function iconLightFor(string $aaguid): ?string
    {
        return static::all()[$aaguid]['icon_light'] ?? null;
    }

    /**
     * Get the dark-mode icon (data URI) for the given AAGUID.
     */
    public static function iconDarkFor(string $aaguid): ?string
    {
        return static::all()[$aaguid]['icon_dark'] ?? null;
    }

    /**
     * Get the unknown AAGUID value.
     */
    public static function unknown(): string
    {
        return '00000000-0000-0000-0000-000000000000';
    }

    /**
     * Get all AAGUID metadata mappings.
     *
     * @return array<string, AaguidEntry>
     */
    public static function all(): array
    {
        /** @var array<string, AaguidEntry> */
        return static::$aaguids ??= require __DIR__.'/../../resources/aaguids.php';
    }

    /**
     * Flush the cached AAGUIDs.
     */
    public static function flush(): void
    {
        static::$aaguids = null;
    }
}
