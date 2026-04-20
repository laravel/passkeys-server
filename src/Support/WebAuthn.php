<?php

declare(strict_types=1);

namespace Laravel\Passkeys\Support;

use Laravel\Passkeys\Passkeys;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

/**
 * This class provides a static interface to the webauthn-lib package, handling
 * serialization, deserialization, and validation of WebAuthn ceremonies.
 */
final class WebAuthn
{
    /**
     * The cached serializer instance.
     */
    private static ?SerializerInterface $serializer = null;

    /**
     * The cached attestation statement support manager.
     */
    private static ?AttestationStatementSupportManager $attestationStatementSupportManager = null;

    /**
     * Serialize data to JSON.
     */
    public static function toJson(mixed $data): string
    {
        return self::serializer()->serialize($data, 'json');
    }

    /**
     * Deserialize JSON to a specific class.
     *
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     */
    public static function fromJson(string $json, string $class): mixed
    {
        return self::serializer()->deserialize($json, $class, 'json');
    }

    /**
     * Create the attestation response validator for registration ceremonies.
     */
    public static function attestationValidator(): AuthenticatorAttestationResponseValidator
    {
        return AuthenticatorAttestationResponseValidator::create(
            ceremonyStepManager: self::ceremonyStepManagerFactory()->creationCeremony()
        );
    }

    /**
     * Create the assertion response validator for verification ceremonies.
     */
    public static function assertionValidator(): AuthenticatorAssertionResponseValidator
    {
        return AuthenticatorAssertionResponseValidator::create(
            ceremonyStepManager: self::ceremonyStepManagerFactory()->requestCeremony()
        );
    }

    /**
     * Get or create the ceremony step manager factory.
     */
    private static function ceremonyStepManagerFactory(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory;

        $factory->setAllowedOrigins(Passkeys::allowedOrigins());

        $factory->setAttestationStatementSupportManager(
            self::attestationStatementSupportManager()
        );

        return $factory;
    }

    /**
     * Get or create the attestation statement support manager.
     *
     * Only "none" attestation is registered, so we accept passkeys without
     * verifying hardware attestation certificates from the authenticator.
     */
    private static function attestationStatementSupportManager(): AttestationStatementSupportManager
    {
        if (! self::$attestationStatementSupportManager instanceof AttestationStatementSupportManager) {
            self::$attestationStatementSupportManager = AttestationStatementSupportManager::create();
            self::$attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
        }

        return self::$attestationStatementSupportManager;
    }

    /**
     * Get or create the serializer instance.
     */
    private static function serializer(): SerializerInterface
    {
        if (! self::$serializer instanceof SerializerInterface) {
            self::$serializer = (new WebauthnSerializerFactory(
                self::attestationStatementSupportManager()
            ))->create();
        }

        return self::$serializer;
    }

    /**
     * Reset the cached instances (useful for testing).
     */
    public static function flush(): void
    {
        self::$serializer = null;
        self::$attestationStatementSupportManager = null;
    }
}
