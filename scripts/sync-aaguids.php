<?php

declare(strict_types=1);

/**
 * Syncs the AAGUID list from the passkey-authenticator-aaguids repository.
 *
 * @see https://github.com/passkeydeveloper/passkey-authenticator-aaguids
 */
$source = 'https://raw.githubusercontent.com/passkeydeveloper/passkey-authenticator-aaguids/main/aaguid.json';
$namesDestination = __DIR__.'/../resources/aaguids.php';
$iconsDestination = __DIR__.'/../resources/aaguid-icons.php';

$json = file_get_contents($source);

if ($json === false) {
    fwrite(STDERR, "Failed to fetch AAGUID list from {$source}\n");
    exit(1);
}

$data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

$names = array_map(fn (array $entry): string => $entry['name'], $data);

$icons = array_filter(array_map(fn (array $entry): array => array_filter([
    'light' => $entry['icon_light'] ?? null,
    'dark' => $entry['icon_dark'] ?? null,
], fn (?string $icon): bool => $icon !== null), $data));

/**
 * Render a flat `key => scalar` array as a PHP file.
 *
 * @param  array<string, string>  $values
 */
$renderNames = function (array $values): string {
    $lines = ['<?php', '', 'return ['];

    foreach ($values as $aaguid => $name) {
        $lines[] = sprintf('    %s => %s,', var_export($aaguid, true), var_export($name, true));
    }

    return implode("\n", [...$lines, '];', '']);
};

/**
 * Render a nested `key => [theme => icon]` array as a PHP file.
 *
 * @param  array<string, array<string, string>>  $values
 */
$renderIcons = function (array $values): string {
    $lines = ['<?php', '', 'return ['];

    foreach ($values as $aaguid => $themes) {
        $lines[] = sprintf('    %s => [', var_export($aaguid, true));
        foreach ($themes as $theme => $icon) {
            $lines[] = sprintf('        %s => %s,', var_export($theme, true), var_export($icon, true));
        }
        $lines[] = '    ],';
    }

    return implode("\n", [...$lines, '];', '']);
};

file_put_contents($namesDestination, $renderNames($names));
file_put_contents($iconsDestination, $renderIcons($icons));

echo 'Synced '.count($names).' AAGUIDs and '.count($icons)." icon sets.\n";
