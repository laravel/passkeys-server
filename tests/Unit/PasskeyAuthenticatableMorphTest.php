<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Passkeys\Tests\Stubs\WebStubUser;
use Laravel\Passkeys\Tests\TestCase;

uses(TestCase::class);

it('returns a MorphMany relation from passkeys()', function (): void {
    expect((new WebStubUser)->passkeys())->toBeInstanceOf(MorphMany::class);
});

it('uses authenticatable_type as the morph type column', function (): void {
    expect((new WebStubUser)->passkeys()->getMorphType())->toBe('authenticatable_type');
});

it('uses authenticatable_id as the morph foreign key', function (): void {
    expect((new WebStubUser)->passkeys()->getForeignKeyName())->toBe('authenticatable_id');
});
