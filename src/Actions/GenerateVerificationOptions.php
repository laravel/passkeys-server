<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Actions;

use Laravel\Passkeys\Passkeys;
use Webauthn\PublicKeyCredentialRequestOptions;

class GenerateVerificationOptions
{
    /**
     * Generate verification options for passwordless login.
     *
     * Uses discoverable credentials (no allowCredentials) for passwordless flow.
     */
    public function __invoke(): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Passkeys::relyingPartyId(),
            allowCredentials: [],
            userVerification: Passkeys::userVerificationRequirement(),
            timeout: Passkeys::timeout(),
        );
    }
}
