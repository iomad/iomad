<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

/**
 * Class BlacklistedAlgorithmException
 *
 * This exception is thrown when the algorithm used is on a blacklist
 *
 * @package simplesamlphp/xml-security
 */
class BlacklistedAlgorithmException extends SignatureVerificationFailedException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Blacklisted algorithm.')
    {
        parent::__construct($message);
    }
}
