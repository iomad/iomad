<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

/**
 * Class CanonicalizationFailedException
 *
 * This exception is thrown when we can't canonicalize the referenced document.
 *
 * @package simplesamlphp/xml-security
 */
class CanonicalizationFailedException extends RuntimeException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Canonicalization failed.')
    {
        parent::__construct($message);
    }
}
