<?php

declare(strict_types=1);

use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;

dataset('actionClasses', [
    'GenerateRegistrationOptions' => [GenerateRegistrationOptions::class],
    'StorePasskey' => [StorePasskey::class],
    'GenerateVerificationOptions' => [GenerateVerificationOptions::class],
    'VerifyPasskey' => [VerifyPasskey::class],
    'DeletePasskey' => [DeletePasskey::class],
]);

it('declares a string $guard parameter on __invoke()', function (string $actionClass): void {
    $reflection = new ReflectionMethod($actionClass, '__invoke');

    $guardParameter = null;

    foreach ($reflection->getParameters() as $parameter) {
        if ($parameter->getName() === 'guard') {
            $guardParameter = $parameter;
            break;
        }
    }

    expect($guardParameter)
        ->not->toBeNull("{$actionClass}::__invoke() must declare a \$guard parameter");

    $type = $guardParameter->getType();

    expect($type)
        ->toBeInstanceOf(ReflectionNamedType::class, "{$actionClass}::__invoke(): \$guard must have a named type");

    expect($type->getName())
        ->toBe('string', "{$actionClass}::__invoke(): \$guard must be typed as string");
})->with('actionClasses');
