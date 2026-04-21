<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Cose\Algorithms;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkeys;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class GenerateRegistrationOptions
{
    /**
     * Generate registration options for a user to create a new passkey.
     */
    public function __invoke(Authenticatable $user): PublicKeyCredentialCreationOptions
    {
        if (! $user instanceof PasskeyUser) {
            throw new RuntimeException('User model must implement the PasskeyUser contract.');
        }

        // Don't verify the authenticator's attestation certificate chain.
        $attestation = PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE;

        return PublicKeyCredentialCreationOptions::create(
            rp: $this->relyingParty(),
            user: $this->userEntity($user),
            challenge: random_bytes(32),
            pubKeyCredParams: $this->supportedAlgorithms(),
            authenticatorSelection: $this->authenticatorSelection(),
            attestation: $attestation,
            excludeCredentials: $this->excludedCredentials($user),
            timeout: Passkeys::timeout(),
        );
    }

    /**
     * Create the relying party entity.
     */
    protected function relyingParty(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(
            name: Passkeys::relyingPartyName(),
            id: Passkeys::relyingPartyId(),
        );
    }

    /**
     * Create the user entity for registration.
     */
    protected function userEntity(PasskeyUser $user): PublicKeyCredentialUserEntity
    {
        return PublicKeyCredentialUserEntity::create(
            name: $user->getPasskeyUsername(),
            id: $user->getPasskeyUserHandle(),
            displayName: $user->getPasskeyDisplayName(),
        );
    }

    /**
     * Get the authenticator selection criteria.
     *
     * @see https://www.w3.org/TR/webauthn-3/#dictdef-authenticatorselectioncriteria
     */
    public function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        // Allow any authenticator: built-in (Touch ID, Windows Hello) or external (YubiKey).
        $crossPlatform = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;

        // Require a discoverable credential so the user can sign in without typing a username.
        $residentKey = AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED;

        // Require user verification (biometric, PIN) rather than just presence (tap).
        $userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;

        return AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: $crossPlatform,
            userVerification: $userVerification,
            residentKey: $residentKey,
        );
    }

    /**
     * Get credentials to exclude from registration. Prevents the same
     * authenticator from being registered twice.
     *
     * @return array<PublicKeyCredentialDescriptor>
     */
    public function excludedCredentials(PasskeyUser $user): array
    {
        $type = PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY;

        return $user->passkeys()->get()->map(
            fn ($passkey): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                $type,
                Base64UrlSafe::decodeNoPadding($passkey->credential_id)
            )
        )->all();
    }

    /**
     * Get the supported public key credential algorithms.
     *
     * @return array<PublicKeyCredentialParameters>
     */
    public function supportedAlgorithms(): array
    {
        $type = PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY;

        // ES256 (ECDSA) is preferred and supported by most authenticators.
        // RS256 (RSA) is a fallback for older Windows Hello implementations.
        $es256 = Algorithms::COSE_ALGORITHM_ES256;
        $rs256 = Algorithms::COSE_ALGORITHM_RS256;

        return [
            PublicKeyCredentialParameters::create($type, $es256),
            PublicKeyCredentialParameters::create($type, $rs256),
        ];
    }
}
