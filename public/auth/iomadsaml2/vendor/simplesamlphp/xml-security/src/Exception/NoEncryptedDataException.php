<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

/**
 * Class NoEncryptedData
 *
 * This exception is thrown when we can't find encrypted data in a given DOM document or element.
 *
 * @package simplesamlphp/xml-security
 */
class NoEncryptedDataException extends RuntimeException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'There is no EncryptedData in the document or element.')
    {
        parent::__construct($message);
    }
}
