<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Alg\Encryption;

use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Key\SymmetricKey;

/**
 * Class implementing the 3DES encryption algorithm.
 *
 * @package simplesamlphp/xml-security
 */
class TripleDES extends AbstractEncryptor
{
    /**
     * 3DES constructor.
     *
     * @param \SimpleSAML\XMLSecurity\Key\SymmetricKey $key The symmetric key to use.
     */
    public function __construct(
        #[\SensitiveParameter]
        SymmetricKey $key,
    ) {
        parent::__construct($key, C::BLOCK_ENC_3DES);
    }


    /**
     * @return string[]
     */
    public static function getSupportedAlgorithms(): array
    {
        return [C::BLOCK_ENC_3DES];
    }
}
