<?php

declare(strict_types=1);
use Laravel\Passkeys\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->config = config('passkeys');
});

it('exposes a guards key as an array', function (): void {
    expect($this->config)->toHaveKey('guards');
    expect($this->config['guards'])->toBeArray();
});

it('defines a web guard block under guards', function (): void {
    expect($this->config['guards'])->toHaveKey('web');
    expect($this->config['guards']['web'])->toBeArray();
});

it('requires user_model, connection, redirect, middleware and management_middleware on the web guard', function (): void {
    $web = $this->config['guards']['web'];

    expect($web)->toHaveKey('user_model');
    expect($web)->toHaveKey('connection');
    expect($web)->toHaveKey('redirect');
    expect($web)->toHaveKey('middleware');
    expect($web)->toHaveKey('management_middleware');
});

it('preserves the global relying_party_id, allowed_origins, timeout and user_handle_secret keys at the top level', function (): void {
    expect($this->config)->toHaveKey('relying_party_id');
    expect($this->config)->toHaveKey('allowed_origins');
    expect($this->config)->toHaveKey('timeout');
    expect($this->config)->toHaveKey('user_handle_secret');
});
