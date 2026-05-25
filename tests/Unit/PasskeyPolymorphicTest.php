<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Tests\TestCase;

uses(TestCase::class);

it('exposes authenticatable() as a MorphTo relation', function (): void {
    expect((new Passkey)->authenticatable())->toBeInstanceOf(MorphTo::class);
});

it('uses authenticatable_type as the morph type column', function (): void {
    expect((new Passkey)->authenticatable()->getMorphType())->toBe('authenticatable_type');
});

it('uses authenticatable_id as the morph foreign key', function (): void {
    expect((new Passkey)->authenticatable()->getForeignKeyName())->toBe('authenticatable_id');
});

it('removes user_id from fillable', function (): void {
    expect((new Passkey)->getFillable())->not->toContain('user_id');
});

it('adds polymorphic columns to fillable', function (): void {
    $fillable = (new Passkey)->getFillable();

    expect($fillable)->toContain('authenticatable_type')
        ->and($fillable)->toContain('authenticatable_id');
});
