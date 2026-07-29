<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Alg\Signature;

use SimpleSAML\XMLSecurity\Alg\AlgorithmInterface;
use SimpleSAML\XMLSecurity\Backend\SignatureBackend;
use SimpleSAML\XMLSecurity\Key\KeyInterface;

/**
 * An interface representing algorithms that can be used for digital signatures.
 *
 * @package simplesamlphp/xml-security
 */
interface SignatureAlgorithmInterface extends AlgorithmInterface
{
    /**
     * Get the digest used by this signature algorithm.
     *
     * @return string The identifier of the digest algorithm used.
     */
    public function getDigest(): string;


    /**
     * Get the key to use with this signature algorithm.
     *
     * @return \SimpleSAML\XMLSecurity\Key\KeyInterface
     */
    public function getKey(): KeyInterface;


    /**
     * Set the backend to use for actual computations by this algorithm.
     *
     * @param \SimpleSAML\XMLSecurity\Backend\SignatureBackend|null $backend The backend to use, or null if we want to
     * use the default.
     *
     */
    public function setBackend(?SignatureBackend $backend): void;


    /**
     * Sign a given plaintext with this cipher and the loaded key.
     *
     * @param string $plaintext The original text to sign.
     *
     * @return string|false The (binary) signature corresponding to the given plaintext.
     */
    public function sign(string $plaintext);


    /**
     * Verify a signature with this cipher and the loaded key.
     *
     * @param string $plaintext The original signed text.
     * @param string $signature The (binary) signature to verify.
     *
     * @return boolean True if the signature can be verified, false otherwise.
     */
    public function verify(string $plaintext, string $signature): bool;
}
