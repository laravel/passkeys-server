<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Laravel\Passkeys\Passkeys;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;

class GenerateVerificationOptions
{
    /**
     * Generate verification options for passwordless login.
     *
     * Uses discoverable credentials (no allowCredentials) for passwordless flow.
     *
     * @see https://www.w3.org/TR/webauthn-3/#dictdef-publickeycredentialrequestoptions
     */
    public function __invoke(): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Passkeys::relyingPartyId(),
            allowCredentials: [],
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: Passkeys::timeout(),
        );
    }
}
