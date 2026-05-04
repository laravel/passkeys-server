<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Support;

use InvalidArgumentException;

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
     * The cached AAGUID to name mapping.
     *
     * @var array<string, string>|null
     */
    protected static ?array $labels = null;

    /**
     * Get the authenticator label for the given AAGUID.
     */
    public static function labelFor(string $aaguid): ?string
    {
        return static::all()[$aaguid] ?? null;
    }

    /**
     * Get the authenticator icon (data URI) for the given AAGUID.
     */
    public static function iconFor(string $aaguid, string $theme = 'light'): ?string
    {
        if (! in_array($theme, ['light', 'dark'], true)) {
            throw new InvalidArgumentException("Unsupported icon theme [{$theme}]. Expected 'light' or 'dark'.");
        }

        return static::metadata()[$aaguid]['icon_'.$theme] ?? null;
    }

    /**
     * Get the unknown AAGUID value.
     */
    public static function unknown(): string
    {
        return '00000000-0000-0000-0000-000000000000';
    }

    /**
     * Get all AAGUID to name mappings.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return static::$labels ??= array_map(
            static fn (array $entry): string => $entry['name'],
            static::metadata(),
        );
    }

    /**
     * Flush the cached AAGUIDs.
     */
    public static function flush(): void
    {
        static::$aaguids = null;
        static::$labels = null;
    }

    /**
     * Get the full AAGUID metadata mapping.
     *
     * @return array<string, AaguidEntry>
     */
    protected static function metadata(): array
    {
        return static::$aaguids ??= require __DIR__.'/../../resources/aaguids.php';
    }
}
