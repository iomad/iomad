<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Alg\KeyTransport;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XMLSecurity\Backend;
use SimpleSAML\XMLSecurity\Backend\EncryptionBackend;
use SimpleSAML\XMLSecurity\Exception\UnsupportedAlgorithmException;
use SimpleSAML\XMLSecurity\Key\KeyInterface;

/**
 * An abstract class that implements a generic key transport algorithm.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractKeyTransporter implements KeyTransportAlgorithmInterface
{
    /** @var string */
    protected const DEFAULT_BACKEND = Backend\OpenSSL::class;

    /** @var \SimpleSAML\XMLSecurity\Backend\EncryptionBackend */
    protected EncryptionBackend $backend;


    /**
     * Build a key transport algorithm.
     *
     * Extend this class to implement your own key transporters.
     *
     * WARNING: remember to adjust the type of the key to the one that works with your algorithm!
     *
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The encryption key.
     * @param string $algId The identifier of this algorithm.
     */
    public function __construct(
        #[\SensitiveParameter]
        private KeyInterface $key,
        protected string $algId,
    ) {
        Assert::oneOf(
            $algId,
            static::getSupportedAlgorithms(),
            'Unsupported algorithm for ' . static::class,
            UnsupportedAlgorithmException::class,
        );

        /** @var \SimpleSAML\XMLSecurity\Backend\EncryptionBackend $backend */
        $backend = new (static::DEFAULT_BACKEND)();
        $this->setBackend($backend);
    }


    /**
     * @return string
     */
    public function getAlgorithmId(): string
    {
        return $this->algId;
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
    public function setBackend(?EncryptionBackend $backend): void
    {
        if ($backend === null) {
            return;
        }

        $this->backend = $backend;
        $this->backend->setCipher($this->algId);
    }


    /**
     * Encrypt a given key with this cipher and the loaded key.
     *
     * @param string $plaintext The original key to encrypt.
     *
     * @return string The encrypted key (ciphertext).
     */
    public function encrypt(string $plaintext): string
    {
        return $this->backend->encrypt($this->key, $plaintext);
    }


    /**
     * Decrypt a given key with this cipher and the loaded key.
     *
     * @note The class of the returned key will depend on the algorithm it is going to be used for.
     *
     * @param string $ciphertext The encrypted key.
     *
     * @return string The decrypted key.
     */
    public function decrypt(string $ciphertext): string
    {
        return $this->backend->decrypt($this->key, $ciphertext);
    }
}
