<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

/**
 * Class ReferenceValidationFailedException
 *
 * This exception is thrown when we can't validate the signature against the referenced DOMDocument or DOMElement.
 *
 * @package simplesamlphp/xml-security
 */
class ReferenceValidationFailedException extends SignatureVerificationFailedException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Reference validation failed.')
    {
        parent::__construct($message);
    }
}
