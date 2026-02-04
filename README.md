# Laravel Passkeys

Passwordless authentication using WebAuthn/passkeys for Laravel.

## Installation

```bash
composer require laravel/passkeys
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=passkeys-migrations
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=passkeys-config
```

## Setup

Add the `PasskeyAuthenticatable` trait to your User model:

```php
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable
{
    use PasskeyAuthenticatable;
}
```

## JavaScript Client

This package is designed to work with the [`@laravel/passkeys`](https://github.com/laravel/passkeys) npm package:

```bash
npm install @laravel/passkeys
```

```js
import { Passkeys } from '@laravel/passkeys'

// Registration (authenticated user)
await Passkeys.register({ name: 'My MacBook' })

// Verification (login)
await Passkeys.verify()
```

## Routes

The package automatically registers the following routes:

### Guest Routes (Verification)
- `GET /passkeys/options` - Get verification options
- `POST /passkeys/verify` - Verify passkey and authenticate

### Authenticated Routes (Management)
- `GET /user/passkeys/options` - Get registration options
- `POST /user/passkeys` - Store new passkey
- `DELETE /user/passkeys/{passkey}` - Delete passkey

## Configuration

```php
// config/passkeys.php

return [
    // Relying Party (defaults to APP_URL host and app name)
    'relying_party' => [
        'id' => parse_url(config('app.url'), PHP_URL_HOST),
        'name' => config('app.name'),
    ],

    // WebAuthn timeout in milliseconds
    'timeout' => 60000,

    // User verification requirement: required, preferred, discouraged
    'user_verification' => 'required',

    // Authentication guard
    'guard' => 'web',

    // Custom passkey model
    'models' => [
        'passkey' => Laravel\Passkeys\Passkey::class,
    ],

    // Routes middleware
    'middleware' => ['web'],

    // Throttle middleware (null to disable)
    'throttle' => 'throttle:6,1',

    // Redirect after login
    'redirect' => '/dashboard',
];
```

## Events

The package fires the following events:

- `PasskeyRegistered` - When a new passkey is registered
- `PasskeyVerified` - When a user verifies with a passkey
- `PasskeyDeleted` - When a passkey is deleted

## Customization

### Custom Actions

Actions handle the core WebAuthn logic. Extend an action and bind it in your service provider:

```php
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;

class CustomRegistrationOptions extends GenerateRegistrationOptions
{
    protected function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        // Only allow platform authenticators (Touch ID, Face ID, Windows Hello)
        return AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
            userVerification: Passkeys::userVerificationRequirement(),
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );
    }
}

// In your service provider
$this->app->bind(GenerateRegistrationOptions::class, CustomRegistrationOptions::class);
```

Available actions:
- `GenerateRegistrationOptions`
- `GenerateVerificationOptions`
- `StorePasskey`
- `VerifyPasskey`
- `DeletePasskey`

### Custom Responses

Bind your own response classes to customize what happens after passkey operations:

```php
use Laravel\Passkeys\Contracts\PasskeyVerificationResponse;

class MyVerificationResponse implements PasskeyVerificationResponse
{
    public function toResponse($request)
    {
        return response()->json(['redirect' => '/dashboard']);
    }
}

// In your service provider
$this->app->singleton(PasskeyVerificationResponse::class, MyVerificationResponse::class);
```

Available response contracts:
- `PasskeyVerificationResponse` - After successful verification
- `PasskeyRegistrationResponse` - After successful registration
- `PasskeyDeletedResponse` - After passkey deletion

### Custom Passkey Model

Extend the base model for custom behavior:

```php
use Laravel\Passkeys\Passkey as BasePasskey;

class Passkey extends BasePasskey
{
    protected static function booted(): void
    {
        static::created(function ($passkey) {
            // Custom logic when passkey is created
        });
    }
}

// In config/passkeys.php
'models' => [
    'passkey' => App\Models\Passkey::class,
],
```

### Disable Routes

To register your own routes:

```php
use Laravel\Passkeys\Passkeys;

Passkeys::ignoreRoutes();
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
