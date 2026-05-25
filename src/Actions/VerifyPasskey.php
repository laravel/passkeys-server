<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Illuminate\Support\Facades\DB;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Events\PasskeyVerified;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

class VerifyPasskey
{
    /**
     * Validate the passkey credential and return the passkey.
     *
     * The optional $guard parameter scopes the action to a specific auth
     * guard for multi-guard installs; defaults to 'web' for BC.
     *
     * @throws InvalidPasskeyException
     */
    public function __invoke(
        PublicKeyCredential $credential,
        PublicKeyCredentialRequestOptions $options,
        ?PasskeyUser $user = null,
        string $guard = 'web'
    ): Passkey {
        $response = $this->getResponse($credential);

        /** @var Passkey $passkey */
        $passkey = DB::transaction(function () use ($credential, $options, $user, $response, $guard) {
            $passkey = $this->getPasskey($credential, lock: true, guard: $guard);

            $this->ensurePasskeyBelongsToUser($passkey, $user);

            $source = $this->validate($response, $passkey, $options);

            $this->updatePasskey($passkey, $source);

            $owner = $passkey->authenticatable;

            PasskeyVerified::dispatch($owner, $passkey);

            return $passkey;
        });

        return $passkey;
    }

    /**
     * Get the authenticator assertion response from the credential.
     *
     * @throws InvalidPasskeyException
     */
    protected function getResponse(PublicKeyCredential $credential): AuthenticatorAssertionResponse
    {
        if (! $credential->response instanceof AuthenticatorAssertionResponse) {
            throw InvalidPasskeyException::make('Unable to verify passkey. Please try again.');
        }

        return $credential->response;
    }

    /**
     * Get the passkey by credential ID.
     *
     * @throws InvalidPasskeyException
     */
    public function getPasskey(PublicKeyCredential $credential, bool $lock = false, string $guard = 'web'): Passkey
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($credential->rawId);

        $model = Passkeys::passkeyModel();
        $connection = $this->connectionForGuard($guard);

        $query = $connection !== null
            ? $model::on($connection)->where('credential_id', $credentialId)
            : $model::where('credential_id', $credentialId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw InvalidPasskeyException::make('Passkey not recognized. It may have been removed from your account.');
    }

    /**
     * Resolve the DB connection for the given guard, falling back to the
     * default connection when no per-guard configuration is registered.
     */
    protected function connectionForGuard(string $guard): ?string
    {
        try {
            return Passkeys::tableConnectionFor($guard);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Ensure the passkey belongs to the expected user.
     *
     * Compares the polymorphic authenticatable_type + authenticatable_id
     * columns against the expected user's morph class + key.
     *
     * @throws InvalidPasskeyException
     */
    public function ensurePasskeyBelongsToUser(Passkey $passkey, ?PasskeyUser $user): void
    {
        if (! $user instanceof PasskeyUser) {
            return;
        }

        $identifier = $user->getKey();
        $expectedType = $user->getMorphClass();

        if (
            ! is_scalar($identifier)
            || (string) $passkey->authenticatable_id !== (string) $identifier
            || $passkey->authenticatable_type !== $expectedType
        ) {
            throw InvalidPasskeyException::make('Passkey not recognized. It may have been removed from your account.');
        }
    }

    /**
     * Validate the credential against the stored passkey.
     */
    protected function validate(
        AuthenticatorAssertionResponse $response,
        Passkey $passkey,
        PublicKeyCredentialRequestOptions $options
    ): CredentialRecord {
        $source = WebAuthn::fromJson(
            json_encode($passkey->credential, JSON_THROW_ON_ERROR),
            CredentialRecord::class
        );

        return WebAuthn::assertionValidator()->check(
            credentialRecord: $source,
            authenticatorAssertionResponse: $response,
            publicKeyCredentialRequestOptions: $options,
            host: Passkeys::relyingPartyId(),
            userHandle: $source->userHandle,
        );
    }

    /**
     * Update the passkey with the latest credential data.
     *
     * The credential must be persisted after each use to store the updated
     * signature counter, which is used to detect cloned authenticators.
     */
    public function updatePasskey(Passkey $passkey, CredentialRecord $source): void
    {
        $passkey->forceFill([
            'credential' => json_decode(WebAuthn::toJson($source), true, flags: JSON_THROW_ON_ERROR),
            'last_used_at' => now(),
        ])->save();
    }
}
