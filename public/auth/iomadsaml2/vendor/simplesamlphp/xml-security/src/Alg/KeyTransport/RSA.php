<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Alg\KeyTransport;

use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Key\AsymmetricKey;

/**
 * Class implementing the RSA key transport algorithms.
 *
 * @package simplesamlphp/xml-security
 */
final class RSA extends AbstractKeyTransporter
{
    /**
     * RSA constructor.
     *
     * @param \SimpleSAML\XMLSecurity\Key\AsymmetricKey $key The asymmetric key (either public or private) to use.
     * @param string $algId The identifier of this algorithm.
     */
    public function __construct(
        #[\SensitiveParameter]
        AsymmetricKey $key,
        string $algId = C::KEY_TRANSPORT_OAEP_MGF1P,
    ) {
        parent::__construct($key, $algId);
    }


    /**
     * @return string[]
     */
    public static function getSupportedAlgorithms(): array
    {
        return C::$KEY_TRANSPORT_ALGORITHMS;
    }
}
