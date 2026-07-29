<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Backend;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;
use SimpleSAML\XMLSecurity\Key\KeyInterface;

use function hash_equals;
use function hash_hmac;

/**
 * Backend for digital signatures based on hash-based message authentication codes.
 *
 * @package SimpleSAML\XMLSecurity\Backend
 */
final class HMAC implements SignatureBackend
{
    /** @var string */
    protected string $digest;


    /**
     * Build an HMAC backend.
     */
    public function __construct()
    {
        $this->digest = C::$DIGEST_ALGORITHMS[C::DIGEST_SHA256];
    }


    /**
     * Set the digest algorithm to be used by this backend.
     *
     * @param string $digest The identifier of the digest algorithm.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\InvalidArgumentException If the given digest is not valid.
     */
    public function setDigestAlg(string $digest): void
    {
        Assert::keyExists(
            C::$DIGEST_ALGORITHMS,
            $digest,
            'Unknown digest or non-cryptographic hash function.',
            InvalidArgumentException::class,
        );

        $this->digest = C::$DIGEST_ALGORITHMS[$digest];
    }


    /**
     * Sign a given plaintext with this cipher and a given key.
     *
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The key to use to sign.
     * @param string $plaintext The original text to sign.
     *
     * @return string The (binary) signature corresponding to the given plaintext.
     */
    public function sign(
        #[\SensitiveParameter]
        KeyInterface $key,
        string $plaintext,
    ): string {
        return hash_hmac($this->digest, $plaintext, $key->getMaterial(), true);
    }


    /**
     * Verify a signature with this cipher and a given key.
     *
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The key to use to verify the signature.
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
    ): bool {
        return hash_equals(hash_hmac($this->digest, $plaintext, $key->getMaterial(), true), $signature);
    }
}
