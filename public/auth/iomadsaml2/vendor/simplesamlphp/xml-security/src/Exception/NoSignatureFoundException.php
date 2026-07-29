<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

/**
 * Class NoSignatureFoundException
 *
 * This exception is thrown when we can't find a signature in a given DOM document or element.
 *
 * @package simplesamlphp/xml-security
 */
class NoSignatureFoundException extends SignatureVerificationFailedException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'There is no signature in the document or element.')
    {
        parent::__construct($message);
    }
}
