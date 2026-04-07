<?php

declare(strict_types=1);

/**
 * Syncs the AAGUID list from the passkey-authenticator-aaguids repository.
 *
 * @see https://github.com/passkeydeveloper/passkey-authenticator-aaguids
 */
$source = 'https://raw.githubusercontent.com/passkeydeveloper/passkey-authenticator-aaguids/main/aaguid.json';
$destination = __DIR__.'/../resources/aaguids.json';

$json = file_get_contents($source);

if ($json === false) {
    fwrite(STDERR, "Failed to fetch AAGUID list from {$source}\n");
    exit(1);
}

$data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

$aaguids = array_map(fn (array $entry) => $entry['name'], $data);

file_put_contents(
    $destination,
    json_encode($aaguids, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n",
);

echo 'Synced '.count($aaguids)." AAGUIDs.\n";
