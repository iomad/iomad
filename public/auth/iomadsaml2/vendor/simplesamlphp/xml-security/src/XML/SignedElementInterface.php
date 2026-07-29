<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML;

use SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmInterface;
use SimpleSAML\XMLSecurity\Key\KeyInterface;
use SimpleSAML\XMLSecurity\XML\ds\Signature;

/**
 * An interface describing signed elements.
 *
 * @package simplesamlphp/xml-security
 */
interface SignedElementInterface extends CanonicalizableElementInterface
{
    /**
     * Get the ID of this element.
     *
     * When this method returns null, the signature created for this object will reference the entire document.
     *
     * @return string|null The ID of this element, or null if we don't have one.
     */
    public function getId(): ?string;


    /**
     * Retrieve the signature in this object, if any.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\Signature|null
     */
    public function getSignature(): ?Signature;


    /**
     * Retrieve the key used to verify the signature in this object.
     *
     * @return \SimpleSAML\XMLSecurity\Key\KeyInterface The key that verified the signature in this object.
     * @throws \Exception if an error occurs while trying to extract the public key from a certificate.
     */
    public function getVerifyingKey(): ?KeyInterface;


    /**
     * Whether this object is signed or not.
     *
     * @return bool
     */
    public function isSigned(): bool;


    /**
     * Verify the signature in this object.
     *
     * If no signature is present, false is returned. If a signature is present,
     * but cannot be verified, an exception will be thrown.
     *
     * @param \SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmInterface|null $verifier The verifier to use to
     * verify the signature. If null, attempt to verify it with the KeyInfo information in the signature.
     * @return \SimpleSAML\XMLSecurity\XML\SignedElementInterface The object processed again from its canonicalised
     * representation verified by the signature.
     * @throws \SimpleSAML\XMLSecurity\Exception\NoSignatureFoundException if the object is not signed.
     * @throws \SimpleSAML\XMLSecurity\Exception\InvalidArgumentException if no key is passed and there is no KeyInfo
     * in the signature.
     * @throws \SimpleSAML\XMLSecurity\Exception\RuntimeException if the signature fails to validate.
     */
    public function verify(?SignatureAlgorithmInterface $verifier = null): SignedElementInterface;
}
