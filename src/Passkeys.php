<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Illuminate\Support\Facades\Config;
use RuntimeException;
use Webauthn\AuthenticatorSelectionCriteria;

class Passkeys
{
    /**
     * The passkey model class name.
     *
     * @var class-string<Contracts\Passkey>
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
     * Get the relying party ID.
     */
    public static function relyingPartyId(): string
    {
        $rpId = Config::string('passkeys.relying_party.id');

        return $rpId;
    }

    /**
     * Get the relying party name.
     */
    public static function relyingPartyName(): string
    {
        $name = Config::string('passkeys.relying_party.name');

        return $name;
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
     * Get the configured user verification requirement.
     */
    public static function userVerificationRequirement(): string
    {
        $requirement = Config::string('passkeys.user_verification', AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED);

        if (in_array($requirement, AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENTS, true)) {
            return $requirement;
        }

        return AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
    }

    /**
     * Get the passkey model class name.
     *
     * @return class-string<Contracts\Passkey>
     * @phpstan-return class-string<Passkey>
     */
    public static function passkeyModel(): string
    {
        return static::$passkeyModel;
    }

    /**
     * Set the passkey model class name.
     *
     * @param  class-string<Contracts\Passkey>  $model
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
}
