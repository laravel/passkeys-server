<?php

declare(strict_types=1);

arch('strict types')
    ->expect('Laravel\Passkeys')
    ->toUseStrictTypes();

arch('no debugging')
    ->expect(['dd', 'dump', 'var_dump', 'die', 'ray'])
    ->not->toBeUsed();

arch('controllers')
    ->expect('Laravel\Passkeys\Http\Controllers')
    ->toExtend(\Illuminate\Routing\Controller::class)
    ->toHaveSuffix('Controller');

arch('actions are invokable')
    ->expect('Laravel\Passkeys\Actions')
    ->toBeInvokable();

arch('exceptions')
    ->expect('Laravel\Passkeys\Exceptions')
    ->toExtend(\Exception::class)
    ->toHaveSuffix('Exception');

arch('requests')
    ->expect('Laravel\Passkeys\Http\Requests')
    ->toExtend(\Illuminate\Foundation\Http\FormRequest::class)
    ->toHaveSuffix('Request');

arch('no direct env calls')
    ->expect('env')
    ->not->toBeUsedIn('Laravel\Passkeys')
    ->ignoring([
        \Laravel\Passkeys\PasskeysServiceProvider::class,
    ]);

arch('tests')
    ->expect('Tests')
    ->not->toBeUsedIn('Laravel\Passkeys');
