<?php

declare(strict_types=1);

use Laravel\Passkeys\Support\Aaguids;

beforeEach(function (): void {
    Aaguids::flush();
});

it('resolves a label without loading icon data', function (): void {
    expect(Aaguids::labelFor('a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942'))->toBe('AliasVault');

    expect((new ReflectionProperty(Aaguids::class, 'icons'))->getValue())->toBeNull();
});

it('loads icon data only once an icon is requested', function (): void {
    Aaguids::iconFor('a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942');

    $icons = new ReflectionProperty(Aaguids::class, 'icons');

    expect($icons->getValue())->toBeArray();
});

it('resolves light and dark icons', function (): void {
    $aaguid = 'a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942';

    expect(Aaguids::iconFor($aaguid, theme: 'light'))->toStartWith('data:image/svg+xml;base64,');
    expect(Aaguids::iconFor($aaguid, theme: 'dark'))->toStartWith('data:image/svg+xml;base64,');
});

it('defaults to the light theme', function (): void {
    $aaguid = 'a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942';

    expect(Aaguids::iconFor($aaguid))->toBe(Aaguids::iconFor($aaguid, theme: 'light'));
});

it('rejects an unsupported theme', function (): void {
    Aaguids::iconFor('a11a5faa-9f32-4b8c-8c5d-2f7d13e8c942', theme: 'sepia');
})->throws(InvalidArgumentException::class);

it('returns null for an unknown aaguid', function (): void {
    expect(Aaguids::labelFor('does-not-exist'))->toBeNull();
    expect(Aaguids::iconFor('does-not-exist'))->toBeNull();
});

it('returns null for an aaguid with no icon', function (): void {
    $withoutIcon = collect(Aaguids::all())
        ->keys()
        ->first(fn (string $aaguid): bool => Aaguids::iconFor($aaguid) === null);

    expect($withoutIcon)->not->toBeNull()
        ->and(Aaguids::labelFor($withoutIcon))->not->toBeNull();
});
