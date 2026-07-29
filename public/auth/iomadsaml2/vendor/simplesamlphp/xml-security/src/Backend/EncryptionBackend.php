<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Backend;

use SimpleSAML\XMLSecurity\Key\KeyInterface;

/**
 * Interface for backends implementing encryption.
 *
 * @package simplesamlphp/xml-security
 */
interface EncryptionBackend
{
    /**
     * Set the cipher to be used by the backend.
     *
     * @param string $cipher The identifier of the cipher.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\InvalidArgumentException If the cipher is unknown or not supported.
     *
     * @see \SimpleSAML\XMLSecurity\Constants
     */
    public function setCipher(string $cipher): void;


    /**
     * Encrypt a given plaintext with this cipher and a given key.
     *
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The key to use to encrypt.
     * @param string $plaintext The original text to encrypt.
     *
     * @return string The encrypted plaintext (ciphertext).
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\RuntimeException If there is an error while encrypting the plaintext.
     */
    public function encrypt(
        #[\SensitiveParameter]
        KeyInterface $key,
        string $plaintext,
    ): string;


    /**
     * Decrypt a given ciphertext with this cipher and a given key.
     *
     * @param \SimpleSAML\XMLSecurity\Key\KeyInterface $key The key to use to decrypt.
     * @param string $ciphertext The encrypted text to decrypt.
     *
     * @return string The decrypted ciphertext (plaintext).
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\RuntimeException If there is an error while decrypting the ciphertext.
     */
    public function decrypt(
        #[\SensitiveParameter]
        KeyInterface $key,
        string $ciphertext,
    ): string;
}
