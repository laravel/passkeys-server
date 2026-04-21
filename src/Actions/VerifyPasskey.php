<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Events\PasskeyVerified;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class VerifyPasskey
{
    /**
     * Validate the passkey credential and return the passkey.
     *
     * @throws InvalidPasskeyException
     */
    public function __invoke(
        PublicKeyCredential $credential,
        PublicKeyCredentialRequestOptions $options,
        ?PasskeyUser $user = null
    ): Passkey {
        $response = $this->getResponse($credential);
        $passkey = $this->getPasskey($credential);

        $this->ensurePasskeyBelongsToUser($passkey, $user);

        $source = $this->validate($response, $passkey, $options);

        $this->updatePasskey($passkey, $source);

        PasskeyVerified::dispatch($passkey->user, $passkey);

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
    public function getPasskey(PublicKeyCredential $credential): Passkey
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($credential->rawId);

        return Passkeys::passkeyModel()::where('credential_id', $credentialId)->first()
            ?? throw InvalidPasskeyException::make('Passkey not recognized. It may have been removed from your account.');
    }

    /**
     * Ensure the passkey belongs to the expected user.
     *
     * @throws InvalidPasskeyException
     */
    public function ensurePasskeyBelongsToUser(Passkey $passkey, ?PasskeyUser $user): void
    {
        if (! $user instanceof PasskeyUser) {
            return;
        }

        $identifier = $user->getKey();

        if (! is_scalar($identifier) || (string) $passkey->user_id !== (string) $identifier) {
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
    ): PublicKeyCredentialSource {
        $source = WebAuthn::fromJson(
            json_encode($passkey->credential, JSON_THROW_ON_ERROR),
            PublicKeyCredentialSource::class
        );

        return WebAuthn::assertionValidator()->check(
            publicKeyCredentialSource: $source,
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
    public function updatePasskey(Passkey $passkey, PublicKeyCredentialSource $source): void
    {
        $passkey->forceFill([
            'credential' => json_decode(WebAuthn::toJson($source), true, flags: JSON_THROW_ON_ERROR),
            'last_used_at' => now(),
        ])->save();
    }
}
