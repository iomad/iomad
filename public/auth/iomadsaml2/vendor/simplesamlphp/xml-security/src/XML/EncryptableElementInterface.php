<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML;

use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmInterface;
use SimpleSAML\XMLSecurity\XML\xenc\EncryptedData;

/**
 * Interface for elements that can be encrypted.
 *
 * @package simplesamlphp/xml-security
 */
interface EncryptableElementInterface
{
    /**
     * Encrypt this object.
     *
     * @param \SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmInterface $encryptor The encryptor to use.
     * @return \SimpleSAML\XMLSecurity\XML\xenc\EncryptedData The resulting EncryptedData object.
     */
    public function encrypt(EncryptionAlgorithmInterface $encryptor): EncryptedData;
}
