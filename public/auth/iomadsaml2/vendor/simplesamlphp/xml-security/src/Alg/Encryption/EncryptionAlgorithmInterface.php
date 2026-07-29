<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Alg\Encryption;

use SimpleSAML\XMLSecurity\Alg\AlgorithmInterface;
use SimpleSAML\XMLSecurity\Backend\EncryptionBackend;
use SimpleSAML\XMLSecurity\Key\KeyInterface;

/**
 * An interface representing algorithms that can be used for encryption.
 *
 * @package simplesamlphp/xml-security
 */
interface EncryptionAlgorithmInterface extends AlgorithmInterface
{
    /**
     * Get the key to use with this encryption algorithm.
     *
     * @return \SimpleSAML\XMLSecurity\Key\KeyInterface
     */
    public function getKey(): KeyInterface;


    /**
     * Set the backend to use for actual computations by this algorithm.
     *
     * @param \SimpleSAML\XMLSecurity\Backend\EncryptionBackend|null $backend The encryption backend to use, or null if
     * we want to use the default provided by the specific implementation.
     */
    public function setBackend(?EncryptionBackend $backend): void;


    /**
     * Encrypt a given plaintext with this cipher and the loaded key.
     *
     * @param string $plaintext The original text to encrypt.
     *
     * @return string The encrypted plaintext (ciphertext).
     */
    public function encrypt(string $plaintext): string;


    /**
     * Decrypt a given ciphertext with this cipher and the loaded key.
     *
     * @param string $ciphertext The encrypted text to decrypt.
     *
     * @return string The decrypted ciphertext (plaintext).
     */
    public function decrypt(string $ciphertext): string;
}
