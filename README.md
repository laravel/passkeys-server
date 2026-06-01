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

The trait assumes a standard users schema with `name` and `email` columns, which authenticators show in their UI during registration and account selection. `displayName` falls back from `name` to `email` to the auth identifier, and `username` falls back from `email` to the auth identifier — override `getPasskeyDisplayName()` and `getPasskeyUsername()` if you want different values.

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

### Guest Routes (Login)
- `GET /passkeys/login/options` - Get login options
- `POST /passkeys/login` - Verify passkey and authenticate

### Authenticated Routes (Confirmation)
- `GET /passkeys/confirm/options` - Get confirmation options
- `POST /passkeys/confirm` - Confirm password via passkey

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

    // Origins allowed to complete WebAuthn ceremonies
    'allowed_origins' => [config('app.url')],

    // Accept ceremonies from subdomains of the allowed origins. When true, an
    // allowed origin of https://example.com also accepts
    // https://tenant.example.com. Useful for per-tenant/per-team subdomains
    // under one relying party. Defaults to false (exact-origin match).
    'allow_subdomains' => env('PASSKEYS_ALLOW_SUBDOMAINS', false),

    // Secret for deriving stable opaque user handles
    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),

    // WebAuthn timeout in milliseconds
    'timeout' => 60000,

    // Per-guard configuration. Add one block per auth guard that should
    // support passkeys; the default `web` guard is shown below.
    'guards' => [
        'web' => [
            'user_model' => env('AUTH_MODEL', App\Models\User::class),
            'connection' => null,
            'redirect' => '/',
            'middleware' => ['web'],
            'management_middleware' => ['password.confirm'],
        ],
    ],

    // Throttle middleware (null to disable)
    'throttle' => 'throttle:6,1',
];
```

## Multi-Guard Support

A single application can authenticate multiple user populations — for example a customer-facing `web` guard and a back-office `admin` guard — using passkeys, with each guard pointing at its own user model and (optionally) its own database connection. Passkeys are stored polymorphically, so different guards can use different user models without sharing a table.

### Configuration

Each guard gets its own block in `passkeys.guards`:

```php
'guards' => [
    'web' => [
        'user_model' => App\Models\User::class,
        'connection' => null,                  // null = default DB connection
        'redirect' => '/dashboard',
        'middleware' => ['web'],
        'management_middleware' => ['password.confirm'],
    ],
    'admin' => [
        'user_model' => App\Models\AdminUser::class,
        'connection' => 'central',             // separate DB connection
        'redirect' => '/admin',
        'middleware' => ['web', 'admin'],
        'management_middleware' => ['password.confirm:admin'],
    ],
],
```

Keys:

- `user_model` — the Eloquent user class for this guard.
- `connection` — optional DB connection name for the user model; `null` uses the default.
- `redirect` — post-login redirect target for `PasskeyLoginResponse`.
- `middleware` — middleware stack wrapping every passkey route for this guard.
- `management_middleware` — extra middleware on registration/management routes (e.g. recent-reauth gate).

### User Model Contract

Each user model declares which guard it belongs to:

```php
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class AdminUser extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable;

    public function getPasskeyGuard(): string
    {
        return 'admin';
    }
}
```

### Route Registration

Disable the automatic single-guard routes and register one route group per guard via the `Route::passkeys()` macro:

```php
use Laravel\Passkeys\Passkeys;

Passkeys::ignoreRoutes();

Route::passkeys('web', ['prefix' => '/passkeys']);
Route::passkeys('admin', ['prefix' => '/admin/passkeys', 'middleware' => ['web', 'admin']]);
```

The macro stamps a `passkey_guard` route default so the shared controllers know which guard's config to read.

### Polymorphic Schema

The `passkeys` table uses `authenticatable_type` + `authenticatable_id` polymorphic columns so multiple user models can share a single passkeys table. Single-guard installations upgrading from an earlier schema can apply the additive `make_passkeys_polymorphic` migration to backfill the morph columns without data loss.

## Events

The package fires the following events:

- `PasskeyRegistered` - When a new passkey is registered
- `PasskeyVerified` - When a user verifies with a passkey
- `PasskeyDeleted` - When a passkey is deleted

## Customization

### Login Authorization Callback

You may block login after a valid passkey assertion (for example, suspended/banned accounts):

```php
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

Passkeys::authorizeLoginUsing(function (Request $request, PasskeyUser $user, Passkey $passkey): bool {
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
    public function authenticatorSelection(): AuthenticatorSelectionCriteria
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
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;

class MyLoginResponse implements PasskeyLoginResponse
{
    public function toResponse($request)
    {
        return response()->json(['redirect' => '/dashboard']);
    }
}

// In your service provider
$this->app->singleton(PasskeyLoginResponse::class, MyLoginResponse::class);
```

Available response contracts:
- `PasskeyLoginResponse` - After successful login
- `PasskeyConfirmationResponse` - After successful confirmation
- `PasskeyRegistrationResponse` - After successful registration
- `PasskeyDeletedResponse` - After passkey deletion

### Custom Passkey Model

Extend the base model:

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
