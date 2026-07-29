<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Backend;

use SimpleSAML\XMLSecurity\Key\KeyInterface;

/**
 * Interface for backends implementing digital signatures.
 *
 * @package simplesamlphp/xml-security
 */
interface SignatureBackend
{
    /**
     * Set the digest algorithm to use.
     *
     * @param string $digest The identifier of the digest algorithm.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\InvalidArgumentException If the given digest is not valid.
     */
    public function setDigestAlg(string $digest): void;


    /**
     * Sign a given plaintext with this cipher and a given key.
     *
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The key to use to sign.
     * @param string $plaintext The original text to sign.
     *
     * @return string The (binary) signature corresponding to the given plaintext.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\RuntimeException If there is an error while signing the plaintext.
     */
    public function sign(
        #[\SensitiveParameter]
        KeyInterface $key,
        string $plaintext,
    ): string;


    /**
     * Verify a signature with this cipher and a given key.
     *
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The key to use to verify.
     * @param string $plaintext The original signed text.
     * @param string $signature The (binary) signature to verify.
     *
     * @return boolean True if the signature can be verified, false otherwise.
     */
    public function verify(
        #[\SensitiveParameter]
        KeyInterface $key,
        string $plaintext,
        string $signature,
    ): bool;
}
