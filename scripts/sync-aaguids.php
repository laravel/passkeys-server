<?php

declare(strict_types=1);

/**
 * Syncs the AAGUID list from the passkey-authenticator-aaguids repository.
 *
 * @see https://github.com/passkeydeveloper/passkey-authenticator-aaguids
 */
$source = 'https://raw.githubusercontent.com/passkeydeveloper/passkey-authenticator-aaguids/main/aaguid.json';
$destination = __DIR__.'/../resources/aaguids.php';

$json = file_get_contents($source);

if ($json === false) {
    fwrite(STDERR, "Failed to fetch AAGUID list from {$source}\n");
    exit(1);
}

$data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

$aaguids = array_map(fn (array $entry): array => [
    'name' => $entry['name'],
    'icon_light' => $entry['icon_light'] ?? null,
    'icon_dark' => $entry['icon_dark'] ?? null,
], $data);

$lines = ['<?php', '', 'return ['];

foreach ($aaguids as $aaguid => $entry) {
    $lines[] = sprintf('    %s => [', var_export($aaguid, true));
    foreach (['name', 'icon_light', 'icon_dark'] as $key) {
        $value = $entry[$key] === null ? 'null' : var_export($entry[$key], true);
        $lines[] = sprintf("        '%s' => %s,", $key, $value);
    }
    $lines[] = '    ],';
}

$lines[] = '];';
$lines[] = '';

file_put_contents($destination, implode("\n", $lines));

echo 'Synced '.count($aaguids)." AAGUIDs.\n";
