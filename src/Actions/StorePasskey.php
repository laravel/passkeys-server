<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Events\PasskeyRegistered;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

class StorePasskey
{
    /**
     * Validate and store a passkey for the user.
     *
     * @throws InvalidPasskeyException
     */
    public function __invoke(
        Authenticatable $user,
        string $name,
        PublicKeyCredential $credential,
        PublicKeyCredentialCreationOptions $options
    ): Passkey {
        if (! $user instanceof PasskeyUser) {
            throw new RuntimeException('User model must implement the PasskeyUser contract.');
        }

        $response = $this->getResponse($credential);

        $source = $this->validate($response, $options);

        $this->ensureCredentialIsUnique($source);

        $passkey = $this->createPasskey($user, $name, $source);

        PasskeyRegistered::dispatch($user, $passkey);

        return $passkey;
    }

    /**
     * Get the authenticator attestation response from the credential.
     *
     * @throws InvalidPasskeyException
     */
    protected function getResponse(PublicKeyCredential $credential): AuthenticatorAttestationResponse
    {
        if (! $credential->response instanceof AuthenticatorAttestationResponse) {
            throw InvalidPasskeyException::make('Unable to register passkey. Please try again.');
        }

        return $credential->response;
    }

    /**
     * Validate the credential and return the source.
     */
    protected function validate(
        AuthenticatorAttestationResponse $response,
        PublicKeyCredentialCreationOptions $options
    ): PublicKeyCredentialSource {
        return WebAuthn::attestationValidator()->check(
            authenticatorAttestationResponse: $response,
            publicKeyCredentialCreationOptions: $options,
            host: Passkeys::relyingPartyId(),
        );
    }

    /**
     * Ensure the credential is not already registered.
     *
     * @throws InvalidPasskeyException
     */
    protected function ensureCredentialIsUnique(PublicKeyCredentialSource $source): void
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId);

        $exists = Passkeys::passkeyModel()::where('credential_id', $credentialId)->exists();

        if ($exists) {
            throw InvalidPasskeyException::make('Unable to register this passkey.');
        }
    }

    /**
     * Create the passkey record for the user.
     */
    public function createPasskey(
        PasskeyUser $user,
        string $name,
        PublicKeyCredentialSource $source
    ): Passkey {
        $credentialId = Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId);

        return $user->passkeys()->create([
            'name' => $name,
            'credential_id' => $credentialId,
            'credential' => json_decode(WebAuthn::toJson($source), true, flags: JSON_THROW_ON_ERROR),
        ]);
    }
}
