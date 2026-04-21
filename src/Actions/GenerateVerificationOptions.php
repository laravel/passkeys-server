<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkeys;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class GenerateVerificationOptions
{
    /**
     * Generate verification options for passwordless login or user confirmation.
     *
     * @see https://www.w3.org/TR/webauthn-3/#dictdef-publickeycredentialrequestoptions
     */
    public function __invoke(?PasskeyUser $user = null): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Passkeys::relyingPartyId(),
            allowCredentials: $this->allowCredentials($user),
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: Passkeys::timeout(),
        );
    }

    /**
     * Get credentials allowed for verification.
     *
     * When no user is provided, uses discoverable credentials for passwordless login.
     *
     * @return array<PublicKeyCredentialDescriptor>
     */
    public function allowCredentials(?PasskeyUser $user): array
    {
        if (! $user instanceof PasskeyUser) {
            return [];
        }

        $type = PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY;

        return $user->passkeys()->get()->map(
            fn ($passkey): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                $type,
                Base64UrlSafe::decodeNoPadding($passkey->credential_id)
            )
        )->all();
    }
}
