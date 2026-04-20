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

$aaguids = array_map(fn (array $entry) => $entry['name'], $data);

$exported = var_export($aaguids, true);
$exported = str_replace("\n  ", "\n    ", $exported);
$exported = substr_replace($exported, '[', 0, strlen('array ('));
$exported = substr_replace($exported, ']', -1);

file_put_contents($destination, "<?php\n\nreturn {$exported};\n");

echo 'Synced '.count($aaguids)." AAGUIDs.\n";
