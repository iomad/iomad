<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML;

use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmFactory;
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmInterface;
use SimpleSAML\XMLSecurity\Backend\EncryptionBackend;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Key\SymmetricKey;
use SimpleSAML\XMLSecurity\XML\ds\KeyInfo;
use SimpleSAML\XMLSecurity\XML\xenc\CipherData;
use SimpleSAML\XMLSecurity\XML\xenc\CipherValue;
use SimpleSAML\XMLSecurity\XML\xenc\EncryptedData;
use SimpleSAML\XMLSecurity\XML\xenc\EncryptedKey;
use SimpleSAML\XMLSecurity\XML\xenc\EncryptionMethod;

/**
 * Trait aggregating functionality for elements that can be encrypted.
 *
 * @package simplesamlphp/xml-security
 */
trait EncryptableElementTrait
{
    /**
     * The length of the session key to use when encrypting.
     *
     * Override to change it if desired.
     *
     * @var int
     */
    protected int $sessionKeyLen = 16;

    /**
     * The identifier of the block cipher to use to encrypt this object.
     *
     * Override to change it if desired.
     *
     * @var string
     */
    protected string $blockCipherAlgId = C::BLOCK_ENC_AES256_GCM;


    /**
     * Encryt this object.
     *
     * @param \SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmInterface $encryptor The encryptor to use,
     * either to encrypt the object itself, or to encrypt a session key (if the encryptor implements a key transport
     * algorithm).
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\EncryptedData
     */
    public function encrypt(EncryptionAlgorithmInterface $encryptor): EncryptedData
    {
        $keyInfo = null;
        if (in_array($encryptor->getAlgorithmId(), C::$KEY_TRANSPORT_ALGORITHMS)) {
            // the encryptor uses a key transport algorithm, use that to generate a session key
            $sessionKey = SymmetricKey::generate($this->sessionKeyLen);

            $encryptedKey = EncryptedKey::fromKey(
                $sessionKey,
                $encryptor,
                new EncryptionMethod($encryptor->getAlgorithmId()),
            );

            $keyInfo = new KeyInfo([$encryptedKey]);

            $factory = new EncryptionAlgorithmFactory(
                $this->getBlacklistedAlgorithms() ?? EncryptionAlgorithmFactory::DEFAULT_BLACKLIST,
            );
            $encryptor = $factory->getAlgorithm($this->blockCipherAlgId, $sessionKey);
            $encryptor->setBackend($this->getEncryptionBackend());
        }

        $xmlRepresentation = $this->toXML();

        return new EncryptedData(
            new CipherData(
                new CipherValue(
                    base64_encode($encryptor->encrypt($xmlRepresentation->ownerDocument->saveXML($xmlRepresentation))),
                ),
            ),
            null,
            C::XMLENC_ELEMENT,
            null,
            null,
            new EncryptionMethod($encryptor->getAlgorithmId()),
            $keyInfo,
        );
    }


    /**
     * Get the encryption backend to use for any encryption operation.
     *
     * @return \SimpleSAML\XMLSecurity\Backend\EncryptionBackend|null The encryption backend to use, or null if we
     * want to use the default.
     */
    abstract public function getEncryptionBackend(): ?EncryptionBackend;


    /**
     * Get the list of algorithms that are blacklisted for any encryption operation.
     *
     * @return string[]|null An array with all algorithm identifiers that are blacklisted, or null to use this
     * libraries default.
     */
    abstract public function getBlacklistedAlgorithms(): ?array;


    /**
     * Return a string representation of this object.
     *
     * @return string
     */
    abstract public function __toString(): string;
}
