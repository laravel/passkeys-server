<?php

declare(strict_types=1);

use Laravel\Passkeys\Contracts\PasskeyUser;

it('declares getPasskeyGuard() on the contract', function (): void {
    $reflection = new ReflectionClass(PasskeyUser::class);

    expect($reflection->hasMethod('getPasskeyGuard'))->toBeTrue();
});

it('declares getPasskeyGuard() with a string return type', function (): void {
    $reflection = new ReflectionClass(PasskeyUser::class);
    $method = $reflection->getMethod('getPasskeyGuard');

    $returnType = $method->getReturnType();

    expect($returnType)->not->toBeNull();
    expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
    expect($returnType->getName())->toBe('string');
});
