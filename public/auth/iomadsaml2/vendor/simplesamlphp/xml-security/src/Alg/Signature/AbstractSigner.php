<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Alg\Signature;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XMLSecurity\Backend;
use SimpleSAML\XMLSecurity\Backend\SignatureBackend;
use SimpleSAML\XMLSecurity\Exception\UnsupportedAlgorithmException;
use SimpleSAML\XMLSecurity\Key\KeyInterface;

/**
 * An abstract class that implements a generic digital signature algorithm.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractSigner implements SignatureAlgorithmInterface
{
    /** @var string */
    protected const DEFAULT_BACKEND = Backend\OpenSSL::class;

    /** @var \SimpleSAML\XMLSecurity\Backend\SignatureBackend */
    protected SignatureBackend $backend;


    /**
     * Build a signature algorithm.
     *
     * Extend this class to implement your own signers.
     *
     * WARNING: remember to adjust the type of the key to the one that works with your algorithm!
     *
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The signing key.
     * @param string $algId The identifier of this algorithm.
     * @param string $digest The identifier of the digest algorithm to use.
     */
    public function __construct(
        #[\SensitiveParameter]
        private KeyInterface $key,
        protected string $algId,
        protected string $digest,
    ) {
        Assert::oneOf(
            $algId,
            static::getSupportedAlgorithms(),
            sprintf('Unsupported algorithm for %s', static::class),
            UnsupportedAlgorithmException::class,
        );

        /** @var \SimpleSAML\XMLSecurity\Backend\SignatureBackend $backend */
        $backend = new (static::DEFAULT_BACKEND)();
        $this->setBackend($backend);
        $this->backend->setDigestAlg($digest);
    }


    /**
     * @return string
     */
    public function getAlgorithmId(): string
    {
        return $this->algId;
    }


    /**
     * @return string
     */
    public function getDigest(): string
    {
        return $this->digest;
    }


    /**
     * @return \SimpleSAML\XMLSecurity\Key\KeyInterface
     */
    public function getKey(): KeyInterface
    {
        return $this->key;
    }


    /**
     * @inheritDoc
     */
    public function setBackend(?SignatureBackend $backend): void
    {
        if ($backend === null) {
            return;
        }

        $this->backend = $backend;
        $this->backend->setDigestAlg($this->digest);
    }


    /**
     * Sign a given plaintext with the current algorithm and key.
     *
     * @param string $plaintext The plaintext to sign.
     *
     * @return string The (binary) signature corresponding to the given plaintext.
     */
    final public function sign(string $plaintext): string
    {
        return $this->backend->sign($this->key, $plaintext);
    }


    /**
     * Verify a signature with the current algorithm and key.
     *
     * @param string $plaintext The original signed text.
     * @param string $signature The (binary) signature to verify.
     *
     * @return boolean True if the signature can be verified, false otherwise.
     */
    final public function verify(string $plaintext, string $signature): bool
    {
        return $this->backend->verify($this->key, $plaintext, $signature);
    }
}
