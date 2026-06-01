<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class Passkeys
{
    /**
     * The passkey model class name.
     *
     * @var class-string<Passkey>
     *
     * @phpstan-var class-string<Passkey>
     */
    public static string $passkeyModel = Passkey::class;

    /**
     * The user model class name.
     *
     * @var class-string<Contracts\PasskeyUser>
     */
    public static string $userModel = 'App\\Models\\User';

    /**
     * Indicates if routes should be registered.
     */
    private static bool $registersRoutes = true;

    /**
     * Callback to determine if a passkey-verified user should be logged in.
     *
     * The callback receives the request, the authenticatable owner of the
     * passkey, the passkey itself, and the resolved guard name. The guard
     * argument lets multi-guard installs apply guard-specific authorization
     * (e.g. allow admins only from a trusted IP range).
     *
     * @var (Closure(Request, Contracts\PasskeyUser, Passkey, string): bool)|null
     */
    private static ?Closure $authorizeLoginUsing = null;

    /**
     * Get the relying party ID.
     */
    public static function relyingPartyId(): string
    {
        return Config::string('passkeys.relying_party_id');
    }

    /**
     * Get the relying party name.
     *
     * The RP name is deprecated in the WebAuthn spec as most clients don't display it,
     * but remains required for backwards compatibility. Defaults to the RP ID.
     *
     * @see https://www.w3.org/TR/webauthn-3/#dom-publickeycredentialentity-name
     */
    public static function relyingPartyName(): string
    {
        return static::relyingPartyId();
    }

    /**
     * Get the origins allowed to complete WebAuthn ceremonies.
     *
     * @return list<string>
     */
    public static function allowedOrigins(): array
    {
        /** @var list<string> $origins */
        $origins = array_values(array_filter(
            Config::array('passkeys.allowed_origins', []),
            fn ($origin): bool => is_string($origin) && $origin !== '',
        ));

        if ($origins === []) {
            throw new RuntimeException('At least one passkey allowed origin must be configured.');
        }

        return $origins;
    }

    /**
     * Determine whether WebAuthn ceremonies may be completed from subdomains
     * of the configured allowed origins.
     *
     * When enabled, an origin such as https://tenant.example.com satisfies an
     * allowed origin of https://example.com. Defaults to false to preserve the
     * stricter exact-origin matching.
     */
    public static function allowSubdomains(): bool
    {
        return (bool) Config::get('passkeys.allow_subdomains', false);
    }

    /**
     * Get the WebAuthn timeout in milliseconds.
     *
     * @return positive-int
     */
    public static function timeout(): int
    {
        $timeout = Config::integer('passkeys.timeout', 60000);

        if ($timeout < 1) {
            throw new RuntimeException('Passkey timeout must be a positive integer.');
        }

        return $timeout;
    }

    /**
     * Get the passkey model class name.
     *
     * @return class-string<Passkey>
     *
     * @phpstan-return class-string<Passkey>
     */
    public static function passkeyModel(): string
    {
        return static::$passkeyModel;
    }

    /**
     * Set the passkey model class name.
     *
     * @param  class-string<Passkey>  $model
     *
     * @phpstan-param class-string<Passkey>  $model
     */
    public static function usePasskeyModel(string $model): void
    {
        static::$passkeyModel = $model;
    }

    /**
     * Get the user model class name.
     *
     * @return class-string<Contracts\PasskeyUser>
     */
    public static function userModel(): string
    {
        return static::$userModel;
    }

    /**
     * Set the user model class name.
     *
     * @param  class-string<Contracts\PasskeyUser>  $model
     */
    public static function useUserModel(string $model): void
    {
        static::$userModel = $model;
    }

    /**
     * Resolve the user model class for a given auth guard.
     *
     * @return class-string<Contracts\PasskeyUser>
     *
     * @throws RuntimeException when no per-guard config exists for $guard
     */
    public static function userModelFor(string $guard): string
    {
        $guards = Config::array('passkeys.guards', []);
        $model = $guards[$guard]['user_model'] ?? null;

        if ($model === null) {
            throw new RuntimeException("No passkeys configuration for guard '{$guard}'");
        }

        return $model;
    }

    /**
     * Resolve the DB connection name for a given auth guard, or null
     * to use the application's default connection.
     */
    public static function tableConnectionFor(string $guard): ?string
    {
        $guards = Config::array('passkeys.guards', []);
        if (! isset($guards[$guard])) {
            throw new RuntimeException("No passkeys configuration for guard '{$guard}'");
        }

        return $guards[$guard]['connection'] ?? null;
    }

    /**
     * Resolve the post-login redirect path for a given auth guard.
     */
    public static function redirectFor(string $guard): string
    {
        $guards = Config::array('passkeys.guards', []);
        $redirect = $guards[$guard]['redirect'] ?? null;

        if ($redirect === null) {
            throw new RuntimeException("No passkeys configuration for guard '{$guard}'");
        }

        return $redirect;
    }

    /**
     * Register a callback to authorize passkey logins before login.
     *
     * @param  (callable(Request, Contracts\PasskeyUser, Passkey, string): bool)|null  $callback
     */
    public static function authorizeLoginUsing(?callable $callback): void
    {
        self::$authorizeLoginUsing = $callback !== null
            ? Closure::fromCallable($callback)
            : null;
    }

    /**
     * Determine if a passkey-verified user should be allowed to log in.
     *
     * The $guard argument is the auth guard name resolved from the matched
     * route's 'passkey_guard' default, allowing per-guard authorization.
     */
    public static function allowsLogin(Request $request, Passkey $passkey, string $guard = 'web'): bool
    {
        if (! self::$authorizeLoginUsing instanceof Closure) {
            return true;
        }

        return (bool) (self::$authorizeLoginUsing)($request, $passkey->authenticatable, $passkey, $guard);
    }

    /**
     * Determine if Passkeys routes should be registered.
     */
    public static function shouldRegisterRoutes(): bool
    {
        return self::$registersRoutes;
    }

    /**
     * Configure Passkeys to not register its routes.
     */
    public static function ignoreRoutes(): void
    {
        self::$registersRoutes = false;
    }

    /**
     * Get the path to the package's migrations.
     */
    public static function migrationPath(): string
    {
        return __DIR__.'/../database/migrations';
    }
}
