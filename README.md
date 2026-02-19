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

Add the `PasskeyAuthenticatable` trait to your User model and implement the `PasskeyUser` contract:

```php
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;
}
```

If you want to use custom models, override them in a service provider:

```php
use App\Models\User;
use App\Models\Passkey;
use Laravel\Passkeys\Passkeys;

public function boot(): void
{
    Passkeys::useUserModel(User::class);
    Passkeys::usePasskeyModel(Passkey::class);
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
    // Relying Party ID (defaults to APP_URL host)
    'relying_party_id' => parse_url(config('app.url'), PHP_URL_HOST),

    // WebAuthn timeout in milliseconds
    'timeout' => 60000,

    // Authentication guard
    'guard' => 'web',

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

### Sign-In Authorization Callback

You may block login after a valid passkey assertion (for example, suspended/banned accounts):

```php
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

Passkeys::authenticateUsing(function (Request $request, PasskeyUser $user, Passkey $passkey): bool {
    if ($user->is_banned) {
        throw ValidationException::withMessages([
            'credential' => ['This account has been banned.'],
        ]);
    }

    return true;
});
```

Return `false` to stop authentication, or throw your own `ValidationException` for a custom error message.

### User-Bound Verification (Reauth / 2FA Step)

Use `GenerateVerificationOptions` with an authenticated user to scope allowed credentials to that user, then pass the same user into `VerifyPasskey` to enforce ownership:

```php
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;

$options = app(GenerateVerificationOptions::class)($request->user());

$passkey = app(VerifyPasskey::class)(
    $request->credential(),
    $options,
    $request->user(),
);
```

This verifies the passkey without logging the user in again, which is useful for sensitive-action confirmation flows.

### Custom Actions

Actions handle the core WebAuthn logic. Extend an action and bind it in your service provider:

```php
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Webauthn\AuthenticatorSelectionCriteria;

class CustomRegistrationOptions extends GenerateRegistrationOptions
{
    protected function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        // Only allow platform authenticators (Touch ID, Face ID, Windows Hello)
        return AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
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

Extend the base model (or implement `Laravel\Passkeys\Contracts\Passkey`):

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
