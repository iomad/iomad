<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

/**
 * Class SignatureVerificationFailedException
 *
 * This exception is thrown when we can't verify the signature for a given DOMDocument or DOMElement.
 *
 * @package simplesamlphp/xml-security
 */
class SignatureVerificationFailedException extends RuntimeException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Signature verification failed.')
    {
        parent::__construct($message);
    }
}
